"use strict";

window.lorem_play = {

  displaying: function(){
  },
  set: function(){

    var vw = $(document).find("#lorem_play #view").width();
    var vh = $(document).find("#lorem_play #view").height();
    var _s = vw > vh ? vh : vw;
    if ( _s > 1024 ) _s = 1024;

    $(document).find("#lorem_play #view div").css("width",_s+"px").css("height",_s+"px")

    $(document).on("click","#fake_sidebar .highlight_toggler div", function(e){

      var action = $(this).hasClass("him") ? "open_menu" : "open_bi";

      if ( !$(this).hasClass("active") ){
        $(document).find("#fake_sidebar .highlight_toggler div.active").removeClass("active");
        $(this).addClass("active");
      }

      if ( action == "open_menu" ){
        window.ui.body.addClass( "hide_highlights_peek" );
      } else {
        window.ui.body.removeClass( "hide_highlights_peek" );
      }

    });
    $(document).on("change","#fake_sidebar input",function(){
      window.bof_input.exe_display_rules(  window.ui.page.curr().data.becli.play.inputs );
    });
    $(document).on("click","#fake_sidebar .btn-primary",function(){

      if($(this).hasClass("loading"))
      return;

      window.becli.exe({
        endpoint: "lorem_ai_playground?exe=yes",
        post: $(document).find("#fake_sidebar form").serialize(),
        timeout: 120000,
        callBack: function( sta, data ){
          $(document).find("#lorem_play #fake_sidebar .btn-primary").removeClass("done failed loading");
          if ( sta ){
            $(document).find("#lorem_play #fake_sidebar .btn-primary").addClass("done");
            $(document).find("#lorem_play #view div").css("background-image","url("+data.url+")")
          } else {
            $(document).find("#lorem_play #fake_sidebar .btn-primary").addClass("failed");
            $(document).find("#lorem_play #fake_sidebar ._err").addClass("show").text( data.messages[0] );
          }
        },
        callBefore: function(){
          $(document).find("#lorem_play #fake_sidebar ._err.show").removeClass("show");
          $(document).find("#lorem_play #fake_sidebar .btn-primary").removeClass("done failed").addClass("loading");
        }
      })

    });


    window.bof_input.exe_display_rules(  window.ui.page.curr().data.becli.play.inputs );

  },
  unset: function(){

    $(document).off("click","#fake_sidebar .highlight_toggler div");
    $(document).off("change","#fake_sidebar input");
    $(document).off("click","#fake_sidebar .btn-primary");

  }

}
