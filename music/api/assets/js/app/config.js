"use strict";

window.localhost = false;
window.config = {

  localhost: false,
  production: $_bof_config.production,
  version: $_bof_config.version,
  endpoint_address: $_bof_config.endpoint_address,
  platform: "web",
  cache_prefix: "dm_",

  web: {
    _formatize_url: function( urlString ){
      return urlString.substr( urlString.length - 1 ) == "/" ? urlString : urlString + "/"
    },
    address: $_bof_config.web_address,
  },

  javas: function(){

    var general = $.Deferred();
    var muse = $.Deferred();

    var localForagePromise = false
    //if ( window.config.platform == "web" ){
    localForagePromise = window.bof._loadExtension({
      skipNameCheck: true,
      version: false,
      name: "localforage.min.js",
      path: "third/localforage.min.js",
      base: "bof_assets",
    });
    /*} else {
      localForagePromise = $.Deferred();
      localForagePromise.resolve();
    }*/

    var apkPromise = false;
    if (window.config.platform == "web") {
      apkPromise = $.Deferred();
      apkPromise.resolve();
    } else {
      /*window.bof._loadExtension({
        name: "apk",
        path: "app/" + ($_bof_config.production ? "minified/" : "") + "apk.js",
      });*/
      apkPromise = $.Deferred();
      apkPromise.resolve();
    }
  
    $.when(
      window.bof._loadExtension({
        name: "pageBuilder",
        path: "app/"+($_bof_config.production?"minified/":"")+"bof_pageBuilder.js",
      }),
      window.bof._loadExtension({
        name: "bof_modal",
        path: "app/"+($_bof_config.production?"minified/":"")+"bof_modal.js",
      }),
      window.bof._loadExtension({
        name: "bof_input",
        path: "app/"+($_bof_config.production?"minified/":"")+"bof_input.js",
      }),
      window.bof._loadExtension({
        name: "bof_dropdown",
        path: "app/"+($_bof_config.production?"minified/":"")+"bof_dropdown.js",
      }),
      localForagePromise,
      apkPromise
    ).done(function(){
      general.resolve();
    }).fail(function(){
      general.reject();
    })

    if ( window.app.config.no_muse ? window.app.config.no_muse === true : false )
    {
      muse.resolve();
    } 
    else 
    {

      var _promises = false
      if ( $_bof_config.production ){
        _promises = $.when(
          window.bof._loadExtension({
            name: "m2",
            path: "js/bof/bof_mini_muse.js",
            dir: "js",
            base: "bof_assets"
          }),
          window.bof._loadExtension({
            name: "bof_offline",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_offline.js",
          }),
          window.bof._loadExtension({
            name: "bof_offline_cli",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_offline_cli.js",
          }),
        )
      }
      else {
        _promises = $.when(
          window.bof._loadExtension({
            name: "m2",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2.js",
          }),
          window.bof._loadExtension({
            name: "m2c_audio",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_cli_audio.js",
            skipNameCheck: true,
          }),
          window.bof._loadExtension({
            name: "m2c_video",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_cli_video.js",
            skipNameCheck: true,
          }),
          window.bof._loadExtension({
            name: "m2c_youtube",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_cli_youtube.js",
            skipNameCheck: true,
          }),
          window.bof._loadExtension({
            name: "m2c_gcast",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_cli_gcast.js",
            skipNameCheck: true,
          }),
          window.bof._loadExtension({
            name: "m2c_soundcloud",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_cli_soundcloud.js",
            skipNameCheck: true,
          }),
          window.bof._loadExtension({
            name: "m2_focus",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_focus.js",
          }),
          window.bof._loadExtension({
            name: "m2_infinite",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_infinite.js",
          }),
          window.bof._loadExtension({
            name: "m2_cli",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_cli.js",
            skipNameCheck: true,
          }),
          window.bof._loadExtension({
            name: "m2_queue",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_queue.js",
          }),
          window.bof._loadExtension({
            name: "m2_source",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_source.js",
          }),
          window.bof._loadExtension({
            name: "m2_thingie",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_muse2_thingie.js",
          }),
          window.bof._loadExtension({
            name: "bof_offline",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_offline.js",
          }),
          window.bof._loadExtension({
            name: "bof_offline_cli",
            path: "app/"+($_bof_config.production?"minified/":"")+"bof_offline_cli.js",
          }),
        )
      }

      _promises.done(function(){
        muse.resolve();
      }).fail(function(){
        muse.reject();
      });

    }

    return $.when(
      muse,
      general
    )

  },

  events: {
    page_unloading: function(){

      window.pageBuilder.widget.item.closeMenu();
      if ( window.m2_queue )
      window.m2_queue.ui.destroy();
      window.app.ui.playHead.unset();
      window.bof_input.unhook();
      window.bof_dropdown.close();
      window.app.actions.search.unhook();
      window.app.ui.fSidebar.close()

    },
    page_ready: function(){

      if ( window.m2_focus )
      window.m2_focus.ui.hook()
      window.pageBuilder.widget.item.listen();
      window.bof_input.hook();
      window.app.ads.hook();

      if ( $(document).find("#search_query").length ){
        window.app.actions.search.ini();
      }

      window.app.ui.actions.hideSliderArrows();

      var curPageData = window.ui.page.curr().data
      if ( window.bof._safeGet( "becli.single.data.head_play_title", curPageData ) ){
        window.app.ui.playHead.set( curPageData.becli.single.data )
      }

      window.app.ui.fSidebar.close()

      var hasPreHistory = false;
      if ( Object.keys( window.ui.history._states ).length ){
        if ( Object.keys( window.ui.history._states ).indexOf( window.ui.history._state ) > 0 ){
          hasPreHistory = true;
        }
      }

      if ( hasPreHistory ){
        window.ui.body.addClass("has_history", true);
      } else {
        window.ui.body.removeClass("has_history", true);
      }

    },
    page_rendering: function(){
    },
  }

};

if ( window.config.web.address ){
  window.config.web.address = window.config.web._formatize_url( window.config.web.address );
  window.config.web.prefix  = new URL( window.config.web.address ).pathname;
}
