
window.cache = {

  _mirrorSessCookie: function( name, value ){

    if ( name.substr( 0, 5 ) !== "sess_" )
    return;

    if ( !/(\.|^)hitune\.in$/.test( window.location.hostname ) )
    return;

    var ck = "hitune_" + name + "=" + encodeURIComponent( value || "" );
    if ( value && document.cookie.indexOf( ck ) !== -1 )
    return;

    document.cookie = ck
      + "; domain=.hitune.in; path=/; SameSite=Lax; Secure"
      + ( value ? "; max-age=2592000" : "; max-age=0" );

  },

  set: function( name, value, acceptZero ){

    if ( !value )
    return window.cache.remove( name );

    window.cache._mirrorSessCookie( name, value );

    //if ( config.platform == "web" )
    //return Cookies.set( name , value )

    return window.localStorage.setItem( window.config.cache_prefix + name, value )


  },
  get: function( name, defaultValue ){

    defaultValue = defaultValue ? defaultValue : false;
    var output = false;

    // if ( config.platform == "web" )
    // output = Cookies.get( name )

    // else
    output = window.localStorage.getItem( window.config.cache_prefix + name );

    if ( output ){

      window.cache._mirrorSessCookie( name, output );
      return output;

    }
    return defaultValue;

  },
  remove: function( name ){

    window.cache._mirrorSessCookie( name, false );

    // if ( config.platform == "web" )
    // return Cookies.remove( name )
    return window.localStorage.removeItem( window.config.cache_prefix + name );

  },
  removeAll: function(){

    return window.localStorage.clear();

  }

};
