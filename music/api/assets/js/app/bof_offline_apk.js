"use strict";



window.bof_offline_apk = {

  cache: {},
  set: function( item ){


    var uid = Date.now().toString() + Math.random().toString(36).substring(2, 10);

    window.bof_offline_apk.cache.item = item;
    window.bof_offline_apk.cache.promise = $.Deferred();
    window.bof_offline_apk.cache.sta = "paused";
    window.bof_offline_apk.cache.dl_tries = {};
    window.bof_offline_apk.cache.id = uid + "";

  },
  start: function(){

    window.bof_offline_apk.cache.sta = "downloading";
    window.bof_offline_apk.download( true );

  },
  download: function( $start ){

    if ( window.bof_offline_apk.cache.sta != "downloading" )
    return;

    window.bof_offline_cli.getRunning().then( item => {

      //console.log( item );

      if ( item.untouched_links ? item.untouched_links.length && item.untouched_links[0] : false ){

        if ( $start )
        window.bof_offline_cli.updateRunning.state( "downloading" );

        window.bof_offline_apk.download_a_part( window.bof_offline_apk.cache.id, item.untouched_links[0] );

      }

      else {

        window.bof_offline_cli.updateRunning.state( "done" );

      }

    } );

  },
  download_a_part: function (uniqueId, $link) {

    if (Object.keys(window.bof_offline_apk.cache.dl_tries).includes($link)) {
      window.bof_offline_apk.cache.dl_tries[$link] = window.bof_offline_apk.cache.dl_tries[$link] + 1;
      if (window.bof_offline_apk.cache.dl_tries[$link] > 3) {
        window.bof_offline_cli.updateRunning.state("paused");
        return;
      }
    } else {
      window.bof_offline_apk.cache.dl_tries[$link] = 1;
    }

    return window.apk.offline.download_a_part( uniqueId, $link );

  },
  delete: function( $item ){

    window.bof.log("bof_offline_apk: delete");

  },
  deleteBofClient: function(){

    window.bof.log("bof_offline_apk: deleteBofClient");

  },
  deleteAll: function(){

    window.bof.log("bof_offline_apk: deleteAll");

  },
  pause: function(){

    window.bof.log("bof_offline_apk: pause");
    window.bof_offline_cli.updateRunning.state( "paused" );
    window.bof_offline_apk.cache.sta = "paused";
    // window.bof_offline_apk.cache.controller.abort();

  },

}

window.bof_offline_apk.getFileSize = function (fileUrl) {
  const deferred = $.Deferred();

  // Resolve the local file system URL
  window.resolveLocalFileSystemURL(
    fileUrl,
    (fileEntry) => {
      // Get the file metadata
      fileEntry.getMetadata(
        (metadata) => {
          deferred.resolve(metadata.size); // Resolve with file size
        },
        (error) => {
          deferred.reject(`Failed to get metadata: ${error.code}`); // Reject on metadata error
        }
      );
    },
    (error) => {
      deferred.reject(`Failed to resolve file URL: ${error.code}`); // Reject on URL resolution error
    }
  );

  return deferred.promise();
}

window.bof_offline_apk.newFolder = function(folderName) {
  const directory = cordova.file.dataDirectory; // Example: App's data directory
  window.resolveLocalFileSystemURL(directory, (dirEntry) => {
      dirEntry.getDirectory(
          folderName,
          { create: true, exclusive: false }, // Create if it doesn't exist
          (subDirEntry) => {
              console.log(`Folder '${folderName}' created or already exists at: ${subDirEntry.nativeURL}`);
          },
          (error) => {
              console.error(`Error creating folder '${folderName}': ${error.code}`);
          }
      );
  }, (error) => {
      console.error(`Error accessing directory: ${error.code}`);
  });
  
}