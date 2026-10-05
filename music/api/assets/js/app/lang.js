"use strict";

window.lang = {

  cache: {},
  code: null,
  get: function(){

    var promise = $.Deferred();
    window.becli.exe({
      endpoint: "client_translations" + ( window.bof._safeGet("app.config.user.language") ? "?lang=" + window.app.config.user.language : "" ),
      liquid: true,
      callBack: function( sta, data ){
        if ( sta ){
          window.lang.cache = data["translations"]
          window.lang.code = data["code"]
          window.ui.body.removeClass("dir_rtl",true).removeClass("dir_ltr",true).addClass("dir_"+data.direction,true);
          promise.resolve( data["translations"] );
        }
        else {
          promise.reject();
        }
      }
    });
    return promise;

  },
  return: function( $hook, $args ){

    var cache = window.lang ? ( window.lang.cache ? window.lang.cache : {} ) : {};

    var output = $hook + "?";

    if ( cache[ $hook ] ){

      output = cache[ $hook ];

      $args = $args ? $args : {};

      if ( $args.ucfirst )
      output = output.charAt(0).toUpperCase() + output.slice(1);

    }

    return output;

  }

};
