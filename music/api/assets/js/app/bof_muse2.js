"use strict";

window.m2 = {

  log: function( $from, $text, $level ){

    $level = $level ? $level : 4

    var $fromID = null;
    var $fromCl = null;
    if ( $from.includes(":") ){
      $fromID = $from.split(":")[0]
      $fromCl = $from.split(":")[1]
    } else {
      $fromID = "----------"
      $fromCl = $from
    }

    $fromID = $fromID.padEnd(11)
    $fromCl = $fromCl.padEnd(14)

    var timeRelativeToStart = ( new Date().getTime() - window.bof.startTime ) / 1000;
    timeRelativeToStart = ( Math.round( timeRelativeToStart * 100 ) / 100 ).toString()
    timeRelativeToStart = timeRelativeToStart.padEnd(6)

    console.log.apply( $, [
      `%c${timeRelativeToStart} %c${$level} %c${$fromID} %c${$fromCl} %c${$text}`,
      'color: #999; font-size:7pt',
      'color: #aaa; font-size:7pt',
      'color: #aaa; font-size:7pt',
      'color: orange; font-weight:600',
      'color: ' + ( $level == 1 ? "red" : "#ddd" )
    ] );

  },
  app_ready: function(){

    this.app_ready = true

  },
  all_parts_ready: function(){

    if ( this.all_parts_ready_cached === true )
    return true;

    this.all_parts_ready_cached = true;
    this.log( "m2", "All parts ready", 3 )

    var museSetting = false;
    if ( !window.m2.isEmbed() ){
      museSetting = window.app.config.setting.muse
    }
    else {
      museSetting = {
        "muse_hide": true,
        "muse_hide_yt": true,
        "muse_rec_thres": 10,
        "queue_hide_infinite": true,
        "queue_hide": true,
        "queue_disable_auto": true,
        "queue_hide_lyrics": 1,
        "ad_offset": 100000,
        "ad_interval": 100000,
        "ad_skippability": true,
        "ad_skippability_threshold": 1,
        "queue_save": 0
      }
    }

    var museSettingKeys = {
      ad_interval: "thingie_interval",
      ad_offset: "thingie_offset",
      ad_skippability: "thingie_skipable",
      ad_skippability_threshold: "thingie_skipable_threshold",
      muse_hide: "muse_hide",
      muse_hide_yt: "muse_hide_yt",
      muse_rec_thres: "record_threshold",
      queue_disable_auto: "queue_disable_auto",
      queue_hide_lyrics: "queue_hide_lyrics",
      queue_hide_infinite: "queue_hide_infinite",
      queue_hide: "queue_hide",
      queue_save: "queue_save"
    }
    Object.keys( museSettingKeys ).forEach( key => {
      window.m2.config.set( museSettingKeys[key], museSetting[key] ? museSetting[key] : null )
    } );

    window.m2.config.set( "safari", navigator.userAgent.includes("Safari") )
    window.m2.config.set( "iphone", navigator.platform == "iPhone" )

    function iOS() {
      if ( window.apk )
        return false;
      return [
        'iPad Simulator',
        'iPhone Simulator',
        'iPod Simulator',
        'iPad',
        'iPhone',
        'iPod'
      ].includes(navigator.platform)
      // iPad on iOS 13 detection
      || (navigator.userAgent.includes("Mac") && "ontouchend" in document)
    }

    window.m2.config.set( "hotfix", iOS() ) 

    window.m2_focus.ui.add_cli_htmls()
    window.m2_focus.setting.load()
    window.m2.user.listen()
    if ( !window.m2.config.get("hotfix") ){
      window.m2_thingie.check()
    }
    window.m2.load()

  },
  isEmbed: function(){
    return window.bof_embed ? true : false
  },
  load: function(){
      
      // window.m2_queue.load()
      // Clear queue on first load to fix persistent old songs issue
      if ( !window.m2_queue.loaded_from_storage ){
        window.m2_queue.clear();
        window.m2_queue.loaded_from_storage = true;
      }

      // Don't auto-open queue on initial page load - prevent empty queue overlay
      var active = window.m2_queue.active.get();
      if ( !active ){
        window.m2.log( "m2", "No active queue item - skipping initial load", 3 );
        return;
      }

      var __offset = 0;

    try {
      var _ot = window.m2_queue.active.get().data.ot
      var _oh = window.m2_queue.active.get().data.hash
      var _coth = window.cache.get( "muse_offset_h" );
      if ( _coth == _ot + _oh ){
        var _coffset = window.cache.get( "muse_offset_p" );
        if ( _coffset )
          __offset = _coffset;
      }
    } catch( Error ){
      console.log( Error )
    }

    window.m2_queue.active.setFocus({
      autoplay: false,
      preload: true,
      offset: __offset
    })

  },
  close: function(){

    window.m2_focus.setStatus("paused");
    window.m2_queue.ui.destroy()
    window.m2_focus.cancelPreSetObject();
    window.m2_focus.cancelPreSet();
    window.m2_focus.cancelPreCli();
    $(document).find("#player").remove();
    window.ui.body.removeClass("muse_active", true);
    window.cache.remove("muse_que");
    window.cache.remove("muse_que_i");
    if ( window.m2.closerTimer )
    clearTimeout( window.m2.closerTimer )
    window.m2.closeTimeRemaining = 0
    if (window.m2_focus.skiper) {
      clearTimeout(window.m2_focus.skiper)
    }
    try {
      window.m2_thingie.playing = false
      window.m2_thingie.cache = false
      clearTimeout( window.m2_thingie.seeker )
      window.m2_thingie.seeker = null
    } catch( err ){}
    try {
      window.m2_thingie.cli.cleanup()
    } catch( err ){}

  }

}

window.m2.user = {

  listening: false,
  log: function ($text, $level) {
    window.m2.log("m2user", $text, $level)
  },
  listen: function () {

    if (this.listening)
      return;

    this.listening = true;

    if (window.app?window.app.config.mobile:false) {
      $(document).on( "click", "body.mobile #player .data_wrapper", function(){
        window.m2_focus.ui.openAnime()
      } )
    }

    $(document).on("click", ".control.play", function () {
      window.m2.user.control.playToggle();
    });
    var removeVolBar = function (e) {

      if ($(e.target).hasClass("volume_control") || $(e.target).parents(".volume_control").length)
        return;

      $(document).find(".button.volume_control .vol_bar").remove();
      $(document).off("click", removeVolBar);

    }
    $(document).on("click", ".button.volume_control", function () {
      if (window.app?window.app.config.mobile:false) {
        window.m2.user.control.muteToggle();
      } else {
        if ($(document).find(".button.volume_control .vol_bar").length === 0) {

          var _vh = window.m2_focus.setting.get("volume");
          if (window.m2_focus.setting.get("muted"))
            _vh = 0;

          $(document).find(".button.volume_control").append("<div class='vol_bar'><input type='range' id='volume_c' min='0' max='100'><div class='masks'><span class='maskB' style='width: " + _vh + "%'></span><span class='mask'></span></div></div>");
          $(document).on("click", removeVolBar);

        }
      }
    });
    var removeMuseSetting = function (e) {

      if ($(e.target).hasClass("_sw") || $(e.target).parents("._sw").length)
        return;

      if ($(e.target).hasClass("muse_setting_handler") || $(e.target).parents(".muse_setting_handler").length)
        return;

      $(document).find(".button.muse_setting_handler .setting_wrapper").remove();
      $(document).off("click", removeMuseSetting);

    }
    $(document).on("click", ".button.muse_setting_handler", function () {

      if ($(document).find(".button.muse_setting_handler .setting_wrapper").length === 0) {

        var _sp = window.m2_focus.setting.get("speed");
        var _vh = window.m2_focus.setting.get("volume");
        if (window.m2_focus.setting.get("muted"))
          _vh = 0;

        var activeSource = false
        if ( window.m2_queue.active.get() ? ( window.m2_queue.active.get().types ? ( window.m2_queue.active.get().types.sources ? window.m2_queue.active.get().types.sources : false ) : false ) : false){
          Object.keys(window.m2_queue.active.get().types.sources).forEach(function (sType) {
            window.m2_queue.active.get().types.sources[sType].sources.forEach(function (s) {
              if (s.active) {
                s.type = sType
                activeSource = s
              }
            });
          });
        }
        else {
          activeSource = {
            type: window.lang.return("offline")
          }
        }
        
        var gCastBtn = "<div class='_sw gcast'><div class='_swi'><span class='mdi mdi-cast'></span></div><div class='_swl'>"+ window.lang.return('gcast', { ucfirst: true }) + "</div><div class='_swo'><span class='text'>" + ( window.m2_focus.gcast.isConnected() ? window.m2_focus.gcast.deviceName() : window.lang.return('connect', { ucfirst: true }) ) + "</span><span class='mdi mdi-chevron-right'></span></div></div>"

        if ( /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream )
          gCastBtn = "";

        $(document).find(".button.muse_setting_handler").append("<div class='setting_wrapper'>\
          <div class='_tw active'>\
            <div class='_sw que_shuffle'><div class='_swi'><span class='mdi mdi-shuffle'></span></div><div class='_swl'>"+ window.lang.return('shuffle', { ucfirst: true }) + "</div></div>\
            <div class='_sw que_repeat'><div class='_swi'><span class='mdi mdi-repeat'></span></div><div class='_swl'>"+ window.lang.return('repeat', { ucfirst: true }) + "</div><div class='_swo'><div class='_mask'></div></div></div>\
            <div class='_sw infinite_control'><div class='_swi'><span class='mdi mdi-infinity'></span></div><div class='_swl'>"+ window.lang.return('infinite', { ucfirst: true }) + "</div><div class='_swo'><div class='_mask'></div></div></div>\
            <div class='_sw close'><div class='_swi'><span class='mdi mdi-close-circle-outline'></span></div><div class='_swl'>"+ window.lang.return('close', { ucfirst: true }) + "</div><div class='_swo'><span class='closer_coutdown'></span><span class='mdi mdi-chevron-right'></span></div></div>\
            <div class='_sw speed'><div class='_swi'><span class='mdi mdi-play-speed'></span></div><div class='_swl'>"+ window.lang.return('speed', { ucfirst: true }) + "</div><div class='_swo'><span class='num'>" + _sp + "x</span><span class='mdi mdi-chevron-right'></span></div></div>\
            <div class='_sw quality'><div class='_swi'><span class='mdi mdi-tune'></span></div><div class='_swl'>"+ window.lang.return('source', { ucfirst: true }) + "</div><div class='_swo'><span class='text'>" + activeSource.type + "</span><span class='mdi mdi-chevron-right'></span></div></div>\
            <div class='_sw vol'><div class='_swi'><span class='mdi mdi-volume-high'></span></div><div class='_swl'>"+ window.lang.return('volume', { ucfirst: true }) + "</div><div class='_swo'><span class='num'>" + _vh + "%</span><span class='mdi mdi-chevron-right'></span></div></div>\
            <div class='_sw fullscreen'><div class='_swi'><span class='mdi mdi-fullscreen'></span></div><div class='_swl'>"+ window.lang.return('fullscreen', { ucfirst: true }) + "</div></div>\
            "+gCastBtn+"\
          </div>\
        </div>");

        $(document).find(".button.muse_setting_handler .setting_wrapper").css(
          "height",
          $(document).find(".button.muse_setting_handler .setting_wrapper ._tw.active").height() + "px"
        )

        $(document).on("click", removeMuseSetting);

      }

    });
    $(document).on("click", "._sw.goBack", function () {

      $(document).find(".muse_setting_handler .setting_wrapper ._tw.onLeft").animate({ left: 0 }, 100)
      $(document).find(".muse_setting_handler .setting_wrapper ._tw.active").animate({ left: 250 }, 100, function () {
        $(document).find(".muse_setting_handler .setting_wrapper ._tw.active").remove()
        $(document).find(".muse_setting_handler .setting_wrapper ._tw.onLeft").removeClass("onLeft").addClass("active")
      })
      $(document).find(".button.muse_setting_handler .setting_wrapper").animate({
        height: $(document).find(".button.muse_setting_handler .setting_wrapper ._tw.onLeft").height()
      }, 100);

      var _sp = window.m2_focus.setting.get("speed");
      var _vh = window.m2_focus.setting.get("volume");
      if (window.m2_focus.setting.get("muted"))
        _vh = 0;


      $(document).find(".muse_setting_handler .setting_wrapper ._sw.vol ._swo .num").text(_vh + "%")
      $(document).find(".muse_setting_handler .setting_wrapper ._sw.speed ._swo .num").text(_sp + "x")

    });
    function moveSettingSW() {

      $(document).find(".muse_setting_handler .setting_wrapper ._tw.active").animate({ left: -250 }, 100)
      $(document).find(".muse_setting_handler .setting_wrapper ._tw.onRight").animate({ left: 0 }, 100, function () {
        $(document).find(".muse_setting_handler .setting_wrapper ._tw.active").removeClass("active").addClass("onLeft")
        $(document).find(".muse_setting_handler .setting_wrapper ._tw.onRight").removeClass("onRight").addClass("active")
      })

      $(document).find(".button.muse_setting_handler .setting_wrapper").animate({
        height: $(document).find(".button.muse_setting_handler .setting_wrapper ._tw.onRight").height()
      }, 100);

    }
    $(document).on("click", "._sw.close", function () {

      var _html = ""

      if (window.m2.closeTimeRemaining > 0) {
        _html += "<div class='_sw cancel'><div class='_swi'><span class='mdi mdi-close'></span></div><div class='_swl' >" + window.lang.return("cancel", { ucfirst: true }) + " <span style='font-size:90%; opacity: 0.9' class='closer_coutdown'>" + window.general.duration_hr(window.m2.closeTimeRemaining) + "</span></div></div>"
      }

      _html += "<div class='_sw' data-n='0'><div class='_swl' >" + window.lang.return("right_now", { ucfirst: true }) + "</div></div>"
      _html += "<div class='_sw' data-n='5'><div class='_swl' >5 " + window.lang.return("minutes") + "</div></div>"
      _html += "<div class='_sw' data-n='10'><div class='_swl' >10 " + window.lang.return("minutes") + "</div></div>"
      _html += "<div class='_sw' data-n='15'><div class='_swl' >15 " + window.lang.return("minutes") + "</div></div>"
      _html += "<div class='_sw' data-n='30'><div class='_swl' >30 " + window.lang.return("minutes") + "</div></div>"
      _html += "<div class='_sw' data-n='60'><div class='_swl' >1 " + window.lang.return("hour") + "</div></div>"
      _html += "<div class='_sw' data-n='120'><div class='_swl' >2 " + window.lang.return("hours") + "</div></div>"

      $(document).find(".setting_wrapper").append("<div class='_tw onRight closers'>\
        <div class='_sw goBack'><div class='_swi'><span class='mdi mdi-arrow-left'></span></div><div class='_swl'>"+ window.lang.return('back', { ucfirst: true }) + "</div></div>\
        "+ _html + "\
      </div>")

      moveSettingSW()

    });
    $(document).on("click", "._sw.speed", function () {

      var _sp = window.m2_focus.setting.get("speed");
      var _html = ""

      var speeds = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];

      speeds.forEach(function (speed) {
        _html += "<div class='_sw speedBar" + (speed == _sp ? " active" : "") + "' data-n='" + speed + "'><div class='_swl' >" + (speed == 1 ? window.lang.return('normal', { ucfirst: true }) : speed + "x") + "</div></div>"
      })

      $(document).find(".setting_wrapper").append("<div class='_tw onRight'>\
        <div class='_sw goBack'><div class='_swi'><span class='mdi mdi-arrow-left'></span></div><div class='_swl'>"+ window.lang.return('back', { ucfirst: true }) + "</div></div>\
        "+ _html + "\
      </div>")

      moveSettingSW()

    });
    $(document).on("click", "._sw.quality", function () {

      var _html = "";
      Object.keys(window.m2_queue.active.get().types.sources).forEach(function (sType) {
        window.m2_queue.active.get().types.sources[sType].sources.forEach(function (s) {
          _html = _html + "<div class='_sw quality_item" + (s.active ? " active" : "") + (s.locked ? " locked" : "") + "' data-type='" + sType + "' data-hook='" + s.hook + "' data-id='" + s.hash + "'>" + (s.locked ? "<div class='_swi'><span class='mdi mdi-lock'></span></div>" : "") + "<div class='_swl' >" + s.title + "</div></div>"
        });
      });

      $(document).find(".setting_wrapper").append("<div class='_tw onRight quality_list'>\
        <div class='_sw goBack'><div class='_swi'><span class='mdi mdi-arrow-left'></span></div><div class='_swl'>"+ window.lang.return('back', { ucfirst: true }) + "</div></div>\
        "+ _html + "\
      </div>")

      moveSettingSW()

    });
    $(document).on("click", ".closers ._sw", function () {

      if ($(this).hasClass("goBack"))
        return

      if ($(this).hasClass("cancel")) {
        if (window.m2.closerTimer)
          clearTimeout(window.m2.closerTimer)
        window.m2.closeTimeRemaining = 0
        $(document).find(".closer_coutdown").removeClass("active").text("")
        $(document).find(".muse_setting_handler .setting_wrapper ._sw.goBack").click()
        return
      }

      var closingTime = $(this).attr("data-n");
      window.m2.user.control.close(closingTime);
      $(document).find(".muse_setting_handler .setting_wrapper ._sw.goBack").click()

    })
    $(document).on("click", "._sw.vol", function () {

      var _vh = window.m2_focus.setting.get("volume");
      if (window.m2_focus.setting.get("muted"))
        _vh = 0;

      $(document).find(".setting_wrapper").append("<div class='_tw onRight'>\
        <div class='_sw goBack'><div class='_swi'><span class='mdi mdi-arrow-left'></span></div><div class='_swl'>Back</div></div>\
        <div class='vol_bar'><input type='range' id='volume_c' min='0' max='100'><div class='masks'><span class='maskB' style='width: "+ _vh + "%'></span><span class='mask'></span></div></div>\
      </div>")

      moveSettingSW()

    });
    $(document).on("click", "._sw.gcast", function () {

      var loadCastSenderJS = $.Deferred();
      var loadCastJs = $.Deferred()

      if ( window.bof.loaded_extensions.includes("cast_sender.js") || window.config.platform == 'android' ){
        loadCastSenderJS.resolve();
      }
      else {
        $(document).find(".muse_setting_handler .setting_wrapper ._sw.gcast ._swo .text").html( window.lang.return('loading',{ucfirst:true}) )
        window.bof._loadExtension({
          name: "cast_sender.js",
          path: "cast_sender.js?loadCastFramework=1",
          base: "https://www.gstatic.com/cv/js/sender/v1/",
          dir: "",
          skipNameCheck: true,
          version: false
        }).done(function () {
          loadCastSenderJS.resolve();
        }).fail(function () {
          loadCastSenderJS.reject("Loading cast.js failed");
        });
      }

      loadCastSenderJS.done(function(){

        if ( !window.m2_focus.gcast.isLoaded() ){
          $(document).find(".muse_setting_handler .setting_wrapper ._sw.gcast ._swo .text").html( window.lang.return('loading',{ucfirst:true}) )
          window.m2_focus.gcast.load( loadCastJs );
        } else {
          loadCastJs.resolve();
        }
        
        loadCastJs.done(function () {
  
          if ( window.m2_focus.gcast.isConnected() ){
  
            $(document).find(".setting_wrapper .gcast_w").remove();
            $(document).find(".setting_wrapper").append("<div class='_tw onRight gcast_w'>\
            <div class='_sw goBack'><div class='_swi'><span class='mdi mdi-arrow-left'></span></div><div class='_swl'>"+ window.lang.return('back', { ucfirst: true }) + "</div></div>\
            <div class='_sw _gcast_disconnect'><div class='_swi'><span class='mdi mdi-cast-off'></span></div><div class='_swl'>"+window.lang.return('disconnect',{ucfirst:true})+"</span></div></div>\
            </div>")

            moveSettingSW()
  
          } else {
            
            $(document).find(".muse_setting_handler .setting_wrapper ._sw.gcast ._swo .text").html( window.lang.return('connecting',{ucfirst:true}) )
            window.m2_focus.gcast.connect()
            .done(function(){

              $(document).find(".muse_setting_handler .setting_wrapper ._sw.gcast ._swo .text").html( window.m2_focus.gcast.deviceName() )
              window.m2_queue.active.setFocus()
              window.m2_queue.ui.previewFocus()

            })
            .fail(function(error){

              console.log(error);
              $(document).find(".muse_setting_handler .setting_wrapper ._sw.gcast ._swo .text").html( window.lang.return('failed',{ucfirst:true}) )
              window.app.becli.alert(false, error.message);

            })
            
          }
  
        })

      });

      

    });
    $(document).on("click", "._sw.fullscreen", function () {
      
      window.m2.user.control.full_screen();

    });
    $(document).on("click", "._gcast_disconnect", function () {

      window.m2_focus.gcast.disconnect();
      window.m2_queue.active.setFocus()
      window.m2_queue.ui.previewFocus()

    });
    $(document).on("click", ".speedBar", function () {
      var n = $(this).attr("data-n")
      $(document).find(".speedBar.active").removeClass("active")
      $(this).addClass("active")
      window.m2.user.control.setSpeed(n)
    })

    $(document).on("input", "#volume_c", function () {
      var req = $(document).find("#volume_c").val();
      $(document).find(".vol_bar .maskB").css("width", req + "%")
      window.m2.user.control.setVolume(req)
      $(document).find("#player.hotFixed").removeClass("hotFixed")
    });
    $(document).on("change", "#volume_c", function () {
      var req = $(document).find("#volume_c").val();
      $(document).find(".vol_bar .maskB").css("width", req + "%")
      window.m2.user.control.setVolume(req)
      $(document).find("#player.hotFixed").removeClass("hotFixed")
    });

    $(document).on("click", "#players .player_movers div", function () {

      if ($(this).hasClass("move")) {
        if ($("body").hasClass("muse_player_reverse"))
          window.ui.body.removeClass("muse_player_reverse", true);
        else
          window.ui.body.addClass("muse_player_reverse", true);
      }
      else if ($(this).hasClass("hide")) {
        window.ui.body.addClass("muse_player_hide", true);
      }
      else if ($(this).hasClass("fullscreen")) {
        window.m2.user.control.full_screen();
      }

    });

    $(document).on("mousedown touchstart", "#progress_range", function () {
      window.m2_focus.ui.clear_seeker();
    });
    $(document).on("input", "#progress_range", function () {
      window.m2_focus.ui.clear_seeker();
      var req = $(document).find("#progress_range").val();
      $(document).find("#player .progress_e").css("width", Math.round(req) + "%")
      try {
        window.m2_focus.cli.informer("duration").done(function(dur){
          var newCurTime = window._g.duration_hr( Math.round( req / 100 * dur ) )
          $(document).find("#player .progress_bar .progress .time.cur").text(newCurTime);
        })
      } catch( err ){ console.log(err)}
    });
    var reInitiateSeeker = null;
    $(document).on("change", "#progress_range", function () {

      window.m2_focus.ui.clear_seeker();
      if (reInitiateSeeker) {
        clearTimeout(reInitiateSeeker);
        reInitiateSeeker = false;
      }

      var req = $(document).find("#progress_range").val();
      window.m2.user.control.seek(req);

      reInitiateSeeker = setTimeout(function () {
        window.m2_focus.ui.setup_seeker();
        reInitiateSeeker = false;
      }, 1000);

    });

    $(document).on("click", ".que_toggle", function () {
      window.m2.user.control.queue_toggle();
    });
    $(document).on("click", ".que_close", function () {
      window.m2.user.control.queue_destroy();
    });
    $(document).on("click", ".que_open", function () {
      window.m2.user.control.queue_build();
    });

    $(document).on("click", ".que_shuffle", function () {
      window.m2.user.control.queue_shuffle();
    });
    $(document).on("click", ".que_repeat", function () {
      window.m2.user.control.queue_repeat_toggle();
    });
    $(document).on("click", ".control.next", function () {
      window.m2.user.control.next();
    });
    $(document).on("click", ".control.prev", function () {
      window.m2.user.control.prev();
    });

    $(document).on("click", ".infinite_control", function () {
      window.m2.user.control.infiniteToggle();
    });
    $(document).on("click", ".queue .item", function () {

      var queID = $(this).attr("id").substr("bof_queue_".length);
      var queI = $(this).data("i");
      if (queID == "ad") return;
      if ( window.m2_queue._items[ queI ] )
      window.m2_queue.active.changeFocus(queI, queID);

    });

    $(document).on("click", "body.muse_player_active.muse_player_hide #players", function () {

      window.ui.body.removeClass("muse_player_hide", true);

    });
    $(document).on("click", ".queue .data_wrapper .tabs .tab", function (e) {

      if ($(this).hasClass("_lyrics") ? $(this).hasClass("has") : false) {
        $(document).find(".queue").addClass("second_tab")
        $(document).find(".queue .data_wrapper .tabs .tab.active").removeClass("active");
        $(document).find(".queue .data_wrapper .tabs .tab._lyrics").addClass("active");
        window.m2_source.lyrics.display()
      }
      else {
        $(document).find(".queue").removeClass("second_tab")
        $(document).find(".queue .data_wrapper .tabs .tab.active").removeClass("active");
        $(document).find(".queue .data_wrapper .tabs .tab._queue").addClass("active");
      }

    });
    $(document).on("click", ".queue #preview .text_wrapper #thingie_skip .btn", function (e) {
      window.m2.user.control.thingie_skip();
    })

    $(document).on("click", ".queue #preview .types .type", function (e) {

      var sources = window.m2_queue.active.get().types.sources;

      var type = $(this).data("type");
      var reqed_sources = sources[type];

      if (!reqed_sources) return;

      if (reqed_sources.count > 1) {

        var list_html = "<div class='quality_list select_list'>";
        for (var z = 0; z < reqed_sources.sources.length; z++) {
          list_html += "<div class='quality_item select_item" + (reqed_sources.sources[z].locked ? " locked" : "") + (reqed_sources.sources[z].active ? " selected" : "") + "' data-type='" + type + "' data-hook='" + reqed_sources.sources[z].hook + "' data-id='" + reqed_sources.sources[z].hash + "'>";
          list_html += reqed_sources.sources[z].title;
          list_html += "</div>";
        }
        list_html += "</div>";

        window.bof_modal.create({
          class: "select muse_quality",
          title: window.lang.return("select_an_item", { ucfirst: true }),
          content: list_html,
          buttons: []
        });

        return;

      }

      var switch_type_name = type;
      if (type == "audio" || type == "video") {
        switch_type_name = reqed_sources.sources[0].hook
      }

      if (reqed_sources.locked) {
        window.app.actions.get_unlock_solution(
          window.muse.queue.active.get().data.ot,
          window.muse.queue.active.get().data.hash,
          type, hook, ID
        )
        return;
      }

      window.m2.user.control.switch_type(switch_type_name);

    });
    $(document).on("click", ".quality_list .quality_item", function (e) {

      var type = $(this).data("type");
      var hook = $(this).data("hook");
      var ID = $(this).data("id");

      if ($(this).hasClass("locked")) {
        window.app.actions.get_unlock_solution(
          window.m2_queue.active.get().data.ot,
          window.m2_queue.active.get().data.hash,
          type, hook, ID
        )
        return;
      }


      window.m2.user.control.switch_type(hook, ID);
      window.bof_modal.close();

    });
    $(document).on("click", ".button.muse_like_handler", function (e) {

      if ( $(this).hasClass("loading") )
      return;

      window.app.actions.user.like( 
        $(this).hasClass("liked") ? false : true, 
        window.m2_queue.active.get().data.ot, 
        window.m2_queue.active.get().data.hash 
      );

      if ( $(this).hasClass("liked") ){
        $(this).removeClass("liked").addClass("unliked").find(".mdi").addClass("mdi-heart-outline").removeClass("mdi-heart")
      } else {
        $(this).addClass("liked").removeClass("unliked").find(".mdi").removeClass("mdi-heart-outline").addClass("mdi-heart")
      }

    });
    $(window).on("resize",function(){
      window.m2_queue.ui.move_frame( window.m2_queue.ui.player_place, true )
    })

  },
  set_mediasession: function () {

    var active = window.m2_queue.active.get().data;
    var _title = window._g.decode_htmlspecialchars(active.title);
    var _stitle = window._g.decode_htmlspecialchars(active.sub_title);

    if ( window.apk ? window.config.platform == "android" : false ){
      window.apk.m2.set_mediasession()
    }
    else if ('mediaSession' in navigator) {

      navigator.mediaSession.metadata = new MediaMetadata({
        title: _title,
        artist: _stitle,
        artwork: [
          { src: active.cover_big ? active.cover_big  : active.cover, sizes: '96x96', type: 'image/jpg' },
          { src: active.cover_big ? active.cover_big  : active.cover, sizes: '128x128', type: 'image/jpg' },
          { src: active.cover_big ? active.cover_big  : active.cover, sizes: '192x192', type: 'image/jpg' },
          { src: active.cover_big ? active.cover_big  : active.cover, sizes: '256x256', type: 'image/jpg' },
          { src: active.cover_big ? active.cover_big  : active.cover, sizes: '384x384', type: 'image/jpg' },
          { src: active.cover_big ? active.cover_big  : active.cover, sizes: '512x512', type: 'image/jpg' },
        ]
      });

    }

    if ( window.apk ? window.config.platform == "android" : false ){
      window.apk.m2.hook_controls();
    } else {
      window.m2.user.native_key_listen();
    }
    

  },
  native_key_listen: function () {

    function nativeKeyListener(e) {
      if (e.key == "MediaTrackNext") {
        window.m2.user.control.next();
        e.preventDefault();
      }
      if (e.key == "MediaTrackPrevious") {
        window.m2.user.control.prev();
        e.preventDefault();
      }
    }

    // Clean
    $(document).off("keydown", nativeKeyListener);
    if ('mediaSession' in navigator) {
      navigator.mediaSession.setActionHandler('previoustrack', null);
      navigator.mediaSession.setActionHandler('nexttrack', null);
      navigator.mediaSession.setActionHandler('play', null);
      navigator.mediaSession.setActionHandler('pause', null);
    }

    var yt_source = false;
    if ( window.m2_queue.active.get() ? ( 
      window.m2_queue.active.get().source ? ( 
        window.m2_queue.active.get().source.type ? 
          window.m2_queue.active.get().source.type[0] === "youtube" 
        : false 
      ) : false 
    ) : false )
      yt_source = true;

    // Set
    if ( !yt_source && ('mediaSession' in navigator)) {

      navigator.mediaSession.setActionHandler('previoustrack', function () {
        window.m2.user.control.prev()
      });
      navigator.mediaSession.setActionHandler('nexttrack', function () {
        window.m2.user.control.next();
      });
      navigator.mediaSession.setActionHandler('play', function () {
        window.m2.user.control.play()
      });
      navigator.mediaSession.setActionHandler('pause', function () {
        window.m2.user.control.pause()
      });

    }
    else {

      $(document).on("keydown", nativeKeyListener);

    }

  },
  request: function (action, object_type, object_hash, sourceArgs, displayData) {

    this.log("Request " + action)

    if (action == "focus") {
      window.m2_focus.loadObject(action, object_type, object_hash, sourceArgs, displayData)
    }
    else {
      window.m2_focus.appendObject(action, object_type, object_hash, sourceArgs, displayData)
      window.m2_queue.ui.build()
    }

    if (action == "focus") {
      setTimeout(function () {
        window.m2_infinite.start(object_type, object_hash, sourceArgs, displayData)
      }, 50)
    }

    window.pageBuilder.widget.item.closeMenu();

  },

  control: {

    log: function ($text, $level) {
      window.m2.user.log("control " + $text, $level)
    },

    switch_type: function (hook, id) {
      window.m2.user.control.log("switch_type hook:" + hook + " id:" + id)
      window.m2_focus.switch_type(hook, id)
    },

    playToggle: function () {
      if (window.m2_focus.getStatus() == "playing") {
        window.m2.user.control.log("pause");
        window.m2.user.control.pause()
      } else {
        window.m2.user.control.log("play");
        window.m2.user.control.play()
      }
    },
    play: function () {
      window.m2.user.control.log("play");
      if (window.m2_thingie.has() && !window.m2_thingie.isPlaying() && window.m2_focus.thingie_on_ini_play) {
        window.m2_thingie.play(true)
        window.m2_focus.thingie_on_ini_play = false
        return;
      }
      window.m2_focus.control("play")
    },
    pause: function () {
      window.m2.user.control.log("pause");
      window.m2_focus.control("pause")
    },

    muteToggle: function () {
      if (window.m2_focus.setting.get("muted")) {
        window.m2_focus.control("volume", window.m2_focus.setting.get("volume"));
        window.m2_focus.setting.set("muted", false)
        $(document).find("#player").addClass("unmuted").removeClass("muted");
        $(document).find("#player.hotFixed").removeClass("hotFixed")
      } else {
        window.m2_focus.control("volume", 0);
        window.m2_focus.setting.set("muted", true)
        $(document).find("#player").removeClass("unmuted").addClass("muted");
        $(document).find("#player.hotFixed").removeClass("hotFixed")

      }
    },
    mute: function () {
      window.m2.user.control.log("mute");
      window.m2_focus.control("volume", 0)
      window.m2_focus.setting.set("muted", true);
      $(document).find("#player").removeClass("unmuted").addClass("muted");
    },
    unmute: function () {
      window.m2.user.control.log("unmute");
      window.m2_focus.control("volume", window.m2_focus.setting.get("volume"))
      window.m2_focus.setting.set("muted", false);
      $(document).find("#player").addClass("unmuted").removeClass("muted");
    },

    setVolume: function (newVal) {

      window.m2.user.control.log("setVolume:" + newVal)
      window.m2_focus.control("volume", newVal)

      if (newVal > 0) {
        window.m2_focus.setting.set("muted", false)
        window.m2_focus.setting.set("volume", newVal)
        $(document).find("#player").addClass("unmuted").removeClass("muted");
      } else {
        window.m2_focus.setting.set("muted", true)
        window.m2_focus.setting.set("volume", 0)
        $(document).find("#player").removeClass("unmuted").addClass("muted");
      }

    },
    setSpeed: function (newVal) {

      window.m2.user.control.log("setSpeed:" + newVal)
      window.m2_focus.control("speed", newVal)
      window.m2_focus.setting.set("speed", newVal)

    },
    full_screen: function () {
      window.m2_focus.control("full_screen");
    },
    seek: function (to) {
      window.m2.user.control.log("Seek to " + to);
      window.m2_focus.control("seek", to);
    },
    prev: function () {
      window.m2.user.control.log("Prev");
      window.m2_queue.prev();
    },
    next: function () {
      window.m2.user.control.log("Next");
      window.m2_queue.next("hard");
    },

    queue_toggle: function () {

      window.m2.user.control.log("Queue Toggle");
      if (window.m2_queue.ui.isOpen()) {
        window.m2.user.control.queue_destroy();
      }
      else {
        window.m2.user.control.queue_build();
      }

    },
    queue_destroy: function () {

      window.m2.user.control.log("Queue Destroy");

      if (!window.m2_queue.ui.isOpen())
        return;

      window.m2_queue.ui.destroy();

    },
    queue_build: function () {

      window.m2.user.control.log("Queue Build");

      if (window.m2_queue.ui.isOpen())
        return;

      if (window.app?window.app.config.mobile:false)
        window.m2_focus.ui.openAnime()
      else
        window.m2_queue.ui.build();

    },

    queue_shuffle: function () {
      window.m2.user.control.log("Queue Shuffle")
      window.m2_queue.shuffle();
    },
    queue_repeat_toggle: function () {
      var repeat = window.m2_focus.setting.get("repeat")
      window.m2_focus.setting.set("repeat", repeat ? false : true)
      if (repeat) {
        window.m2.user.control.log("Repeat -> Off")
        $(document).find("#player").addClass("repeat_off").removeClass("repeat_on");
        window.ui.body.addClass( "repeat_off" , true);
        window.ui.body.removeClass( "repeat_on" , true);
      } else {
        window.m2.user.control.log("Repeat -> On")
        window.m2_infinite.off();
        $(document).find("#player").addClass("repeat_on").removeClass("repeat_off");
        window.ui.body.removeClass( "repeat_off" , true);
        window.ui.body.addClass( "repeat_on" , true);
      }
    },

    infiniteToggle: function () {
      var infinite = window.m2_focus.setting.get("infinite")
      window.m2_focus.setting.set("infinite", infinite ? false : true)
      if (infinite) {
        window.m2.user.control.log("Infinite -> Off")
        window.m2_infinite.off();
      } else {
        window.m2.user.control.log("Infinite -> On")
        window.m2_focus.setting.set("repeat", false);

        window.m2_infinite.on();
      }
    },

    thingie_skip: function () {
      window.m2.user.control.log("Trying to skip advertisement")
      window.m2_thingie.skip()
    },

    close: function (delay) {

      window.m2.user.control.log("Closing requested")
      if (delay == 0) {
        window.m2.close()
      } else {
        window.m2.closeTimeRemaining = delay * 60
        window.m2.user.control.closer()
      }

    },
    closer: function () {

      if (window.m2.closerTimer)
        clearTimeout(window.m2.closerTimer)

      if (window.m2.closeTimeRemaining <= 0) {
        window.m2.close()
        return
      }

      window.m2.closeTimeRemaining = window.m2.closeTimeRemaining - 1;
      window.m2.closerTimer = setTimeout(window.m2.user.control.closer, 1000)
      $(document).find(".closer_coutdown").addClass("active").text(window.general.duration_hr(window.m2.closeTimeRemaining))

    }

  },

}

window.m2.config = (function () {

  var _i = {
      thingie_offset: 5,
      thingie_interval: 1,
      thingie_skipable: true,
      thingie_skipable_threshold: 10,
      muse_hide: false,
      muse_hide_yt: false,
      queue_save: true,
      queue_disable_auto: false,
      queue_hide_lyrics: false,
      queue_hide_infinite: false,
      queue_hide: false,
      record_threshold: 10
  };

  return {
      get: function (key) {
          var item = _i[key];
          if ( key == "queue_save" ){
            item = item !== false && item !== null && item !== undefined
          }
          return item
      },
      getAll: function(){
          return _i
      },
      set: function (key, value) {
          _i[key] = value;
      },
  };

})()

var waiter = false
function waitForAllM2s(){

  if (

    window.m2_source &&
    window.m2_thingie &&

    typeof m2c !== 'undefined' &&
    typeof m2c_audio !== 'undefined' &&
    typeof m2c_video !== 'undefined' &&
    typeof m2c_soundcloud !== 'undefined' &&
    typeof m2c_youtube !== 'undefined' &&

    window.m2.app_ready === true &&
    window.m2_focus &&
    window.m2_infinite &&
    window.m2_queue

   ){
    window.m2.all_parts_ready()
    return
  }

  if ( waiter ) clearTimeout( waiter )
  waiter = setTimeout( function(){
    waitForAllM2s()
  }, 100 );
  
}

waitForAllM2s();
