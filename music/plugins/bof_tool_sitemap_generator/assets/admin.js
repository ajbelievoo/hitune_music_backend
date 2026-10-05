"use strict";

window.bof_sitemap_generator = {

  exeOnReload: false,
  state_data: function( $val ){
    if ( $val == 1 ) return {
      title: "Queued",
      detail: "Witing for cronjob-runner to pick this job up! Then Sitemap Generator will create the sitemap(s) for you. It could take only a few minutes or hours depending on the number of your items. You can leave the page. You can't change Sitemap Generator's setting right now. We are not checking for state-change in background, do manual refresh",
      can_cancel: true,
      can_save: false,
      can_exe: false,
      val: 1
    };
    if ( $val == 2 ) return {
      title: "In-Progress",
      detail: "Sitemap Generator has been executed by cronjob and it is making sitemap(s) for you! You can't cancel this process. You can leave the page. You can't change Sitemap Generator's setting right now",
      can_cancel: false,
      can_save: false,
      can_exe: false,
      val: 2
    };
    if ( $val == 3 ) return {
      title: "Executing",
      detail: "Please wait while Sitemap Generator execute this command",
      can_cancel: false,
      can_save: false,
      can_exe: false,
      val: 3
    };
    return {
      title: "Offline",
      detail: "Sitemap Generator is not doing anything",
      can_cancel: false,
      can_save: true,
      can_exe: true,
      val: 0
    };
  },
  set: function(){

    $(document).find(".settings_wrapper #save_button.t2").after('<div class="btn btn-primary t2" id="exe_button">Make a Sitemap</div>');
    $(document).on("click","body.page_sitemap_generator .settings_wrapper #exe_button",function(e){
      window.bof_sitemap_generator.exeOnReload = true;
      $(document).find("body.page_sitemap_generator .settings_wrapper #exe_button").text("Wait, Saving first .....");
      $(document).find("body.page_sitemap_generator .settings_wrapper #save_button").click();
    });
    $(document).on("click","body.page_sitemap_generator #cancel_button",function(e){
      window.becli.exe({
        endpoint: "sitemap_generator_cancel",
        post: {
          state: 3
        },
        callBack: function( sta, data ){
          if ( !sta )
          alert( "failed: " + data.messages[0] );
          window.ui.page.reload()
        }
      });
    });

    var state = window.ui.page.data.becli.setting.state;
    var state_data = window.bof_sitemap_generator.state_data( state );

    if ( window.bof_sitemap_generator.exeOnReload === true && state_data.can_exe === true ){

      state = 3;
      state_data = window.bof_sitemap_generator.state_data( state );
      window.bof_sitemap_generator.exeOnReload = false;

      window.becli.exe({
        endpoint: "sitemap_generator_execute",
        post: {
          state: 3
        },
        callBack: function( sta, data ){
          if ( !sta )
          alert( "failed: " + data.messages[0] );
          window.ui.page.reload()
        }
      });

    }

    $("#main .content").prepend( "<div class='state state_i"+state_data.val+"'><div class='sT'>"+state_data.title+"</div><div class='sD'>"+state_data.detail+"</div><div class='btn btn-primary' id='cancel_button'>Cancel</div></div>" )

    if ( !state_data.can_save )
    $(document).find("body.page_sitemap_generator .settings_wrapper #save_button").remove();

    if ( !state_data.can_exe )
    $(document).find("body.page_sitemap_generator .settings_wrapper #exe_button").remove();

    if ( !state_data.can_cancel )
    $(document).find("body.page_sitemap_generator #cancel_button").remove();

  },
  unset: function(){
    $(document).off("click","body.page_sitemap_generator .settings_wrapper #exe_button");
    $(document).off("click","body.page_sitemap_generator #cancel_button");
  }

}
