"use strict";

window.bof_gateway_flutterwave = {

  setup: function( data ){

    $.getScript( "https://checkout.flutterwave.com/v3.js" ).done(function( script, textStatus ) {
      FlutterwaveCheckout( data.data );
    });

  }

}
