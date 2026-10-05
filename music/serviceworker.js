importScripts('https://cdnjs.cloudflare.com/ajax/libs/localforage/1.10.0/localforage.min.js');

// Take control immediately so deployments reach open clients without delay
self.addEventListener("install", function(e){ self.skipWaiting(); });
self.addEventListener("activate", function(e){ e.waitUntil( clients.claim() ); });

var cache_db = localforage.createInstance({
  name: "cache"
});

var dl_db = localforage.createInstance({
  name: "downloads"
});

var cacher = {

  extensions: {
    js: {
      lifetime: 7*24*60*60,
    },
    css: {
      lifetime: 7*24*60*60,
    },
    html: {
      lifetime: 7*24*60*60,
    },
    woff2: {
      lifetime: 90*24*60*60,
    },
    png: {
      lifetime: 3*24*60*60,
    },
    gif: {
      lifetime: 3*24*60*60,
    },
    jpg: {
      lifetime: 3*24*60*60,
    },
    jpeg: {
      lifetime: 3*24*60*60,
    },
  },

  log: function( $text ){
    // console.log( $text );
  },
  parseURL: function( urlAddress, request ){

    var url = new URL( urlAddress );
    var urlPure = url.protocol + "//"  + url.host + url.pathname;
    var urlExtension = urlAddress.split(/[#?]/)[0].split('.').pop().trim();

    // classic dl
    var bof_offline = null;
    if ( url.searchParams.get( "bof_offline" ) || request.headers.get("bof_offline") ){

      var offline_code = url.searchParams.get( "bof_offline" ) ? url.searchParams.get( "bof_offline" ) : request.headers.get( "bof_offline" );
      var offline_code_match = offline_code.match("(.*)?\-(.*)?\-(.*)?")

      if ( offline_code_match ? offline_code_match[1] && offline_code_match[2] && offline_code_match[3] : false ){

        var offline_file_type = url.searchParams.get( "bof_offline_hls" ) || request.headers.get("bof_offline_hls") ? "hls" : "solid";
        var offline_file_type_abs = offline_file_type;
        if ( urlExtension == "m3u8" ) offline_file_type_abs = "hls";
        var offline_file_id = null;

        if ( offline_file_type_abs == "solid" ){
          if ( request.headers.get( "range" ) ){
            var range = request.headers.get( "range" );
            var matchRange = range.match("bytes=(.*)?-");
            if ( matchRange )
            offline_file_id = matchRange[1];
          }
          else {
            offline_file_id = "one_piece";
          }
        }
        else {
          if ( urlExtension == "m3u8" ){
            offline_file_id = "map";
          }
          else if ( urlExtension == "key" ){
            offline_file_id = "key";
          }
          else if ( urlExtension == "mp3" ){
            var matchSliceName = urlAddress.match("slice_(.*)?.mp3");
            if ( matchSliceName )
            offline_file_id = matchSliceName[1];
          }
        }

        bof_offline = {
          code: offline_code,
          file_type: offline_file_type,
          file_type_abs: offline_file_type_abs,
          file_id: offline_file_id,
          code_full: offline_code + "-" + offline_file_id,
          hash: offline_code_match[1],
          object_code: offline_code_match[2] + "-" + offline_code_match[3],
          object_type: offline_code_match[2],
          object_hash: offline_code_match[3],
          protected: url.searchParams.get("protected") == "yes",
          download: url.searchParams.get("bof_offline_download") == "yes"
        };

      }

    }

    return {
      full: urlAddress,
      pure: urlPure,
      extension: urlExtension,
      bof_version: url.searchParams.get( "bof_version" ),
      bof_cache: url.searchParams.get( "bof_cache" ),
      bof_offline: bof_offline,
    }

  },
  now: function(){
    return new Date().getTime() / 1000
  },
  removeOld: function(){

    caches.open("bof").then( cache => {
      cache.keys().then( Requests => {

        if ( !Requests ? true : !Requests.length )
        return;

        Requests.forEach( Request => {

          // var request_url_parsed = cacher.parseURL( Request.url );
          cache_db.getItem( Request.url ).then( value => {

            if ( value ){
              if ( value.expire < cacher.now() ){
                cacher.log( "TIMER: Removed " + Request.url + " -> too old" );
                cache.delete( Request );
                cache_db.removeItem( Request.url );
              }
            }
            else {
              // cache.delete( Request );
              // cacher.log( "TIMER: Removed" + Request.url + " -> not listed" );
            }

          })

        } )

      } )
    } );

  },
  handleRequest: function( event ){

    var urlAddress = event.request.url;
    var url = cacher.parseURL( urlAddress, event.request );

    if ( url.bof_offline ){
      urlAddress = url.pure;
    }

    // Page navigations: network-first so new deployments/versions reach users
    // immediately; fall back to cache only when offline.
    if ( event.request.mode === "navigate" ){
      event.respondWith(
        fetch( event.request ).then( response => {
          if ( response && response.ok ){
            try {
              var clone = response.clone();
              caches.open("bof").then( cache => cache.put( urlAddress, clone ) );
            } catch( err ){}
          }
          return response;
        }).catch( () => caches.match( urlAddress ) )
      );
      return;
    }

    if (
      !url.bof_offline &&
      !url.bof_cache &&
      !Object.keys( cacher.extensions ).includes( url.extension ) &&
      url.extension != "ts" && url.extension != "key" &&
      !urlAddress.startsWith( "https://fonts.g" ) &&
      !urlAddress.startsWith( "https://i.scdn.co" ) &&
      !urlAddress.startsWith( "https://cdn.jsdelivr.net" ) &&
      event.request.mode !== "navigate"
    ){
      // console.log( urlAddress + " RETURN::1" );
      return;
    }

    if ( urlAddress.includes( "/admin/" ) || urlAddress.includes( "login_social_ini" ) ){
      // console.log( urlAddress + " RETURN::2" );
      return;
    }
    
    if ( urlAddress.includes( "/api/be/" ) || urlAddress.includes( "api/be/" ) ){
      return;
    }
    
    if ( urlAddress.includes( "/api/" ) && !urlAddress.includes( "/api/assets/" ) ){
      return;
    }
    
    if ( urlAddress.includes( "/api/assets/theme/pages/" ) ){
      return;
    }

    if ( urlAddress.includes( "bof_sw_ignore_me" ) || urlAddress.includes( "googlesyndication.com" ) ){
      // console.log( urlAddress + " RETURN::3" );
      return;
    }

    if (
      Object.keys( cacher.extensions ).includes( url.extension ) &&
      url.bof_version == "dont_cache" &&
      !urlAddress.includes( "api/assets/js/app/config.js?bof_version=dont_cache" ) &&
      event.request.mode !== "navigate"
    ){
      // console.log( urlAddress + " RETURN::4" );
      return;
    }

    event.respondWith(
      caches.match( urlAddress )
      .then( cachedResponse => {

        if ( !cachedResponse )
        return cachedResponse;

        var promise = new Promise( function( resolve, reject ){

          var dropEntry = function( reason ){
            cacher.log( "CHECK: Removed " + urlAddress + " -> " + reason );
            caches.open( "bof" ).then( cache => cache.delete( urlAddress ) );
            cache_db.removeItem( urlAddress );
            cachedResponse = null;
          };

          // Versioned assets: wait for the stored-version check before resolving —
          // previously resolve() fired before getItem() so stale same-URL entries
          // were served forever (e.g. css edited under an unchanged bof_version).
          if ( url.bof_version && !urlAddress.includes( "api/assets/js/app/config.js?bof_version=dont_cache" ) ){
            cache_db.getItem( urlAddress ).then( value => {
              if ( !value ) return resolve( cachedResponse );
              if ( value.version && url.bof_version && value.version != url.bof_version )
              return dropEntry( "Version" ), resolve( cachedResponse );
              if ( cacher.now() > value.expire )
              return dropEntry( "Expired" ), resolve( cachedResponse );
              resolve( cachedResponse );
            } ).catch( () => resolve( cachedResponse ) );
            return;
          }

          // remove expired
          if ( url.bof_cache ){
            cache_db.getItem( urlAddress ).then( value => {
              if ( value ? cacher.now() > value.expire : false )
              dropEntry( "Expired" );
              resolve( cachedResponse );
            } );
            return;
          }

          resolve( cachedResponse );

        } );

        return promise;

      } )
      .then( cachedResponse => {

        if ( !cachedResponse )
        return cachedResponse;

        if ( url.bof_offline ){
          dl_db.getItem( url.bof_offline.code ).then( Item => {

            Item = Item ? Item : {};
            Item.cached = Item.cached ? Item.cached : [];
            if ( !Item.cached.includes( decodeURI( urlAddress ) ) )
            Item.cached.push( decodeURI( urlAddress ) );

            dl_db.setItem( url.bof_offline.code, Item );

          } )
        }

        return cachedResponse;

      } )
      .then( cachedResponse => {

        if ( cachedResponse ){
          if (
            event.request.mode !== "navigate" &&
            !urlAddress.endsWith( "api/client_config" ) &&
            !urlAddress.endsWith( "api/client_translations" ) &&
            !urlAddress.includes( "api/assets/js/app/config.js?bof_version=dont_cache" ) ?
              true :
            !navigator.onLine
          ){
            cacher.log( "EXE: [Y] Already Cached: " + urlAddress + " -> [Y]" );
            return cachedResponse;
          }
        }

        cacher.log( "EXE: [N] Not Cached: " + urlAddress + " -> [N]" );

        if (
          event.request.headers.get("range") ||
          ( url.bof_offline && !url.bof_offline.download ) ||
          ( ( url.extension == "ts" || url.extension == "key" ) && !url.bof_offline )
        ){
          cacher.log( "EXE: " + urlAddress + " -> RawFetch" );
          return fetch( event.request );
        }

        var lifeTime = 5*24*60*60;
        if ( Object.keys( cacher.extensions ).includes( url.extension ) )
        lifeTime = cacher.extensions[ url.extension ].lifetime;
        else if ( url.bof_cache )
        lifeTime = url.bof_cache;
        else if ( url.bof_offline )
        lifeTime = 365*24*60*60;

        return caches.open("bof").then( cache => {
          return fetch( event.request ).then( response => {
            return cache.put( urlAddress, response.clone() ).then( () => {

              if ( url.bof_offline ){
                dl_db.getItem( url.bof_offline.code ).then( Item => {

                  Item = Item ? Item : {};
                  Item.cached = Item.cached ? Item.cached : [];
                  if ( !Item.cached.includes( decodeURI( urlAddress ) ) )
                  Item.cached.push( decodeURI( urlAddress ) );

                  dl_db.setItem( url.bof_offline.code, Item );

                } )
              }
              else {

                cache_db.setItem( urlAddress, {
                  extension: url.extension,
                  version: url.bof_version,
                  time: cacher.now(),
                  expire: parseInt( cacher.now() ) + parseInt( lifeTime ),
                  size: response.headers.get("content-length")
                } );

              }

              cacher.log( "EXE: [Y] Cached: " + urlAddress + " -> [Y]" );

              return response;

            } );
          } );
        } );

      } )
    );

  }

};

self.addEventListener( 'activate', event => {
  self.clients.claim()
  cacher.removeOld();
} );
self.addEventListener( 'fetch', event => {
  cacher.handleRequest( event );
} );
self.addEventListener( 'message', event => {

  if ( event.data.action == "cleanAll" ){

    caches.delete("bof");
    cache_db.clear();

  }
  else if ( event.data.action == "cleanBofClient" ){

    caches.open("bof").then( cache => {
      cache.keys().then( Requests => {
        Requests.forEach( Request => {
          // decodeURI( Request.url )
          if ( Request.url.includes( "api/bofClient" ) ){
            cache.delete( Request );
            cache_db.removeItem( Request.url );
          }
        } );
      } );
    } );

  }
  else if ( event.data.action == "clean" ){

    var url_to_delete = event.data.url;
    var urls_to_delete = typeof url_to_delete == "string" ? [ url_to_delete ] : url_to_delete;

    caches.open("bof").then( cache => {
      cache.keys().then( Requests => {
        Requests.forEach( Request => {
          if ( urls_to_delete.includes( Request.url ) || urls_to_delete.includes( decodeURI( Request.url ) ) ){
            cache.delete( Request );
          }
        } );
      } );
    } );

    for ( var i=0; i<urls_to_delete.length; i++ ){
      var urlToDelete = urls_to_delete[i];
      cache_db.removeItem( urlToDelete );
    }

  }

});
self.addEventListener( 'push', function(event) {
  if (event.data) {

    var push_string = event.data.text();
    var push_data = JSON.parse( push_string );
    var notification_args = {};

    if ( push_data.image ) notification_args.icon = push_data.image;
    if ( push_data.content ) notification_args.body = push_data.content;

    self.registration.showNotification( push_data.title , notification_args );

    var chapar_db = localforage.createInstance({
      name: "chapar"
    });

    chapar_db.setItem( "sw_new", "yes" );

  }
})
