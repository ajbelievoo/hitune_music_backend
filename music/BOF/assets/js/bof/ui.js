"use strict";

window.ui = {

  page: {

    name: false,
    args: null,
    data: {},
    cache: {
      becli: []
    },

    set: function( $name, $args ){

      window.ui.page.name = $name;
      window.ui.page.args = $args;

    },
    add_data: function( $key, $val ){
      window.ui.page.data[ $key ] = $val;
    },
    get_data: function( $key ){
      return window.ui.page.data[ $key ];
    },
    curr: function(){
      return {
        name: window.ui.page.name,
        o_args: window.app.pages[ window.ui.page.name ],
        u_args: window.ui.page.args,
        args: $.extend( window.app.pages[ window.ui.page.name ], window.ui.page.args ),
        data: window.ui.page.data,
        cache: window.ui.page.cache,
        stateID: window.ui.history._state,
        stateData: window.ui.history.get()
      }
    },
    event: function( $page, $event, $args ){

      if ( typeof( $page ) === "string" )
      $page = window.app.pages[ $page ];

      var $promise = $.Deferred();
      var $nativePromise = $.Deferred();
      var $pagePromise = $.Deferred();
      var $globPromise = $.Deferred();

      $(document).trigger( "ui_page_event", {
        event: $event,
        page: $page
      } )

      if ( $page ? $page.events[ $event ] : false ){

        var event_holder = $page.events[ $event ];
        var pageEvent = null;

        if ( typeof( event_holder ) == "string" ){

          var event_holder_parsed = event_holder.split(".").reduce(function(prev, curr) {
            if ( prev )
            return prev[curr];
          }, window );

          if ( event_holder_parsed )
          pageEvent = event_holder_parsed( $args );

        } else {
          pageEvent = event_holder( $args );
        }

        if ( pageEvent !== null && typeof( pageEvent ) == "object" ){

          pageEvent
          .done(function( eventResult ){
            $pagePromise.resolve( eventResult );
          })
          .fail(function( eventErr ){
            $pagePromise.reject( eventErr );
          });

        }
        else {

          $pagePromise.resolve( pageEvent );
        }

      }
      else {
        $pagePromise.resolve( "none" );
      }

      if ( window.app.events[ "page_" + $event ] ){

        var globEvent = window.app.events[ "page_" + $event ]( $args );

        if ( typeof( globEvent ) == "object" ){

          globEvent
          .done(function( eventResult ){
            $globPromise.resolve( eventResult );
          })
          .fail(function( eventErr ){
            $globPromise.reject( eventErr );
          });

        }
        else {
          $globPromise.resolve( globEvent );
        }

      }
      else {
        $globPromise.resolve( "none" );
      }

      if ( $event == "ready" ){
        if ( window.location.hash ){
          var Hash = window.location.hash.substr( 1 );
          if ( $(document).find("#"+Hash).length )
          $(document).find("#main").scrollTop( $(document).find("#"+Hash).offset().top );
        }
      }
      if ( $event == "preloading" ){
        if ( $page ? $page.extenders : false ){

          var $extenders_promises = [];
          for ( var $i=0; $i<Object.keys($page.extenders).length; $i++ ){
            var $extender_k = Object.keys($page.extenders)[ $i ];
            var $extender = $page.extenders[ $extender_k ];
            $extenders_promises.push( window.app._extension_new( $extender_k, $extender, true ) );
          }

          $.when.apply( $, $extenders_promises ).done( function(){
            $nativePromise.resolve();
          } ).fail( function(){
            $nativePromise.reject();
          } )

        } else {
          $nativePromise.resolve();
        }
      }
      else {
        $nativePromise.resolve();
      }

      $.when( $nativePromise, $pagePromise, $globPromise ).done(function( $nativeResult, $pageResult, $globResult ){

        $promise.resolve({
          page: $pageResult,
          glob: $globResult,
          native: $nativeResult
        })


      }).fail(function( $nativeError, $pageError, $globError ){

        $promise.reject({
          page: $pageError,
          glob: $globError,
          native: $nativeError
        });
      })

      return $promise;

    },
    load: function( $name, $args ){

      $args = $args ? $args : {};
      $args.name = $name;
      var promise = $.Deferred();
      var currentPage = window.app.pages[ window.ui.page.name ];
      var requestedPage = window.app.pages[ $name ];

      // Lock
      window.ui.lock.on( $name );

      // UnLoad current page
      window.ui.page.event( currentPage, "unloading", $args ).done(function(){

        // Stop current page jobs
        window.ui.page.abortConnections();

        // PreLoad requested page
        window.ui.page.event( requestedPage, "preloading", $args ).done(function(){

          $.when(
            window.ui.theme.load( requestedPage.theme_file, requestedPage.theme_args ),
            window.becli.exe_array( requestedPage.becli, $args )
          ).done(function( themeLoadResult, becliExeResult ){

            window.ui.page.add_data( "becli", becliExeResult );

            window.ui.page.event( requestedPage, "rendering", $args ).done(function(){
              window.render.mix( themeLoadResult, window.ui.page.get_data( "becli" ), $args ).done(function( renderingResult ){
                window.ui.page.event( requestedPage, "displaying", $args ).done(function(){
                  window.ui.page.display( renderingResult, $args ).done(function(){

                    window.ui.page.event( requestedPage, "loaded", $args ).done(function(){

                      window.ui.page.event( currentPage, "unloaded", $args ).fail(function( currentPageUnloadedFailReason ){
                        promise.reject( "currentPageUnloadedFailReason: " + currentPageUnloadedFailReason );
                      });

                      window.ui.page.set( $name, $args );
                      window.ui.lock.off( $name );
                      window.ui.body.setClasses();

                      if ( $args.history !== false && requestedPage.ignore_history !== true ){
                        window.ui.history.add();
                        $(document).find("#main").scrollTop( 0 );
                      }
                      else if ( requestedPage.ignore_history_restore !== true ) {
                        window.ui.history._state = $args.stateID
                        window.ui.history.restoreState();
                      }

                      window.ui.page.event( requestedPage, "ready", $args ).fail(function( requestedPageReadyFailReason ){
                        promise.reject( "requestedPageReadyFailReason: " + requestedPageReadyFailReason );
                      });

                      promise.resolve();
                      window.bof.log( "UI.page.load: Done loading " + $name );

                    }).fail(function( requestedPageLoadedFailedReason ){
                      promise.reject( "requestedPageLoadedFailedReason: " + requestedPageLoadedFailedReason );
                    });

                  }).fail(function(){
                    // ui display failed
                  });
                }).fail(function( requestedPageDisplayingFailedReason ){
                  promise.reject( "requestedPageDisplayingFailedReason: " + requestedPageDisplayingFailedReason );
                });
              }).fail(function(){
                // render mix failed
              })
            }).fail(function( requestedPageRenderingFailedReason ){
              promise.reject( "requestedPageRenderingFailedReason: " + requestedPageRenderingFailedReason.native );
            });
          }).fail(function(e,d){
            promise.reject( "Ui.Page.Load: becli&theme failed: " + JSON.stringify( Array.prototype.slice.call(arguments, 0) ) );
          });
        }).fail(function( requestedPagePreloadingFailedReason ){
          promise.reject( "requestedPagePreloadingFailedReason: " + requestedPagePreloadingFailedReason );
        });


      }).fail(function( currPageUnloadingFailedReason ){
        promise.reject( "currPageUnloadingFailedReason: " + currPageUnloadingFailedReason );
      })

      promise.fail(function( Reason ){
        window.bof.log( Reason );
        window.ui.lock.off( $name );
        $("body").trigger( "ui_page_load_failure", {
          name: $name,
          reason: Reason,
          args: $args
        } )
      });

      return promise;

    },
    reload: function(){

      var cur_args = window.ui.page.curr().u_args;
      cur_args = cur_args ? cur_args : {};

      return window.ui.page.load( window.ui.page.name, $.extend( cur_args, {
        history: false,
        stateID: window.ui.page.curr().stateID
      } ) );

    },
    display: function( content, $args ){

      $("body #main .content").html( content );

      var promise = $.Deferred();
      promise.resolve();
      return promise;

    },

    abortConnections: function(){
      if ( !window.ui.page.cache.becli ) return;
      for( var i=0; i<window.ui.page.cache.becli.length; i++ ){
        var _page_a = window.ui.page.cache.becli[i];
        _page_a.abort();
      }
      window.ui.page.cache.becli = [];
    },

  },
  lock: {

    activeLocks: [],
    on: function( $name ){

      if ( window.ui.lock.activeLocks.length == 0 ){
        window.app.events.lock.first_on( $name );
        window.ui.body.addClass( "loading", true );
      }

      window.ui.lock.activeLocks.push( $name );
      window.app.events.lock.on( $name );

    },
    off: function( $name ){

      window.ui.lock.activeLocks = jQuery.grep( window.ui.lock.activeLocks, function(value) {
        return value != $name;
      });

      window.app.events.lock.off( $name );

      if ( window.ui.lock.activeLocks.length == 0 ){
        window.app.events.lock.all_off( $name );
        window.ui.body.removeClass( "loading", true );
      }

    },
    offAll: function(){

      window.ui.lock.activeLocks = [];
      window.app.events.lock.all_off(  );
      window.ui.body.removeClass( "loading", true );

    }

  },
  history: {

    _state: null,
    _states: {},

    listen: function(){

      window.onpopstate = function(e) {

        if ( window.app.events.historyPopState ){
          if ( window.app.events.historyPopState() == "HALT" ){
            history.replaceState(history.state, document.title, location.href);
            e.preventDefault();
            e.stopPropagation();
            return false;
          }
        }

        if ( $("body").hasClass("active_modal") ){
          history.replaceState(history.state, document.title, location.href);
          window.bof_modal.close();
          e.preventDefault();
          e.stopPropagation();
          return false;
        }

        var stateData = null;

        if ( e.state ? e.state.ID : false )
        stateData = window.ui.history.get( e.state.ID );

        if ( stateData ){
          window.ui.link.navigate( stateData.link, {
            history: false,
            stateID: e.state ? e.state.ID : false,
          } );
        }

        e.stopPropagation();
        return false;

      };

    },
    add: function( $link, $title ){

      var link = window.ui.page.curr().args.link;
      var title = window.ui.page.curr().args.title;
      var title_hook = window.ui.page.curr().args.title_hook;
      if ( window.ui.page.curr().args.urlData )
      link = window.ui.page.curr().args.urlData.url.full;

      if ( $link ) link = $link;
      if ( $title ) title = $title;

      if ( window.ui.page.curr().data.becli ? ( window.ui.page.curr().data.becli.single ? ( window.ui.page.curr().data.becli.single.seo ) : false ) : false ){
        title = window.ui.page.curr().data.becli.single.seo.title;
      }
      if ( window.ui.page.curr().data.becli ? ( window.ui.page.curr().data.becli.upload ? ( window.ui.page.curr().data.becli.upload.seo ) : false ) : false ){
        title = window.ui.page.curr().data.becli.upload.seo.title;
      }
      if ( window.ui.page.curr().data.becli ? ( window.ui.page.curr().data.becli.auth ? ( window.ui.page.curr().data.becli.auth.title ) : false ) : false ){
        title = window.ui.page.curr().data.becli.auth.title;
      }

      var ID = window._g._mt() + "_" + ( link && link !== true ? link.substr( link.length-6 ) : "" ) + "_" + Math.round(Math.random()*1000);

      if ( !title && title_hook )
      title = window.lang.return( title_hook, { ucfirst: true } )

      window.ui.history._states[ ID ] = {
        link: link,
        title: title,
        time: window._g._mt()
      };

      window.history.pushState( { ID: ID }, title, window.config.platform == "web" && typeof( link ) === "string" ? link : null );
      document.title = title;

      window.ui.history._state = ID;
      return ID;

    },
    record: function( Var, Val, StateID ){

      if ( window.ui.history._states[ StateID ? StateID : window.ui.history._state ] )
      window.ui.history._states[ StateID ? StateID : window.ui.history._state ][ Var ] = Val;

    },
    get: function( StateID ){
      return window.ui.history._states[ StateID ? StateID : window.ui.history._state ];
    },
    hasPre: function( StateID ){

      StateID = StateID ? StateID : window.ui.history._state;
      return Object.keys( window.ui.history._states ).indexOf( window.ui.history._state ) > 0;

    },
    removePre: function( StateID ){

      StateID = StateID ? StateID : window.ui.history._state;
      if ( Object.keys( window.ui.history._states ).indexOf( window.ui.history._state ) > 0 ){
        var keys =  Object.keys( window.ui.history._states );
        var preKey = keys[ keys.indexOf( window.ui.history._state ) - 1 ];
        delete window.ui.history._states[ preKey ];
      }


    },
    restoreState: function(){

      var state = window.ui.history.get();

      if ( state.scrollTop )
      $(document).find("#main").scrollTop( state.scrollTop );

    }

  },
  theme: {

    _loading: {},
    _cached: {},

    load: function( $path, $args ){

      $args = $args ? $args : {};
      $args.extension = $args.extension ? $args.extension : "html";
      $args.dir = $args.dir ? $args.dir : false;
      $args.base = $args.base ? $args.base : "theme";
      $args.use_base = $args.use_base === false ? false : true;
      $args.reload = $args.reload === true;

      var promise = $.Deferred();

      // Short cache
      if ( Object.keys( window.ui.theme._cached ).includes( $path ) && !$args.reload ){
        promise.resolve( window.ui.theme._cached[ $path ] );
        return promise;
      }

      // Long cache
      if ( window.apk ){
        var cached = window.cache.get( "html_" + $path );
        if ( cached && !window.config.production && !$args.reload ){
          promise.resolve( cached );
          return promise;
        }
      }

      // Already loading
      if ( Object.keys( window.ui.theme._loading ).includes( $path ) )
      return window.ui.theme._loading[ $path ];

      // Load html content
      window.ui.theme._loading[ $path ] = promise;

      var $version = null;
      if ( Object.keys( $args ).includes( "version" ) ) $version = $args.version;
      else if ( window.config ? window.config.version : false ) $version = window.config.version;
      if ( $args.cache === false ) $version = "dont_cache";
      if ( window.config ? ( !window.config.production && $version ) : false ) $version = "dont_cache";

      $.ajax({
        url: ( $args.use_base ? ( $args.base.endsWith("/") ? $args.base : $args.base + "/" ) : "" ) + 
        ( $args.dir ? ( $args.dir.endsWith("/") ? $args.dir : $args.dir + "/" ) : "" ) + $path + "." + $args.extension + ( $version ? ( "?bof_version=" + $version + ( $version == "dont_cache" ? "&bof_dont_cache=" + new Date().getTime() : "" ) ) : "" ),
        timeout: 10000
      }).done(function(data){

        window.ui.theme._cached[ $path ] = data;
        if ( window.apk )
        window.cache.set( "html_" + $path, data );
        promise.resolve( data );
        delete window.ui.theme._loading[ $path ];

      }).fail(function(){

        promise.reject( "Loading " + $path + " failed" );
        window.bof.log( "Ui.Theme.Load: Loading " + $path + " failed" );

      });

      return promise;

    },
    part: function( $path, $args ){

      var promise = $.Deferred();

      $args = $args ? $args : {};
      $args = $.extend( {
        target: "body"
      }, $args );

      window.ui.theme.load( $path, $args ).done(function( loadedPart ){

        if ( $args.render ){
          window.render.mix( loadedPart, {} ).done(function( loadedPartParsed ){
            if ( $args.target )
            $( $args.target ).prepend( "<div class='bof_part'>"+ loadedPartParsed +"</div>" );
            promise.resolve( loadedPartParsed );
          });
        }
        else {
          if ( $args.target )
          $( $args.target ).prepend( "<div class='bof_part'>"+ loadedPart +"</div>" );
          promise.resolve( loadedPart );
        }

      });

      return promise;

    }

  },
  body: {

    _classes: [ "unloaded", "splash" ],

    getClasses: function(){

      var classes = window.ui.body._classes;

      var bodyClasses = window.ui.page.curr().args.body_class;
      if ( bodyClasses ) classes = classes.concat( bodyClasses );

      if ( window.ui.page.curr().data ? ( window.ui.page.curr().data.becli ? ( window.ui.page.curr().data.becli.single ? ( window.ui.page.curr().data.becli.single.page ? window.ui.page.curr().data.becli.single.page.classes : false ) : false ) : false ) : false ){
        var pageClasses = window.ui.page.curr().data.becli.single.page.classes;
        if ( pageClasses ) classes = classes.concat( pageClasses );
      }

      var logged = window.user.logged();
      if ( logged ) classes = classes.concat( "logged" );
      else classes = classes.concat( "notLogged" );

      return classes.concat( [ "page_" + window.ui.page.name ] );

    },
    setClasses: function(){

      var classes = window.ui.body.getClasses();
      $("body").attr( "class", classes.join( " " ) );

    },
    addClass: function( $name, $permanent ){

      if ( !$("body").hasClass( $name ) )
      $("body").addClass( $name );

      if ( $permanent && !window.ui.body._classes.includes( $name ) )
      window.ui.body._classes.push( $name );

      return window.ui.body;

    },
    removeClass: function( $name, $permanent ){

      if ( $("body").hasClass( $name ) )
      $("body").removeClass( $name );

      if ( $permanent && window.ui.body._classes.includes( $name ) )
      window.ui.body._classes = jQuery.grep( window.ui.body._classes, function(value) {
        return value != $name;
      });

      return window.ui.body;

    },
    removeSplashClasses: function(){

      window.ui.body.removeClass( "splash", true );
      window.ui.body.removeClass( "unloaded", true );

    },

  },
  link: {

    listen: function(){

      $(document).on( "click", "a", function(e){

        if ( $(this).attr("target") == "_blank" )
        return true;

        if ( $(this).attr("data-external") )
        return true;

        var $url = $(this).attr("href");

        if ( $url ? $url.startsWith( "mailto:" ) : false )
        return true;

        if ( $url ?
          ( $url.substr( 0, 'http'.length ) == 'http' ? $url.substr( 0, window.config.web.address.length ) != window.config.web.address : false ) || $url.endsWith( ".pdf" ) :
          false
        ){
          e.stopPropagation();
          window.open( $url, '_blank' ).focus();
          return false;
        }

        e.stopPropagation();
        window.ui.link.navigate( $url );
        return false;

      });

    },
    navigate: function( $url, $args ){

      $args = $args ? $args : {};
      var url_parsed = window.ui.link.parse( $url );

      if ( !url_parsed ) return;
      window.ui.link.navigating_to = url_parsed

      return window.ui.page.load( url_parsed.page, $.extend( {
        urlData: url_parsed
      }, $args ) );

    },
    parse: function( $url ){

      if ( !$url )
      return;

      if ( $url.substr( 0, window.config.web.address.length ) == window.config.web.address )
      $url = $url.substr( window.config.web.address.length )

      if ( $url.substr( 0, 'http'.length ) == 'http' )
      return;

      var $base_url_parsed = new URL( window.config.web.address );
      var $base_sub_folder = $base_url_parsed.pathname != "/" ? $base_url_parsed.pathname.substr(1) : false;
      var $full_url = window.config.web.address + $url;
      var $parsed_url = new URL( $full_url );

      var $requested_path = $parsed_url.pathname.substr( 1 );
      if ( $base_sub_folder ? $base_sub_folder == $requested_path.substr( 0, $base_sub_folder.length ) : false ){
        $requested_path = $requested_path.substr( $base_sub_folder.length );

      }

      var $requested_queries = false;
      if ( $parsed_url ? ( $parsed_url.searchParams ? $parsed_url.searchParams.entries() : false ) : false ){
        $requested_queries = Array.from($parsed_url.searchParams.entries()).reduce((obj, [key, value]) => {
          obj[key] = value;
          return obj;
        }, {});
      }

      var $requested_queries_string = "";
      if ( $requested_queries ){
        var $requested_queries_OBJ = new URLSearchParams( $requested_queries );
        $requested_queries_string = $requested_queries_OBJ.toString();
      }

      var pages = window.app.pages;
      for ( var i=0; i<Object.keys( pages ).length; i++ ){

        var page_name = Object.keys( pages )[i];
        var page_args = pages[ page_name ];

        if ( !page_args.url ) continue;

        var regExp = new RegExp( page_args.url, "i" );
        var matchRegExp = $requested_path.match( regExp );

        if ( matchRegExp && page_args.url !== true ){
          return {
            url: {
              full: $url,
              path: $requested_path,
              query: $requested_queries,
              query_s: $requested_queries_string,
              match: matchRegExp.splice( 1 ),
              hash: $parsed_url.hash
            },
            page: page_name
          };
        }

      }

      return false;

    }

  },
  pull_up: {

    listen: function(){

      $(document).on( "mousedown", function( ev ){

        if ( $("#main").scrollTop() > 0  )
        return;

        var iniY = ev.pageY;

        $(document).on( "mousemove" , function (e) {

          var movementY = e.pageY - iniY;
          if ( movementY > 0 ){
            window.ui.pull_up.checkMovement( movementY );
          }

        });

      });
      $(document).on( "mouseup", function () {
        window.ui.pull_up.stopListeningToMovement();
      });
      $(document).on( "mouseleave", function (){
        window.ui.pull_up.stopListeningToMovement();
      });

      $(document).on( "touchstart", function( ev ){

        if ( $("#main").scrollTop() > 0  )
        return;

        var iniY = ev.touches[0].pageY;

        $(document).on( "touchmove", function( e ){

          var movementY = e.touches[0].pageY - iniY;
          if ( movementY > 0 ){
            window.ui.pull_up.checkMovement( movementY );
          }

        });

      });
      $(document).on( "touchend", function (){
        window.ui.pull_up.stopListeningToMovement();
      });
      $(document).on( "touchcancel", function (){
        window.ui.pull_up.stopListeningToMovement();
      });

    },
    checkMovement: function( $movementY ){

      if ( $("body").hasClass("reloading") )
      return;

      if ( $movementY >= 100) {

        $("body").addClass("reloading");

        window.ui.page.reload().done(function(){
          $("body").removeClass("reloading");
        });

        window.ui.pull_up.stopListeningToMovement();

        return;

      }

      $("body").css( "margin-top", $movementY + "px" );
      $("#reloader").css( "height", $movementY + "px" );

    },
    stopListening: function(){

      window.ui.pull_up.stopListeningToMovement();
      $(document).unbind( "mousedown" );
      $(document).unbind( "mouseup" );
      $(document).unbind( "mouseleave" );
      $(document).unbind( "touchstart" );
      $(document).unbind( "touchend" );
      $(document).unbind( "touchcancel" );

    },
    stopListeningToMovement: function(){

      $( "body" ).css( "margin-top", "0px" );
      $( "#reloader" ).css( "height", "0px" );
      $(document).unbind( "mousemove" );
      $(document).unbind( "touchmove" );

    },

  },

}
