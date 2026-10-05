"use strict";

window.localhost = false;
window.config = {

  localhost: false,
  production: $_bof_config.production,
  version: $_bof_config.version,
  endpoint_address: $_bof_config.endpoint_address,
  bo_bocc_address: "http://localhost/bocc/",
  platform: "web",
  cache_prefix: "dm_ad_",

  web: {
    _formatize_url: function( urlString ){
      return urlString.substr( urlString.length - 1 ) == "/" ? urlString : urlString + "/"
    },
    address: $_bof_config.web_address,
  }

};

if ( window.config.web.address ){
  window.config.web.address = window.config.web._formatize_url( window.config.web.address );
  window.config.web.prefix  = new URL( window.config.web.address ).pathname;
}
