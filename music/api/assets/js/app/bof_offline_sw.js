"use strict";

window.bof_offline_sw = {

  cache: {},
  set: function( item ){

    window.bof_offline_sw.cache.item = item;
    window.bof_offline_sw.cache.promise = $.Deferred();
    window.bof_offline_sw.cache.sta = "paused";
    window.bof_offline_sw.cache.controller = null;
    window.bof_offline_sw.cache.dl_tries = {};

  },
  start: function(){

    window.bof_offline_sw.cache.sta = "downloading";
    window.bof_offline_sw.download( true );

  },
  download: function( $start ){

    if ( window.bof_offline_sw.cache.sta != "downloading" )
    return;

    window.bof_offline_cli.getRunning().then( item => {

      if ( item.untouched_links ? item.untouched_links.length && item.untouched_links[0] : false ){

        if ( $start )
        window.bof_offline_cli.updateRunning.state( "downloading" );

        window.bof_offline_sw.download_a_part( item.untouched_links[0] );

      }

      else {

        window.bof_offline_cli.updateRunning.state( "done" );

      }

    } );

  },
  download_a_part: function( $link ){

    if ( Object.keys( window.bof_offline_sw.cache.dl_tries ).includes( $link ) ){
      window.bof_offline_sw.cache.dl_tries[ $link ] = window.bof_offline_sw.cache.dl_tries[ $link ] + 1;
      if ( window.bof_offline_sw.cache.dl_tries[ $link ] > 5 ){
        window.bof_offline_cli.updateRunning.state( "paused" );
        return;
      }
    } else {
      window.bof_offline_sw.cache.dl_tries[ $link ] = 1;
    }

    window.bof_offline_sw.cache.controller = new AbortController();
    fetch( $link, {
      signal: window.bof_offline_sw.cache.controller.signal,
      mode: "no-cors"
    } )
    .then( response => {
      window.bof_offline_cli.updateRunning.dled( response.headers.get("content-length") );
      window.bof_offline_sw.download();
    } )

  },
  delete: function( $item ){

    window.bof.log("bof_offline_sw: delete");
    try {
      navigator.serviceWorker.controller.postMessage({
        action: "clean",
        url: $item.files_pure
      });
    } catch( Error ){
      console.log("sw.delete failed");
      console.error( Error );
    }

  },
  deleteBofClient: function(){

    window.bof.log("bof_offline_sw: deleteBofClient");
    if ('serviceWorker' in navigator) {
      try {
        navigator.serviceWorker.controller.postMessage({
          action: "cleanBofClient",
        });
      } catch( Error ){
        console.log("sw.deleteBofClient failed");
        console.error( Error );
      }
    }

  },
  deleteAll: function(){

    window.bof.log("bof_offline_sw: deleteAll");
    if ('serviceWorker' in navigator) {
      try {
        navigator.serviceWorker.controller.postMessage({
          action: "cleanAll",
        });
      } catch( Error ){
        console.log("sw.deleteAll failed");
        console.error( Error );
      }
    }

  },
  pause: function(){

    window.bof.log("bof_offline_sw: pause");
    window.bof_offline_cli.updateRunning.state( "paused" );
    window.bof_offline_sw.cache.sta = "paused";
    window.bof_offline_sw.cache.controller.abort();

  },

}
