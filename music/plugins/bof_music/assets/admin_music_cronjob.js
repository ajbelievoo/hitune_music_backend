"use strict";

window.admin_music_cronjob = {

  set: function(){

    $(document).on("click","#spotify_browse_button",function(){

      var type = $(document).find(".bof_input[name='object_type']").val();
      window.bof_modal.create({
        title: "Browse Spotify",
        class: "browse_spotify",
        inputs: {
          query: {
            label: "Query",
            tip: type == "user_lists" ? "Enter user's id or username" : ( type == "cat_lists" ? "Enter a country alpha code-2. Example: US, GB" : "Enter a keyword to search for " + type ),
            input: {
              name: "browse_spotify_query",
              type: "text",
              placeholder: type == "user_lists" ? "Username...." : ( type == "cat_lists" ? "US" : "Query..." ),
            },
            group: "a"
          }
        },
      });

    });
    $(document).on("input",".bof_input[name='browse_spotify_query']",function(e){

      if ( !$(document).find("#spotify_browse_result").length ){
        $(document).find(".modal.browse_spotify .inputs").after("<div id='spotify_browse_result'><span class='loading'>Loading ....</span></div>")
      }

      window.becli.exe({
        endpoint: "spotify_browse_endpoint",
        ID: "spotify_browse_endpoint",
        post: {
          query: $(this).val(),
          type: $(document).find(".bof_input[name='object_type']").val()
        },
        callBack: function( sta, data ){

          if ( sta ? !data.result : false ){
            $(document).find("#spotify_broswse_result").html("<span class='nada'>Found nothing</span>")
          } else if ( sta ) {
            $(document).find("#spotify_broswse_result").html(" ");
            var _html = "";
            for ( var z=0; z<Object.keys(data.result).length; z++ ){
              var sid = Object.keys(data.result)[z];
              var _data = data.result[ sid ];
              _html += "<div class='spotify_browse_item "+(_data.image?"has_cover":"")+"' data-spotify-id=\""+_data.id+"\">";
              _html += "<span class='_title'>"+_data.name+"</span>";
              if ( _data.image ) _html += "<span class='_cover_holder' style='background-image:url(\""+_data.image+"\")'></span>";
              _html += "</div>";
            }
            $(document).find("#spotify_browse_result").html( _html );
          }

        }
      })
    });
    $(document).on("click","div#spotify_browse_result .spotify_browse_item",function(e){

      var spotify_id = $(this).data("spotify-id");
      $(document).find("textarea[name=api_ids]").val( $(document).find("textarea[name=api_ids]").val() + ($(document).find("textarea[name=api_ids]").val()?"\n":"") + spotify_id );
      window.bof_modal.close();

    });

  },

  unset: function(){
    $(document).off("click","#spotify_browse_button");
    $(document).off("input",".bof_input[name='browse_spotify_query']");
    $(document).off("click","div#spotify_browse_result .spotify_browse_item");
  }

}
