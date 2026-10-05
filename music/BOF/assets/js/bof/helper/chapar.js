
window.chapar = {

  push_supported: function(){

    if ( window.apk ){
      window.bof.log( "--> Push notification supported by APK" );
      return true;
    }

    if (
      !( 'Notification' in window ) ||
      !( "ServiceWorker" in window ) ||
      !window.app.config.setting.vapid_public
    ){
      window.bof.log( "--> Push notification not supported", 5, {css:"color:red"} );
      return false;
    }

    window.bof.log( "--> Push notification supported" );
    return true;

  },
  push_check: function( ignoreSub ){

    var promise = $.Deferred();

    if ( ignoreSub === true ? false : !window.cache.get( "push_enabled" ) ){
      promise.resolve( false )
      return promise;
    }

    if ( window.apk ){
      PushNotification.hasPermission((status) => {
        promise.resolve( status.isEnabled )
      })
      return promise
    }
    
    if ( 'Notification' in window ? ( Notification.permission === 'granted' ) : false ){
      promise.resolve( true );
    }
    else {
      promise.resolve( false );
    }

    return promise;

  },
  push_suggest_registering: function(){

    var dont_suggest = window.cache.get( "push_dont_suggest", false );

    if ( !dont_suggest )
    return true;

    if ( window.apk ){
      // check if access is denied already
    } else if ( 'Notification' in window ? Notification.permission !== 'denied' : false ){
      return false;
    }

    if ( window._g._mt() - dont_suggest > (6*60*60*1000) )
    return true;

    return false;

  },
  push_register: function($retry){

    if ( !window.chapar.push_supported() )
    return;

    if ( !window.user.logged() ){
      window.ui.link.navigate("userAuth")
      return;
    }

    window.chapar.push_check(true).done(function (hasPer) {

      window.bof.log( "Chapar -> Permission: " + (hasPer ? "yes" : "none") )

      if (!hasPer && $retry === true) {
        window.bof.log( "Chapar -> User denined permission" )
        return;
      }

      if (!hasPer) {
        if (window.apk) {
          cordova.plugins.diagnostic.switchToNotificationSettings();
          document.addEventListener("resume", function () {
            PushNotification.hasPermission((status) => {
              if (status.isEnabled) {
                hasPer = true;
                window.chapar.push_register(true)
              }
            })
          });
        }
        else {
          Notification.requestPermission().then((permission) => {
            if (permission === "granted") {
              hasPer = true;
              window.chapar.push_register(true)
            }
          });
        }
        return;
      }

      var key = $.Deferred();
      if (window.apk) {
        window.apk.chapar.push_register(key)
      }
      else {
        navigator.serviceWorker.ready
        .then(function (registration) {
          registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: window.app.config.setting.vapid_public,
          }).then( function( pushSubscription ) {
            key.resolve( pushSubscription )
          });
        })
      }

      key.done(function (pushSubscription) {

        window.becli.exe({
          endpoint: "user_push_register",
          post: {
            push_subscription: JSON.stringify(pushSubscription)
          },
          callBack: function (sta, data) {
            if (sta) {
              window.app.becli.alert(true, window.lang.return("activated", { ucfirst: true }));
              window.cache.set("push_enabled", true);
              $(document).find(".chapar_msgs .chapar_register").remove();
              $(document).find(".chapar_msgs").addClass("push_enabled")
            }
          }
        })

      });

    })

  },

  load_messages: function( $args ){

    $args = $.extend( {
      page: 1,
      type: "list"
    }, $args );

    if ( $args.type == "list" ){
      $(document).find(".chapar_msgs").addClass("loading");
      $(document).find(".chapar_msgs .list").html("<div class='ph_skel_wrapper ph_ts'>\
        <div class='skel'><div class='avatar'></div><div class='line'></div></div>\
        <div class='skel'><div class='avatar'></div><div class='line'></div></div>\
        <div class='skel'><div class='avatar'></div><div class='line'></div></div>\
      </div>");
    }

    window.becli.exe({
      liquid: true,
      endpoint: "user_chapar",
      post: {
        page: $args.page,
        type: $args.type
      },
      callBack: function( sta, data ){

        if ( data.type == "list" ){

          $(document).find(".chapar_msgs").removeClass("loading has_unseen").addClass("loaded");
          if ( sta ? ( data ? ( data.items ? data.items.length : false ) : false ) : false ){
            var nots_html = "";
            nots_html += "<div id='chapar_list'>";
            for ( var z=0; z<data.items.length; z++ ){
              var not = data.items[z];
              if ( !not ) continue;
              nots_html += "<div class='payam_wrapper'>";
                nots_html += "<div class='payam type_"+not.type+" img_"+(not.image?"yes":"no")+" seen_"+(not.seen?"yes":"no")+"' data-link='"+not.link+"'>";
                  if ( not.image ) nots_html += "<div class='img_wrapper'><img src='"+not.image+"'></div>";
                  nots_html += "<div class='c'>"+not.title+"</div>";
                  nots_html += "<div class='time'>"+not.time+"</div>";
                nots_html += "</div>";
              nots_html += "</div>";
            }
            nots_html += "</div>";
            if ( data.has_more ){
              nots_html += "<div class='more chapar_more' data-page='"+data.has_more+"'>"+ window.lang.return( "more", { ucfirst: true } ) +"</div>";
            }
            $(document).find(".chapar_msgs .list").html( nots_html );
          }
          else {
            $(document).find(".chapar_msgs .list").html("<div class='empty'>\
              <span class='mdi mdi-emoticon-sad-outline'></span>\
              <span class='_t'>"+ window.lang.return( "msngr_nada", { ucfirst: true } ) +"</span>\
              <span class='_te'>"+ window.lang.return( "msngr_nada_tip", { ucfirst: true } ) +"</span>\
            </div>");
          }

        }

        $(document).find(".chapar_menu_wrapper").removeClass("has_unseen").find("._count").remove();
        if ( sta ? data.unseen : false ){
          $(document).find(".chapar_menu_wrapper").addClass("has_unseen").append("<span class='_count'>"+data.unseen+"</span>")
          $(document).find(".chapar_msgs").addClass("has_unseen");
        }

      }
    })

  },

  listen: function(){

    $(document).on("click", ".dropdown_button.chapar_menu_wrapper", function () {

      window.chapar.push_check().done(function (push_enabled) {

        var push_supported = window.chapar.push_supported();
        var push_suggest_registering = (push_enabled || !push_supported) ? false : window.chapar.push_suggest_registering();

        $(document).find(".chapar_msgs").removeClass("push_supported push_enabled push_suggest_registering loaded").addClass("loading");

        if (push_supported) $(document).find(".chapar_msgs").addClass("push_supported")
        if (push_enabled) $(document).find(".chapar_msgs").addClass("push_enabled")
        if (push_suggest_registering) $(document).find(".chapar_msgs").addClass("push_suggest_registering")

        if (window.user.logged())
          window.chapar.load_messages();

        else {
          $(document).find(".chapar_msgs").addClass("not_logged");
          $(document).find(".chapar_msgs .list").html("<div class='empty'>\
          <span class='mdi mdi-emoticon-sad-outline'></span>\
          <span class='_t'>"+ window.lang.return("msngr_nada", { ucfirst: true }) + "</span>\
          <span class='_te'>"+ window.lang.return("msngr_nada_tip", { ucfirst: true }) + "</span>\
        </div>");
        }

      })

    });
    $(document).on( "click", ".chapar_msgs #chapar_list .payam_wrapper", function(){

      var payam = $(this).find(".payam");
      if ( payam ){
        var link = payam.data("link");
        if ( link ){
          window.ui.link.navigate( link );
        }
      }

    } );

    $(document).on( "click", ".chapar_msgs .more.chapar_more", function(){

      var newPage = $(document).find(".chapar_msgs .more.chapar_more").data("page");
      window.chapar.load_messages({
        page: newPage
      });

    } );
    $(document).on( "click", ".chapar_msgs.has_unseen .titles ._maar", function(){
      window.chapar.load_messages({
        type: "set",
      })
    } );

    // registration
    $(document).on( "click", ".chapar_msgs .chapar_register ._actions ._act#chapar_do_register", function(){
      window.chapar.push_register();
    } );
    $(document).on( "click", ".chapar_msgs.push_supported.push_enabled .titles ._ic.push._en", function(){
      window.chapar.push_register();
    } );
    $(document).on( "click", ".chapar_msgs .chapar_register ._actions ._act.later", function(){
      $(document).find(".chapar_msgs .chapar_register").remove();
      $(document).find(".chapar_msgs").removeClass("push_suggest_registering");
      window.cache.set( "push_dont_suggest", window._g._mt() );
    } );

    if ( window.user.logged() )
    window.chapar.load_messages({
      type: "get"
    })

    window.chapar.sw();

  },
  sw: function(){

    window.bof.log( "Chapar:SW: Checking", 4, { css: 'color:#ccc' } );

    if ( window.chapar.push_check() ){
      var DB = localforage.createInstance({
        name: "chapar"
      });
      DB.getItem( "sw_new" ).then( item => {
        if ( item === "yes" ){
          DB.setItem( "sw_new", "no" );
          if ( window.user.logged() ) {
            window.chapar.load_messages({
              type: "get"
            })
          }
        }
      } );
    }
    else if ( window.user.logged() ) {
      window.chapar.load_messages({
        type: "get"
      })
    }

    setTimeout(function(){
      window.chapar.sw()
    },5*60*1000);

  },

};
