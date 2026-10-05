"use strict";

window.bof_gateway_kkiapay = {

  setup: function( data ){

    $.getScript( "https://cdn.kkiapay.me/k.js" ).done(function( script, textStatus ) {
  		openKkiapayWidget( data.data )
  	});

  }

}
