"use strict";

window.bof_gtranslate_admin_js = {

  set: function(){
    $(".settings_wrapper").on("becli_reload_after",function(){
      window.bof_content_setting.reloadAfter = false;
    });
    $(".settings_wrapper").on("becli_done",function(e,sta,data,$args){
      if ( sta ){

        var _d = data.inputs;
        $(document).find(".page_g_translate #setting_form .row .col:nth-child(2)").css("display","block");

        if ( _d.has_more ){
          $(document).find(".page_g_translate #setting_form .row .col:nth-child(2) .setting_group").append( "<div class='log'>"+_d.done_job_data+"</div>" );
          $(document).find(".settings_wrapper #save_button.t2").text("Waiting 10 sec before next query ....");
          setTimeout( function(){
            $(document).find(".settings_wrapper #save_button.t2").text("Waiting 10 sec before next query ....");
          },150);
          setTimeout( function(){
            $(document).find(".settings_wrapper #save_button.t2").text("Loading");
            $(document).find("body.page_g_translate .settings_wrapper #save_button.t2").click();
          },10000);
        }
        else {
          $(document).find(".page_g_translate #setting_form .row .col:nth-child(2) .setting_group").append( "<div class='log'>Nothing to translate!</div>" );
          $(document).find(".settings_wrapper #save_button.t2").remove();
        }

        window.bof_content_setting.masonry();

      }
    });
  },

  unset: function(){
    window.bof_content_setting.reloadAfter = true;
    $(".settings_wrapper").off("becli_reload_after");
    $(".settings_wrapper").off("becli_done");
  }

}
