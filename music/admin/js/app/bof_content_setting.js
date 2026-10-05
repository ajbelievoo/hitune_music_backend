"use strict";

window.bof_content_setting = {

  reloadAfter: true,
  displaying: function(){

    var p = $.Deferred();
    window.app._extension( "masonry" ).done(function(){
      p.resolve();
    });
    return p;

  },

  ready: function(){

    window.bof_content_setting.masonry();
    $(document).on( "click", ".settings_wrapper #save_button", function(e){

      var saveBtn = $(this);
      if ( saveBtn.attr("disabled") )
      return;

      $(".settings_wrapper").trigger("becli_reload_after",[]);

      window.app.ui.becli.exe(
        "button",
        {
          dom: $(document).find( ".settings_wrapper #save_button" ),
        },
        {
          endpoint: window.ui.page.curr().args.becli[0].endpoint + "?bof=submitting&" + window.ui.page.curr().args.urlData.url.query_s,
          post: $("#setting_form").serializeArray(),
          reload_after: window.bof_content_setting.reloadAfter,
          c_callback: function( sta, data, $args ){
            if ( !sta )
            window.bof_content_setting.masonry();
            $(".settings_wrapper").trigger("becli_done",[sta,data,$args]);
          }
        }
      );

    } );
    $(document).on( "input", ".settings_wrapper input", function(){
      window.bof_content_setting.masonry();
    } )
    $(document).on( "click", "#openai_key_test", function(e){

      window.bof_modal.create({
        class: "no_groups",
        title: "Testing OpenAI Key",
        content: "<div id='openai_test_res'><p>Starting the test ....</p></div>"
      });

      window.becli.exe({
        endpoint: "openai_test",
        post: {
          key: $(document).find("input[name$=openai_key]").val(),
        },
        callBack: function( sta, data ){
          for ( var i=0; i<data.messages.length; i++ ){
            $(document).find("#openai_test_res").append("<p>"+data.messages[i]+"</p>");
          }
        }
      })

    } );
    $(document).on( "bofInput_change", function(e,i){
      window.bof_content_setting.masonry();
    } )

    if ( window.ui.page.curr().data.becli.setting.exe_display_rules ? window.ui.page.curr().data.becli.setting.exe_display_rules : false ){
      window.bof_content_setting.exe_display_rules();
      $(document).on( "change", ".setting_group .bof_input", function(e){
        window.bof_content_setting.exe_display_rules();
      });
    }

  },

  unloading: function(){

    $(document).off( "click", "#openai_key_test" );
    $(document).off( "click", ".settings_wrapper #save_button" );
    $(document).off( "change", ".settings_wrapper input" );
    $(document).off( "change", ".setting_group .bof_input" );
    $(document).off( "bofInput_change", function(e,i){
      window.bof_content_setting.masonry();
    } )

  },

  masonry: function(){
    $('.settings_wrapper .row').masonry({
      itemSelector: '.col',
    });
  },

  exe_display_rules: function(){
    if ( window.ui.page.curr().data.becli.setting.groups ? window.ui.page.curr().data.becli.setting.groups : false ){
      var __inputs = {}
      for ( var i=0; i<Object.keys(window.ui.page.curr().data.becli.setting.groups).length; i++ ){
        var _k = Object.keys( window.ui.page.curr().data.becli.setting.groups )[i];
        for ( var z=0; z<Object.keys(window.ui.page.curr().data.becli.setting.groups[_k].inputs).length; z++ ){
          var _kik = Object.keys(window.ui.page.curr().data.becli.setting.groups[_k].inputs)[z];
          var _ki = window.ui.page.curr().data.becli.setting.groups[_k].inputs[_kik]
          __inputs[_kik] = _ki
        }
      }
      window.bof_input.exe_display_rules( __inputs );
      window.bof_content_setting.masonry();
    }
  }


}
