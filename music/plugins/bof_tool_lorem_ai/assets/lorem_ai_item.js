"use strict";

window.bof_lorem_ai_item = {

  displaying: function(){
  },
  set: function(){

    $(document).find(".settings_wrapper").html("<div class='lorem'>\
      <div class='logs'>\
        <div class='log'>Executing, wait please ....</div>\
      </div>\
    </div>");

    var start = window._g.milli_time();

    window.becli.exe({
      endpoint: "lorem_ai_item_runner",
      post: {
        object: window.ui.page.curr().args.urlData.url.match[0],
        item: window.ui.page.curr().args.urlData.url.match[1]
      },
      callBack: function( sta, data ){
        if ( sta && data.logs ? data.logs.length : false ){
          for ( var z=0; z<data.logs.length; z++ ){
            $(document).find(".lorem .logs").append("<div class='log'>"+data.logs[z]+"</div>")
          }
        }
        if ( !sta ){
          if ( window._g.passed_time(start) > 30*1000 ){
            $(document).find(".lorem .logs").append("<div class='log'>Request timed out! This does not mean that process is finished ( failure or success ). Please check back on your item ( by clicking on back button ) and only retry after few minutes if your required data is not added</div>")
            $(document).find(".lorem .logs").append("<div class='log'>Running Lorem-AI through web-server for lots of items is not recommended. We recommend using cronjobs instead</div>")
          }
        }
      }
    })

  },
  unset: function(){
  }

}
