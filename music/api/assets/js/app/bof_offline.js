"use strict";

window.bof_offline = {

  listen: function(){

    $(document).on( "click", ".dropdown_button.download_menu_wrapper", function(){
      $(document).find("#download_drop .list").html("loading");
      window.bof_offline.ui.list();
    } );
    $(document).on( "click", ".file_list .file", function(e){

      if ( $(e.target).parents(".buttons").length ) return;

      window.bof_offline.db.get( $(this).data("key") ).then( item_w => {
        window.m2_queue.set( [item_w.item.data.muse] )
        window.m2_queue.active.setFocus()
      } );

    } );
    $(document).on( "click", ".file_list .file .buttons > div", function(e){

      var key = $(this).parents(".file").data("key");
      var action = $(this).attr("class");
      window.bof_offline.db.get( key ).then( item_w => {

        var item = item_w.item;

        if ( action == "delete" && item.state == "done" ){
          window.bof_offline_cli.delete( item );
          window.bof_offline.ui.list();
        }

        if ( action == "cancel" && [ "failed", "paused", "pending" ].includes( item.state ) ){
          window.bof_offline_cli.delete( item );
          window.bof_offline.ui.list();
        }

        if ( action == "pause" && item.state == "downloading" ){

          window.bof_offline_cli.pause();
          setTimeout( function(){
            window.bof_offline_cli._markStop();
            window.bof_offline_cli.exe();
          }, 20 );

        }

        if ( action == "start" && ( item.state == "paused" || item.state == "failed" ) ){

          // start
          var dl_db = window.bof_offline.db._get();
          dl_db.getItem( item.key ).then( _item => {
            _item.state = "pending";
            dl_db.setItem( item.key, _item ).then( () => {
              window.bof_offline_cli.exe();
            } );
          } )

        }

      } );

    } );

    if ( window.app.config.mobile ){
      $(document).find(".file_list").prependTo("#main")
      // $(document).find(".header .download_menu_wrapper").remove();
      // $(document).find(".header .bof_dropdown.file_list").remove();
    } else {
      // $(document).find(".sidebar .bof_dropdown.file_list").remove();
      // $(document).find(".sidebar .download_menu_wrapper").remove();
    }

    window.ui.body.addClass( "bof_offline_button_" + window.app.config.theme.setting.offline_download_button, true );

    window.bof_offline_cli.exe();

    window.addEventListener('offline', function(event){
      window.bof_offline.mode.on();
    });
    window.addEventListener('online', function(event){
      window.bof_offline.mode.off();
    });

    window.bof_offline.count();

  },

  count: function(){

    window.bof_offline.db.count().done(function(count){
      if ( count > 0 ){
        window.ui.body.removeClass( "bof_offline_count_zero", true );
        window.ui.body.addClass( "bof_offline_count_plus", true );
      }
      else{
        window.ui.body.addClass( "bof_offline_count_zero", true );
        window.ui.body.removeClass( "bof_offline_count_plus", true );
      }
    });

  },

  download: function( object_type, object_hash, source_hash, type, inBG ){

    if ( !"serviceWorker" in navigator ) {
      window.app.becli.alert( false, "Browser doesn't support service workers" );
      return;
    }

    var promise = $.Deferred();
    var dl_code = source_hash+"-"+object_type+"-"+object_hash;

    if ( inBG !== true )
    window.bof_modal.set_loading("initial");

    window.becli.exe({
      liquid: true,
      endpoint: "muse_request_download",
      post: {
        object_type: object_type,
        object_hash: object_hash,
        source_hash: source_hash,
        type: type
      },
      callBack: function( sta, data ){

        if ( !sta ){

          window.bof_modal.close();

          if ( data.messages[0] == "locked" )
          window.app.actions.get_unlock_solution(
            object_type,
            object_hash,
            null,
            null,
            source_hash
          )

          promise.reject();

          return;

        }

        if ( !data.source_data.ID ){
          data.source_data.ID = source_hash
          if ( !data.source_data.muse.data.ID ){
            data.source_data.muse.data.ID = source_hash
          }
        }

        if ( type == "out" ){
          window.open( data.source_file_list[0], '_blank' );
        }
        else {
          window.bof_offline.ui.start_download( dl_code, data.source_data, data.source_file_list ).done(function(){
            promise.resolve();
          }).fail(function(){
            promise.reject();
          });
        }

        if ( inBG !== true )
        window.bof_modal.close();

      }
    });

    return promise;

  },
  downloadGroup: function( items, Ini ){

    if ( !items ? true : !items.length )
    return;

    if ( !"serviceWorker" in navigator ) {
      window.app.becli.alert( false, "Browser doesn't support service workers" );
      return;
    }

    var item = items.shift();

    window.bof_offline.download( item.real_ot, item.real_oh, item.hash, "in", Ini!==true ).then(function(){
      if ( items ? items.length : false )
      window.bof_offline.downloadGroup( items, false );
    });

  },

  ui: {
    start_download: function( dl_code, data, file_list ){

      var promise = $.Deferred();
      var calSize = $.Deferred();

      if ( data.size )
      calSize.resolve( data.size );
      else {

        fetch( data.web_address, {
          mode: "no-cors",
          method: "HEAD"
        } )
        .then( response => {
          if ( response.headers.get("content-length") )
          calSize.resolve( parseInt( response.headers.get("content-length" ) ) );
          else
          calSize.resolve( null );
        } )

      }

      calSize.done(function(fileSize){

        window.bof_offline.db.insert( dl_code, data, file_list ).done(function(){

          window.bof_offline.db.count().done(function(count){

            $(document).find("body .download_menu_wrapper .icon").addClass("pulse");
            setTimeout( function(){
              $(document).find("body .download_menu_wrapper .icon").removeClass("pulse");
            }, 8000 );

            window.m2_queue.ui.destroy()
            window.bof_modal.close()

            if ( !$(document).find(".bof_dropdown#download_drop").hasClass("active") ){
              $(document).find("body .download_menu_wrapper").click();
              $(document).find("body #mn_downloads").click()
            } else {
              window.bof_offline.ui.list();
            }

            window.bof_offline_cli.exe();
            promise.resolve();

          });

        }).fail(function(){
          window.app.becli.alert( false, "Can't be downloaded" );
          promise.reject();
        });

      }).fail(function(){
        window.app.becli.alert( false, "Can't be downloaded" );
        promise.reject();
      });

      return promise;

    },
    list: function( $args ){

      $args = $args ? $args : {};

      var promise = $.Deferred();
      window.bof_offline.db.list().then( function( list ){

        list = list ? Object.values( list ) : list;
        if ( !list ? true : !list.length ){
          promise.resolve("<div class='empty'>\
            <span class='mdi mdi-airplane-landing'></span>\
            <div class='e_title'>"+window.lang.return( "download_e_title", { ucfirst: true } )+"</div>\
            <div class='e_desc'>"+window.lang.return( "download_e_desc", { ucfirst: true } )+"</div>\
          </div>");
          return;
        }

        var list_htmls = [];
        for( var i=list.length-1; i>=0; i-- ){
          var item = list[i];
          if ( !item.data ) continue;
          var item_html = "<div data-key='"+item.key+"' class='file sta_"+item.state+" cover_"+(item.data.cover?"yes":"no")+" sub_title_"+(item.data.sub_title?"yes":"no")+" key_"+item.key+" '>";
            item_html += "<div class='state'>\
              <div class='pending'><span class='mdi mdi-reload'></span><span class='text'>Pending</span></div>\
              <div class='paused'><span class='mdi mdi-pause-circle-outline'></span><span class='text'>Paused</span></div>\
              <div class='downloading'><span class='mdi mdi-tray-arrow-down'></span><span class='text'>Downloading</span></div>\
              <div class='done'><span class='mdi mdi-check-circle-outline'></span><span class='text'>Done</span></div>\
              <div class='failed'><span class='mdi mdi-alert-circle-outline'></span><span class='text'>Failed</span></div>\
            </div>";
            if ( item.data.cover )
            item_html += "<div class='cover_w'><div class='cover' style='background-image:url(\""+item.data.cover+"\")'></div></div>";
            item_html += "<div class='titles'><div class='main'>"+item.data.title+"</div><div class='second'>"+item.data.sub_title+"</div></div>";
            item_html += "<div class='size'>"+window._g.humanFileSize(item.data.size)+"</div>";
            item_html += "<div class='progress_wrapper'>\
              <div class='progress_e' style='width:"+(item.data.percentage_dled?item.data.percentage_dled:0)+"%'></div>\
              <div class='progress_t'>"+ (item.data.percentage_dled?item.data.percentage_dled:0) +"%</div>\
            </div>";
            item_html += "<div class='buttons'>\
              <div class='start'><span class='mdi mdi-download'></span><span class='text'>Start</span></div>\
              <div class='pause'><span class='mdi mdi-pause'></span><span class='text'>Pause</span></div>\
              <div class='cancel'><span class='mdi mdi-close'></span><span class='text'>Cancel</span></div>\
              <div class='delete'><span class='mdi mdi-delete'></span><span class='text'>Delete</span></div>\
            </div>";
          item_html += "</div>";
          list_htmls.push( item_html );
        }

        promise.resolve( list_htmls.join("") );

      } );

      promise.done( function( html ){

        var target = false;
        if ( $args.target === undefined ){
          if ( $("body").hasClass("offline_mode") ){
            target = ".bof_offline_page .file_list";
          } else {
            target = "#download_drop .list";
          }
        } else {
          target = $args.target;
        }

        if ( target )
        $(document).find( target ).html( html );

      } );

      return promise;

    },
  },
  db: {

    order: {
      get: function(){
        var dl_db = window.bof_offline.db._get();
        var promise = $.Deferred();
        dl_db.getItem( "_bof_dl_list" ).then( item => {
          promise.resolve( item );
        } );
        return promise;
      },
      set: function(newOrder){
        var dl_db = window.bof_offline.db._get();
        var promise = $.Deferred();
        dl_db.setItem( "_bof_dl_list", newOrder ).then( item => {
          promise.resolve( item );
        } );
        return promise;
      }
    },
    _get: function(){
      return localforage.createInstance({
        name: "downloads"
      });
    },
    insert: function( dl_code, data, file_list ){

      var dl_db = window.bof_offline.db._get();
      var promise = $.Deferred();

      /*var nonExistencePromise = $.Deferred();
      dl_db.keys().then( function( keys ){
        if ( keys.includes( dl_code ) )
        nonExistencePromise.reject();
        else
        nonExistencePromise.resolve();
      } );

      nonExistencePromise.done(function(){
      }).fail(function(){
        promise.reject();
      });*/

      var orderPromise = $.Deferred();
      window.bof_offline.db.order.get().done( function( order_list ){

        order_list = order_list ? order_list : [];
        order_list.push( dl_code );

        window.bof_offline.db.order.set( order_list ).done(function(){

          dl_db.getItem( dl_code ).then( function( Item ){
            Item = Item ? Item : {};
            Item.data = data;
            Item.files = file_list;
            dl_db.setItem( dl_code, Item ).then( function(){
              promise.resolve();
              window.bof_offline.count();
            } );
          } );

        });

      } );

      return promise;

    },
    get: function( dl_code ){

      var dl_db = window.bof_offline.db._get();
      var promise = $.Deferred();

      dl_db.getItem( dl_code ).then( item => {

        if ( !item.state )
        item.state = "pending";

        if ( item.state == "downloading" ){
          if ( window.bof_offline_cli.running_cache.item ? window.bof_offline_cli.running_cache.item.key != dl_code : true )
          item.state = "pending";
        }

        item.key = dl_code;
        item.files_pure = [];
        item.untouched_links = [];

        if ( item.files ){
          for ( var i=0; i<item.files.length; i++ ){

            var item_full_address = item.files[i];
            var __u = new URL( item_full_address );
            var item_pure_address = __u.origin + __u.pathname;

            item.files_pure.push( item_pure_address );

            if ( item.cached ? !item.cached.includes( item_pure_address ) && !item.cached.includes( decodeURI( item_pure_address ) ) : true ){

              __u.searchParams.delete("bof_offline");
              __u.searchParams.delete("bof_offline_download");
              __u.searchParams.delete("bof_offline_hls");
              var _dq = __u.searchParams.toString();

              item.untouched_links.push(
                item_pure_address +
                ( item_pure_address.includes("?") ? "&" : "?" ) + "bof_offline=" + dl_code + "&bof_offline_download=yes" +
                ( item_pure_address.includes(".key") || item_pure_address.includes(".m3u8") || item_pure_address.includes("/slice_") ? "&bof_offline_hls=yes" : "" ) +
                ( _dq ? "&" + _dq : "" )
              );

            }

          }
        }

        promise.resolve({
          key: dl_code,
          item: item
        })

      } );

      return promise;

    },
    update: function( dl_code, newItem ){

      var dl_db = window.bof_offline.db._get();
      return dl_db.setItem( dl_code, newItem )

    },
    list: function(){

      var dl_db = window.bof_offline.db._get();
      var promise = $.Deferred();
      var list = {};

      var list_promises = [];

      window.bof_offline.db.order.get().done( function( order_list ){

        dl_db.keys().then( function( keys ){

          var keys_ordered = [];

          if ( order_list ){
            for ( var z=0; z<order_list.length; z++ ){
              if ( keys.includes( order_list[z] ) ){
                list_promises.push(
                  window.bof_offline.db.get( order_list[z] )
                );
                keys_ordered.push( order_list[z] );
              }
            }
          }

          var unorder_list = $.grep(keys, function(value) {
            return $.inArray(value, keys_ordered.concat(["_bof_dl_list"])) === -1;
          });

          if ( unorder_list ? unorder_list.length : false ){
            for ( var z=0; z<unorder_list.length; z++ ){
              list_promises.unshift(
                window.bof_offline.db.get( unorder_list[z] )
              );
            }
          }

          if ( list_promises ){
            $.when.apply( $, list_promises ).done(function(){

              var _r = Array.prototype.slice.call( arguments, 0 );
              var _l = {};

              if ( _r ){
                for ( var i=0; i<_r.length; i++ ){
                  var _i = _r[i];
                  _l[ _i.key ] = _i.item
                }
              }

              promise.resolve( _l );

            });
          }

        } )


      } );

      return promise;

    },
    count: function(){

      var dl_db = window.bof_offline.db._get();
      var promise = $.Deferred();
      var _c = 0;

      dl_db.keys().then( keys => {

        if ( !keys )
        promise.resolve( 0 );

        else {
          for ( var z=0; z<keys.length; z++ ){
            if ( keys[z] != "_bof_dl_list" )
            _c = _c + 1;
          }
        }

        promise.resolve( _c );
      } );

      return promise;

    },
    get_objects: function( type, hash ){

      var dl_db = window.bof_offline.db._get();
      var promise = $.Deferred();
      var code = type + "-" + hash;

      dl_db.keys().then( keys => {

        var keys_check_promises = [];
        for ( var i=0; i<keys.length; i++ ){
          var key = keys[i];
          if ( key.endsWith( code ) ){

            var keys_check_promise = $.Deferred();
            keys_check_promises.push( keys_check_promise )
            window.bof_offline.db.get( key ).then( item_w => {

              var item = item_w.item;

              if ( item.state == "done" && promise.state() == "pending" ){
                promise.resolve( item );
              }

              keys_check_promise.resolve();

            } );

          }
        }

        if ( keys_check_promises.length ){
          $.when.apply( $, keys_check_promises ).done( function(){
            if ( promise.state() == "pending" )
            promise.reject("no_done_key");
          } );
        }
        else {
          promise.reject("no_related_key");
        }

      } );

      return promise;

    },
    get_first_pending: function(){

      var promise = $.Deferred();
      window.bof_offline.db.list().done( list => {

        if ( !list ){
          promise.reject();
          return;
        }

        for ( var i=0; i<Object.values(list).length; i++ ){
          var item = Object.values(list)[i];
          if ( item.state == "pending" ){

            promise.resolve( item );
            return;

          }
        }

        promise.reject();

      } );
      return promise;

    },
    delete: function( dl_code ){

      var dl_db = window.bof_offline.db._get();
      dl_db.removeItem( dl_code ).then( function(){
        window.bof_offline.count();
      } );
      var new_order_list = [];
      window.bof_offline.db.order.get().done( function( order_list ){
        if ( order_list ){
          for ( var z=0; z<order_list.length; z++ ){
            var _key = order_list[z];
            if ( _key != dl_code )
            new_order_list.push( order_list[z] );
          }
        }
        window.bof_offline.db.order.set( new_order_list );
        if( !new_order_list.length )
        window.bof_dropdown.close();
      } );

    }

  },
  mode: {
    on: function(){

      if ( window.m2_queue.ui.isOpen() ){
        window.m2_queue.ui.destroy()
      }

      window.ui.body.addClass( "offline_mode", true );
      window.ui.body.removeClass( "noParts", true );
      window.bof_offline.ui.list( {
        target: false
      } ).done( html => {
        $("body").append("<div class='bof_offline_page'>\
          <div class='off_title'>"+window.lang.return( "downloads", { ucfirst: true } )+"</div>\
          <div class='file_list'>"+html+"</div>\
          <div class='off_pop'>\
            <span class='mdi mdi-wifi-off'></span>\
            <div class='off_pop_title'>"+window.lang.return( "your_offline", { ucfirst: true } )+"</div>\
            <div class='off_pop_desc'>"+window.lang.return( "your_offline_desc", { ucfirst: true } )+"</div>\
            <div class='off_pop_btn btn bn' onClick='$(document).find(\".bof_offline_page\").toggleClass(\"inied\")'>"+window.lang.return( "your_offline_btn", { ucfirst: true } )+"</div>\
          </div>\
        </div>");
      } );

    },
    off: function(){

      window.app.becli.alert( true, window.lang.return( "your_online", { ucfirst: true } ) );

      window.ui.body.removeClass( "offline_mode", true );
      $(document).find(".bof_offline_page").remove();

      if ( $("body").hasClass("offline_start") ){
        window.ui.body.removeClass( "offline_start", true );
        window.ui.link.navigate( "/" );
      }

    }
  },

}
