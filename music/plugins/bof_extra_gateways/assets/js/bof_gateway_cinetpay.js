"use strict";

window.bof_gateway_cinetpay = {

  setup: function( data ){

    $.getScript( "https://cdn.cinetpay.com/seamless/main.js" ).done(function( script, textStatus ) {

      CinetPay.setConfig(data.data);
      CinetPay.getCheckout(data.transaction);

      CinetPay.waitResponse(function(data) {
        console.log("data");
        console.log(data);
        window.location.href= data.redirect_url
      });

      CinetPay.onError(function(data) {
        console.log("error");
        console.log(data);
        window.app.becli.alert( false, data.description );
        setTimeout( function(){
          window.location.reload();
        }, 1000 );
      });

    });

  }

}
