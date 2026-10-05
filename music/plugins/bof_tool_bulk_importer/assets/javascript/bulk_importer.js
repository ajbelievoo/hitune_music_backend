"use strict";

window.bulk_importer = {

  held_shift: false,
  held_ctrl: false,
  selected: {
    cache: [],
    last_noshift: null,
    handle: function( id ){

    },
    exists: function( id ){
      return window.bulk_importer.selected.cache.includes( id );
    },
    add: function( id ){
      var file = window.ui.page.data.becli.content.files[id];
      if ( file.mp3 )
      window.bulk_importer.selected.cache.push( id );
    },
    remove: function( id ){
      window.bulk_importer.selected.cache.splice( window.bulk_importer.selected.cache.indexOf( id ), 1 );
    },
    reset: function(){
      window.bulk_importer.selected.cache = [];
    },
    hook: function(){
      $(document).find("tr.selected").removeClass("selected");
      if ( window.bulk_importer.selected.cache.length ){

        var files = [];
        var files_ids = [];
        for( var z=0; z<window.bulk_importer.selected.cache.length; z++ ){
          var b = window.bulk_importer.selected.cache[z];
          var fileData = window.ui.page.data.becli.content.files[b];
          files.push( fileData );
          files_ids.push( fileData.ID );
          $(document).find("tr#f_"+b).addClass("selected");
        }

        var tags = {
          title: files[0]["tag_title"],
          artist: files[0]["tag_artist"],
          album: files[0]["tag_album"],
          album_artist: files[0]["tag_album_artist"],
          album_order: files[0]["tag_album_order"],
          album_type: files[0]["tag_album_type"],
          time_release: files[0]["tag_time_release"],
          genres: files[0]["tag_genres"],
          tags: files[0]["tag_tags"],
          langs: files[0]["tag_langs"],
          ft_artists: files[0]["tag_ft_artists"],
          cd_order: files[0]["tag_cd_order"],
          cover: files[0]["cover"]
        };

        for ( var z=0; z<Object.keys(tags).length; z++ ){
          var _k = Object.keys(tags)[z];
          var isMutural = files.every( item => item[_k=="cover"?"cover":("tag_"+_k)] === tags[_k] );
          if ( !isMutural ) tags[ _k ] = null;
          $(document).find(".bof_input[name="+_k+"]").val( tags[ _k ] );
        }

        $(document).find("input[name=ids]").val(files_ids.join(","))
        $(document).find("#bulk_importer").removeClass("nonSelected").addClass("someSelected");
        $(document).find("#bulk_importer #selectedCount").text( files.length + " item" + ( files.length > 1 ? "s" : "" ) + " selected" )
        $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igC input:checked").click();
        $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI #upload_cover").removeClass("done failed doing");

        window.bof_input.hook();

      }
      else {
        $(document).find("#bulk_importer").addClass("nonSelected").removeClass("someSelected");
      }
    },
    unselectAll: function(){
      window.bulk_importer.selected.cache = [];
      window.bulk_importer.selected.hook();
    },
  },

  bof_input_callback: function(e,o){
    var ok = false;
    if ( o.sta && o.data ? o.data.success : false ){
      if ( o.data.newFile ){
        ok = o.data.newFile
      }
    }
    if ( ok ){
      $(document).find("input[name=cover]").val( ok );
      $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI #upload_cover").removeClass("doing failed").addClass("done");
      if ( !$(document).find("input[name=cover_active]").is(":checked") )
      $(document).find("input[name=cover_active]").click();
    } else {
      $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI #upload_cover").removeClass("doing done").addClass("failed");
    }
  },

  displaying: function(){
    return window.app._extension( "daterangepicker", true );
  },
  set: function(){

    $(document).on("click","#bulk_importer #bulk_importer_items table tbody tr",function(e){

      var fid = parseInt($(this).attr("id").substr(2));
      var shift = window.bulk_importer.held_shift;
      var ctrl = window.bulk_importer.held_ctrl;

      if ( $(this).hasClass("mp3_0") )
      return;

      if ( !shift )
      window.bulk_importer.selected.last_noshift = fid;

      if ( shift && window.bulk_importer.selected.last_noshift !== null ){
        let _s = fid > window.bulk_importer.selected.last_noshift ? window.bulk_importer.selected.last_noshift+1 : fid;
        let _b = fid < window.bulk_importer.selected.last_noshift ? window.bulk_importer.selected.last_noshift-1 : fid;
        for ( var _i=_s; _i<=_b; _i++ ){
          if ( !window.bulk_importer.selected.exists( _i ) )
          window.bulk_importer.selected.add( _i );
        }
      }
      else if ( ctrl ){
        if ( !window.bulk_importer.selected.exists( fid ) )
        window.bulk_importer.selected.add( fid );
        else
        window.bulk_importer.selected.remove( fid );
      }
      else {
        if ( window.bulk_importer.selected.cache.length ){
          if ( window.bulk_importer.selected.cache.length == 1 ){
            if ( window.bulk_importer.selected.exists( fid ) ){
              window.bulk_importer.selected.reset();
            } else {
              window.bulk_importer.selected.reset();
              window.bulk_importer.selected.add( fid );
            }
          } else {
            window.bulk_importer.selected.reset();
            window.bulk_importer.selected.add( fid );
          }
        } else {
          window.bulk_importer.selected.add( fid );
        }
      }

      window.bulk_importer.selected.hook();

    });
    $(document).on("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._bts .btn.save",function(e){

      if ( $(this).hasClass("loading") )
      return;

      window.becli.exe({
        endpoint: "bulk_importer_save",
        post: $(document).find("#bulk_importer form").serialize(),
        callBack: function( sta, data ){
          $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._bts .btn.save").removeClass("loading");
          if ( sta ? data.tags : false ){
            for( var z=0; z<Object.keys(data.tags).length; z++ ){
              var _k = Object.keys(data.tags)[z];
              var _v = data.tags[ _k ];
              if ( _k == "cover" ){
                $(document).find("#bulk_importer #bulk_importer_items table tbody tr.selected td._bi_cover span").css("background-image","url("+$_bof_config.web_address.replace("admin/","")+"/files/bulk_importer_covers/"+_v+".png)")
              } else {
                $(document).find("#bulk_importer #bulk_importer_items table tbody tr.selected td._bi_"+_k).attr("title",_v).text(_v);
              }
              for( var zz=0; zz<window.bulk_importer.selected.cache.length; zz++ ){
                var b = window.bulk_importer.selected.cache[zz];
                window.ui.page.data.becli.content.files[b][_k=="cover"?"cover":("tag_"+_k)] = _v;
              }
            }
          }
        },
        callBefore: function(){
          $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._bts .btn.save").addClass("loading");
        }
      })

    })
    $(document).on("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._bts .btn.import",function(e){

      if ( $(this).hasClass("loading") )
      return;

      $(this).addClass("loading").text("Wait ...");

      window.becli.exe({
        endpoint: "bulk_importer_mark",
        post: {
          ids: $(document).find("#bulk_importer form input[name=ids]").val(),
          type: $(document).find("input[name=import_as]:checked").val()
        },
        callBack: function( sta, data ){

          if ( sta ){
            if ( data.oks.length ){
              for ( var z=0; z<data.oks.length; z++ ){
                var ok = data.oks[z];
                $(document).find("tr.id_"+ok+" td._bi_sta").attr("title","Processing").removeClass("sta_1").addClass("sta_2");
              }

            }

            window.bof_modal.create({
              title: data.oks.length + " file(s) marked to be imported",
              class: "bulk_import_modal",
              content: data.stats
            });

          }

          $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._bts .btn.import").removeClass("loading").text("Import")
        },
      })

    })
    $(document).on("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igC input",function(e){
      var enabled = $(this).is(":checked");
      $(this).parents("._ig").find("._igI .bof_input").attr("readonly",enabled?false:true);
    });
    $(document).on("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI .bof_input[readonly]",function(e){
      $(this).parents("._ig").find("._igC input").click();
    });
    $(document).on("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI #upload_cover",function(e){
      $(document).find("#bulk_importer_upload_cover .mask").click();
      $(document).find("#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI #upload_cover").addClass("doing");
    });
    $(document).on("click","#bulk_importer #bulk_importer_sidebar .highlight_toggler div",function(e){

      var action = $(this).hasClass("him") ? "open_menu" : "open_bi";

      if ( !$(this).hasClass("active") ){
        $(document).find("#bulk_importer #bulk_importer_sidebar .highlight_toggler div.active").removeClass("active");
        $(this).addClass("active");
      }

      if ( action == "open_menu" ){
        window.ui.body.addClass( "hide_highlights_peek" );
      } else {
        window.ui.body.removeClass( "hide_highlights_peek" );
      }

    });
    $(document).on("click","#sidebar .links ul li a",function(e){
      if ( !$("body").hasClass("hide_highlights_peek") ) $("body").addClass("hide_highlights_peek");
      $(document).find("#bulk_importer #bulk_importer_sidebar .highlight_toggler div.active").removeClass("active");
      $(document).find("#bulk_importer #bulk_importer_sidebar .highlight_toggler div.him").addClass("active");
    });
    $(document).on("click","#resync_btn",function(e){

      window.becli.exe({
        endpoint: "bulk_importer_sync",
        callBack: function( sta, data ){
          if ( sta ){
            $(document).find("#bulk_importer").removeClass("state_0").addClass("state_1");
          }
        },
        callBefore: function(){
          $(document).find("#resync_btn").text("Wait ....");
        }
      })

    });

    $(document).on("keydown",function(e){
      if ( e.keyCode == 16 )
      window.bulk_importer.held_shift = true;
      if ( e.keyCode == 17 )
      window.bulk_importer.held_ctrl = true;
    });
    $(document).on("keyup",function(e){
      if ( e.keyCode == 16 )
      window.bulk_importer.held_shift = false;
      if ( e.keyCode == 17 )
      window.bulk_importer.held_ctrl = false;
    });

    $(document).find("#bulk_importer").addClass("state_"+window.ui.page.data.becli.content.state);
    $(document).find("#bulk_importer #bulk_importer_items table thead tr td a[href='bulk_importer?sort="+(window.ui.page.data.becli.content.sort)+"']").parents("td").addClass("sorted");
    $(document).find("#bulk_importer_upload_cover .file_input_wrapper").attr("data-endpoint","bulk_importer_upload_cover");

    $(document).on("bof_input_callback",window.bulk_importer.bof_input_callback);

  },
  unset: function(){

    $(document).off("click","#bulk_importer #bulk_importer_items table tbody tr");
    $(document).off("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._bts .btn.save");
    $(document).off("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._bts .btn.import");
    $(document).off("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igC input");
    $(document).off("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI .bof_input[readonly]");
    $(document).off("click","#bulk_importer #bulk_importer_sidebar #bulk_importer_editor ._i ._ig ._igI #upload_cover");
    $(document).off("click","#bulk_importer #bulk_importer_sidebar .highlight_toggler div");
    $(document).off("click","#sidebar .links ul li a",function(e){
      if ( !$("body").hasClass("hide_highlights_peek") ) $("body").addClass("hide_highlights_peek");
      $(document).find("#bulk_importer #bulk_importer_sidebar .highlight_toggler div.active").removeClass("active");
      $(document).find("#bulk_importer #bulk_importer_sidebar .highlight_toggler div.him").addClass("active");
    });
    $(document).off("click","#resync_btn");

    $(document).off("keydown",function(e){
      if ( e.keyCode == 16 )
      window.bulk_importer.held_shift = true;
      if ( e.keyCode == 17 )
      window.bulk_importer.held_ctrl = true;
    });
    $(document).off("keyup",function(e){
      if ( e.keyCode == 16 )
      window.bulk_importer.held_shift = false;
      if ( e.keyCode == 17 )
      window.bulk_importer.held_ctrl = false;
    });

    $(document).off("bof_input_callback",window.bulk_importer.bof_input_callback);

  }

}
