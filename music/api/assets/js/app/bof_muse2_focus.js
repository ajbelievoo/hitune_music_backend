"use strict";

window.m2_focus = {

    status: "loading",

    log: function( text, level, ID ){
        window.m2.log( ( ID ? ( ID + ":" ) : ( window.m2_focus.ID ? window.m2_focus.ID + ":" : "" ) ) + "m2_focus", text, level )
    },

    set: function( sourceData, exeArgs ){

        if ( !window.m2.all_parts_ready() ) return;

        var ID = window._g.uniqid(10);
        window.m2_focus.ID = ID;

        var promise = $.Deferred();

        window.m2_focus.cancelPreSetObject();
        window.m2_focus.cancelPreSet();
        window.m2_focus.cancelPreCli();

        window.m2_focus.sourceData = sourceData;
        window.m2_focus.ui.setup_player(sourceData.data, sourceData.source);
        window.m2_focus.setStatus("loading")
        window.m2_focus.ui.hook();
        window.m2.user.set_mediasession()

        var thingie_promise = null
        if (window.m2.config.get("hotfix")) {
            if (exeArgs ? exeArgs.preload : false) {
                thingie_promise = $.Deferred()
                thingie_promise.reject()
            }
            else if (window.m2.hotfix_inied ? window.m2.hotfix_inied.length : false) {
                thingie_promise = window.m2_thingie.check()
            }
            else {
                thingie_promise = $.Deferred()
                thingie_promise.reject()
            }
        }
        else if (exeArgs ? exeArgs.preload : false) {
            thingie_promise = $.Deferred()
            window.m2_thingie.check()
                .done(function () {
                    thingie_promise.reject()
                    window.m2_focus.thingie_on_ini_play = true
                })
                .fail(function () {
                    thingie_promise.reject()
                })
        }
        else {
            thingie_promise = window.m2_thingie.check()
        }

        var raaz_promise = window.m2_source.raaz.parse(sourceData)
        window.m2_focus.raaz_promise = raaz_promise;

        var recheckOffline = $.Deferred();

        if ( window.bof_offline ){
            window.bof_offline.db.get_objects(sourceData.data.ot, sourceData.data.hash)
            .done(function (dled_item) {
                sourceData.source = dled_item.data.muse.source
                recheckOffline.resolve();
                window.m2_focus.log("Recheck offline -> Found the file!", 2)
            })
            .fail(function (err) {
                recheckOffline.resolve()
                window.m2_focus.log("Recheck offline -> nope", 2)
            })
        } else {
            recheckOffline.resolve();
        }

        recheckOffline.done(function () {

            var autoplay = true
            if (exeArgs ? Object.keys(exeArgs).includes("autoplay") : false) {
                autoplay = exeArgs.autoplay
            }

            var preload = false;
            if (exeArgs ? Object.keys(exeArgs).includes("preload") : false) {
                preload = exeArgs.preload
            }

            window.m2_focus._exeArgs = exeArgs;
            window.m2_focus._offset = 0;

            window.m2_focus.log("Set " + sourceData.data.ot + ":" + sourceData.data.hash + " auto:" + (autoplay ? "1" : "0") + " pre:" + (preload ? "1" : "0"), 2)
            $(document).trigger("m2_focus_set", sourceData)

            window.m2_focus.thingie_on_ini_play = false

            var thingie_play_promise = $.Deferred()
            thingie_promise.done(function () {
                window.m2_focus.log("Got a thingie. Play it", 3, ID)
                window.m2_thingie.play(autoplay).done(function () {
                    window.m2_focus.log("Got a thingie. Playing it", 3, ID)
                    thingie_play_promise.resolve()
                }).fail(function () {
                    window.m2_focus.log("Got a thingie. Failed to play it. IOS? Wait for next item", 3, ID)
                    thingie_play_promise.reject()
                })
            })
            .fail(function () {
                window.m2_focus.log("No thingie. Play the item", 3, ID)
                thingie_play_promise.reject()
            })

            thingie_play_promise.fail(function () {
                raaz_promise
                    .done(function (parsedSource) {

                        window.m2_focus.parsedSource = parsedSource
                        window.m2_focus.log("Initiating cli", 3, ID)

                        if ( window.m2_focus.gcast.isConnected() ){
                            if ( 
                                ( parsedSource.type[0] == "audio" || parsedSource.type[0] == "video" ) && 
                                parsedSource.type[1]["type"] == "free" && 
                                parsedSource.type[1]["address"] 
                            ){
                                window.m2_focus.log("--> GCAST Connected. Use that", 3, ID)
                                parsedSource.type[1]["oType"] = parsedSource.type[0]
                                parsedSource.type[0] = window.config.platform == "web" ? "gcast" : "gcastn";
                            }
                        }
                        else if ( parsedSource.type[0] == "gcast" || parsedSource.type[0] == "gcastn" ){
                            parsedSource.type[0] = parsedSource.type[1]["oType"];
                        }

                        if ( window.apk ? ( window.config.platform == "ios" && parsedSource.type[0] == "youtube" ) : false ){
                            parsedSource.type[0] = "ios_youtube"
                        }

                        var cli_promise = $.Deferred()
                        var cli = new m2c(
                            {
                                autoplay: autoplay,
                                volume: window.m2_focus.setting.get("volume"),
                                muted: window.m2_focus.setting.get("muted"),
                                speed: window.m2_focus.setting.get("speed"),
                            },
                            sourceData.data,
                            parsedSource.type,
                            window.m2_focus.eventHandler,
                            cli_promise,
                            ID
                        )

                        cli_promise
                        .done(function () {
                            window.m2_focus.log("Initiating cli -> Success", 3, ID)
                            window.m2_focus.recorder.start()
                            if ( exeArgs ? exeArgs.offset : false )
                                window.m2_focus._offset = exeArgs.offset
                        })
                        .fail(function (err) {
                            window.m2_focus.log("Initiating cli -> Failure -> " + err.message, 1, ID)
                            window.m2_focus.errorHandler(err)
                        })

                        window.m2_focus.cli = cli;

                    })
                    .fail(function (err) {
                        window.m2_focus.log("Raaz.Parse failed -> " + err.message, 1, ID)
                        window.m2_focus.errorHandler(err)
                    });
            }).done(function () {
                window.m2_focus.log("Got thingie. Don't play the item", 3, ID)
            })

            window.m2_focus.raaz_promise = raaz_promise;

        })

        
        return promise;

    },
    setStatus: function( newSta ){

        window.m2_focus.log( "setStatus: " + newSta )
        if ( window.apk ? window.apk.m2 : false ) window.apk.m2.setStatus( newSta )
        window.m2_focus.status = newSta;

        if ( newSta == "canplay" && window.m2_focus._offset > 0 ){
            window.m2_focus.log( "HAS OFFSET: " + window.m2_focus._offset )
            window.m2_focus.cli.controller("seek",window.m2_focus._offset);
            window.m2_focus.cli.controller("pause");
            window.cache.set("muse_offset_h",false);
            window.m2_focus._offset = 0;
        }

    },
    getStatus: function(){
        return window.m2_focus.status
    },
    eventHandler: function( ID, event, extraData ){
        
        if ( ID != window.m2_focus.ID )
            return;

        window.m2_focus.setStatus( event );

        if ( event == "seeked" ){

            window.m2_focus.cli.controller("play");
            window.m2_focus.setStatus("playing");

        }
        else if ( event == "ended" ){

            if ($(document).find("#player").hasClass("preview_source")) {
                window.app.actions.get_unlock_solution(
                    window.m2_focus.sourceData.data.ot,
                    window.m2_focus.sourceData.data.hash
                )
            }

            window.m2_focus.cli.cleanup();
            setTimeout(function(){
                window.m2_queue.next()
            },100)

        }
        else if ( event == "error" ){

            window.m2_focus.log( "ERROR " + ( extraData ? extraData : "" ), 1, ID )
            window.m2_focus.errorHandler({
                message: extraData ? extraData : null,
                skip: true,
                skip_wait: true
            })

        }

        window.m2_focus.ui.hook();

    },
    cancelPreSet: function(){

        if ( window.m2_focus.raaz_promise ? window.m2_focus.raaz_promise.state() == "pending" : false ){

            window.m2_focus.log( "Canceling the old raaz promise" )
            window.m2_focus.raaz_promise.reject({
                skip: false,
                message: "Canceled by new set: " + window.m2_focus.ID
            })
            window.m2_source.raaz.killActive();

        }

    },
    cancelPreCli: function(){

        if ( window.m2_focus.skiper ){
            clearTimeout( window.m2_focus.skiper )
        }

        if ( window.m2_focus.report_promise ){
            try {
                window.m2_focus.report_promise.reject()
            } catch( err ){}
        }

        if ( !window.m2_focus.cli )
        return;

        window.m2_focus.cli.cleanup();
        window.m2_focus.cli = null;

    },

    loadObject: function( action, object_type, object_hash, sourceArgs, displayData ){

        if ( !window.m2.all_parts_ready() ) return;

        var setObjectID = window._g.uniqid(10);
        window.m2_focus.setObjectID = setObjectID;
        
        window.m2_focus.cancelPreSetObject();
        window.m2_focus.cancelPreSet();
        window.m2_focus.cancelPreCli();
        
        if ( !window.m2.config.get("hotfix") )
        window.m2_thingie.check()
        
        window.m2_focus.sourceData = { data: displayData, source: sourceArgs };
        window.m2_focus.ui.setup_player( displayData, sourceArgs );
        window.m2_focus.setStatus("loading")
        window.m2_focus.ui.hook();

        var source_promise = window.m2_source.solve( action, object_type, object_hash, sourceArgs, displayData ).promise;

        source_promise
        .done(function( sources ){
            
            window.m2_focus.log( "Source.Solve OK", 3 )
            window.m2_queue.extend( sources, action )

        })
        .fail(function( err ){
            window.m2_focus.log( "Source.Solve failed -> " + err.message, 1, setObjectID )
            window.m2_focus.errorHandler( err );
        })

        window.m2_focus.source_promise = source_promise;
        return source_promise

    },
    appendObject: function( action, object_type, object_hash, sourceArgs, displayData ){

        if ( !window.m2.all_parts_ready() ) return;

        var source_promise = window.m2_source.solve( action, object_type, object_hash, sourceArgs, displayData ).promise;

        source_promise
        .done(function( sources ){
            window.m2_focus.log( "Source.Solve OK", 3 )
            window.m2_queue.extend( sources, action )

        })
        .fail(function( err ){
            window.m2_focus.log( "Source.Solve failed -> " + err.message, 1, setObjectID )
            window.m2_focus.errorHandler( err );
        })

        return source_promise

    },
    cancelPreSetObject: function(){

        if ( window.m2_focus.source_promise ? window.m2_focus.source_promise.state() == "pending" : false ){

            window.m2_focus.log( "Canceling the old source promise" )
            window.m2_focus.source_promise.reject({
                skip: false,
                message: "Canceled by new setObject: " + window.m2_focus.setObjectID
            })
            window.m2_source.cancelXhr();

        }

    },

    control: function (action, data) {

        window.m2_focus.log("control: " + action);
        var promise = $.Deferred();

        try {
            window.m2_focus.cli.controller( action, data );
            promise.resolve();
        } catch (err) {
            window.m2_focus.log( action + " -> failed" )
            promise.reject();
        }

        return promise;

    },
    inform: function( hook ){

        if ( window.m2_focus.cli )
        return window.m2_focus.cli.informer( hook )

    },
    errorHandler: function ( err ){

        console.log( err );

        window.m2_focus.log( "errorHandler " + err.message, 1 )

        window.m2_focus.recorder.stop()
        window.m2_focus.setStatus("error")
        window.m2_focus.ui.hook()

        var report = window.m2_focus.recorder.report_source()

        report.done(function(newData){

            if ( newData ? newData.youtube_id : false ){

                window.m2_focus.setStatus("loading")
                window.m2_focus.ui.hook()
                window.m2_queue._items[ window.m2_queue._i ].source.type[1].youtube_id = newData.youtube_id
                window.m2_queue.active.setFocus({
                    autoplay: true
                })

            } else {

                if ( err.skip || err.skip_wait ){

                    window.m2_focus.log("---------SKIP---------" + ( err.skip_wait ? " after delay" : "" ))
        
                    if ( window.m2_focus.skiper ){
                        clearTimeout( window.m2_focus.skiper )
                    }
        
                    window.m2_focus.skiper = setTimeout( function(){
                        window.m2_focus.log("---------SKIPPING---------")
                        window.m2_queue.next()
                    }, 2000 )

                    if ( window.m2_queue.active.get() )
                    window.m2_queue.removeItem( window.m2_queue.active.get().data.ID )
        
                }

            }

        })        

        if ( err.display && err.message ){
            window.app.becli.alert( false, err.message ? err.message : "" );
        }

    },
    
    switch_type: function( hook, id ){
        
        window.m2_focus.log("Changing type:"+hook)
        window.m2_focus.setting.set("type",hook)

        var active = window.m2_queue.active.get()
        var source_promise = window.m2_source.solve( "type_switch", active.data.ot, active.data.hash, false, { reqed_id: id } ).promise;

        source_promise
        .done(function( sources ){
            
            window.m2_focus.log( "Source.Solve OK", 3 )
            window.m2_queue.active.update( sources[0]["source"], hook, id )
            window.m2_queue.switch_type( hook )
            window.m2_queue.active.setFocus()
            window.m2_queue.ui.previewFocus()
            
        })
        .fail(function( err ){})

    },
    
}

window.m2_focus.ui = {

    parent: window.m2_focus,
    seeker: null,
    player_classes: [],

    log: function( text, level, ID ){
        window.m2_focus.log( "UI " + text, level, ID )
    },

    add_cli_htmls: function(){

        window.m2_focus.log(" Add cli htmls")

        var que = "";
        que += "<div class='queue'>";
  
          que += "<div class='touch'></div>";

          if ( window.app?window.app.config.mobile:false ){
            que += "<div class='mob_top_btns'>";
            que += "<div class='button muse_setting_handler'><span class='mdi mdi-tune-variant'></span></div>";
            que += "<div class='button que_toggle'><span class='mdi mdi-chevron-down'></span></div>";
            que += "</div>";
          }
  
          que += '<div id="players">';
            que += '<div class="a_player" id="youtube"></div>';
            que += '<div class="a_player" id="soundcloud"></div>';
            que += '<div class="a_player" id="videojs"></div>';
            que += "<div class='hplayer_veil'><div class='hpv_in'><div class='hpv_play'><span class='mdi mdi-play'></span></div><div class='hpv_t'></div><div class='hpv_b'><span class='mdi mdi-music-note'></span>HITUNE MUSIC</div></div></div>";
            que += "<div class='hplayer_shield'><span class='hpt_ic mdi mdi-music-note'></span><span class='hpt_t'></span></div>";
            que += "<div class='hplayer'>";
              que += "<div class='hp_seek'><div class='hp_seek_fill'></div></div>";
              que += "<div class='hp_row'>";
                que += "<div class='hp_btn hp_play'><span class='mdi mdi-play'></span></div>";
                que += "<div class='hp_time'><span class='hp_cur'>0:00</span><span class='hp_sep'> / </span><span class='hp_dur'>0:00</span></div>";
                que += "<div class='hp_flex'></div>";
                que += "<div class='hp_brand'><span class='mdi mdi-music-note'></span><span class='_t'>HITUNE MUSIC</span></div>";
                que += "<div class='hp_btn hp_vol'><span class='mdi mdi-volume-high'></span></div>";
                que += "<div class='hp_btn hp_full'><span class='mdi mdi-fullscreen'></span></div>";
              que += "</div>";
            que += "</div>";
            que += "<div class='player_movers'>";
              que += "<div class='player_mover fullscreen'>"+window.lang.return( "fullscreen", { ucfirst: true } )+"</div>";
              que += "<div class='player_mover move'>"+window.lang.return( "move", { ucfirst: true } )+"</div>";
              que += "<div class='player_mover hide'>"+window.lang.return( "hide", { ucfirst: true } )+"</div>";
            que += "</div>";
          que += "</div>";
  
          que += "<div id='preview'></div>";
  
          que += "<div class='data_wrapper'>";
            que += "<div class='tabs clearafter'>";
              que += "<div class='tab _queue active'>"+window.lang.return( "queue", { ucfirst: true } )+"</div>";
              que += "<div class='tab _lyrics'>"+window.lang.return( "lyrics", { ucfirst: true } )+"</div>";
            que += "</div>";
          que += "<div class='tBody'>";
          que += "<div class='list'>";
          que += "<div class='_items items clearafter'></div>";
          que += "<div class='infinite button infinite_control'>\
          <div class='_t'>"+window.lang.return( "infinite_play", { ucfirst: true } )+"</div>\
          <div class='_s'>"+window.lang.return( "infinite_play_tip", { ucfirst: true } )+"</div>\
          <div class='_mask'></div>\
          </div>";
          que += "<div class='_items next_items clearafter'></div>";
          que += "</div>";
          que += "<div class='lyrics clearafter'></div>";
          que += "</div>";
          que += "</div>";
        que += "</div>";
  
        $("body").append( que );
        window.m2_focus.ui.touch()
        window.m2_focus.ui.hplayer_bind()

        if (!window.m2.isEmbed() && (window.app?window.app.config.mobile:false) ) {

            if ( window.m2_focus.ui.hammer_mc_que ){
                window.m2_focus.ui.hammer_mc_que.destroy();
                window.m2_focus.ui.hammer_mc_que = false;
            }

            // $("body.mobile .queue").on("scroll",function(){
                // $(document).find("#player").css("--preview_scroll_top",$(document).find(".queue").scrollTop()+"px")   
            // })

            var mc = new Hammer(document.querySelector(".queue"));
            window.m2_focus.ui.hammer_mc_que = mc

            mc.add(new Hammer.Pan({
                direction: Hammer.DIRECTION_DOWN|Hammer.DIRECTION_UP,
                threshold: 0
            }));

            let startScrollTop = 0;
            let startDeltaY = 0;

            mc.on('panstart', function (e) {

                if ( $(e.target).hasClass("touch_sensitive") || $(e.target).parents(".touch_sensitive").length )
                    return;

                startScrollTop = $(document).find(".queue").scrollTop();
                startDeltaY = e.deltaY;
                $(document).find(".queue").addClass("closing")
                window.ui.body.addClass( "muse_que_closing", true );
            });

            mc.on('pan', function (e) {

                if ( $(e.target).hasClass("touch_sensitive") || $(e.target).parents(".touch_sensitive").length )
                    return;

                var _direction = "up";

                if ( e.deltaY < 0 )
                    _direction = "down";

                var _di = Math.abs( e.deltaY );

                var _si = $(document).find(".queue").scrollTop()

                if ( _direction == "down" ){
                    $(document).find(".queue").scrollTop( startScrollTop + ( _di * 1.66 ) );
                    // $(document).find("#player").css("--preview_scroll_top",$(document).find(".queue").scrollTop()+"px")   
                    $(document).find("body").css("--que_bottom","0px") 
                }
                else {
                    if ( _si > 0 ){
                        $(document).find(".queue").scrollTop( startScrollTop - _di );
                        // $(document).find("#player").css("--preview_scroll_top",$(document).find(".queue").scrollTop()+"px")  
                        $(document).find("body").css("--que_bottom","0px")  
                    }
                    else {
                        $(document).find(".queue").css("top", _di - startScrollTop);
                        // $(document).find("#player").css("--preview_scroll_top",((_di - startScrollTop)*-1)+"px")

                        var _poa = _di / 100;
                        _poa = 1 - ( _poa > 1 ? 1 : _poa );
                        $(document).find("#player").css("opacity",_poa)

                        if ( _di > 200 ){
                            var _dii = _di - 200;
                            var _mh = 160;
                            var _cs = ( ( _dii / _mh ) * 70 )
                            _cs = _cs < 0 ? 0 : ( _cs > 70 ? 70 : _cs );
                            $(document).find("body").css("--que_bottom",_cs + "px")                            
                        }
                    }
                }

                if (e.isFinal) {
                    if ($(document).find(".queue").offset().top > $(window).height() / 5) {
                        window.m2_queue.ui.destroy();
                        // $(document).find("#player").css("--preview_scroll_top","0px")
                        setTimeout(function(){
                            $(document).find("body").css("--que_bottom","0px")
                        },250)
                    } else {
                        $(document).find(".queue").css("top", "0px");
                        // $(document).find("#player").css("--preview_scroll_top",$(document).find(".queue").scrollTop()+"px")
                        $(document).find("body").css("--que_bottom","0px")
                    }
                    $(document).find(".queue").removeClass("closing")
                    window.ui.body.removeClass( "muse_que_closing", true );
                    $(document).find("#player").css("opacity","")
                }

            });

        }

    },
    hplayer_bind: function () {

        if ( window.m2_focus.ui._hplayer_bound )
            return;
        window.m2_focus.ui._hplayer_bound = true;

        $(document).on("click", "#players .hp_play", function(){
            window.m2.user.control.playToggle();
        });
        $(document).on("click", "#players .hp_full", function(){
            window.m2_focus.control("full_screen");
        });
        $(document).on("click", "#players .hp_vol", function(){
            window.m2.user.control.muteToggle();
        });
        $(document).on("click", "#players .hplayer_veil", function(){
            if ( window.m2_focus.status == "ended" && window.m2_queue )
                window.m2_queue.next();
            else
                window.m2.user.control.playToggle();
        });
        $(document).on("click", "#players .hp_seek", function(e){
            var w = $(this).width();
            if ( !w ) return;
            var pct = ( ( e.pageX - $(this).offset().left ) / w ) * 100;
            pct = pct < 0 ? 0 : ( pct > 100 ? 100 : pct );
            window.m2_focus.control("seek", pct );
        });

    },
    hplayer_tick: function ( duration, offset ) {

        var $pl = $(document).find("#players");
        if ( !$pl.length )
            return;

        var st = window.m2_focus.getStatus();
        var playing = st == "playing";
        var ended_by_progress = ( duration && duration > 0 && offset >= duration - 0.5 );
        $pl.toggleClass( "hp_paused", !playing || ended_by_progress );
        $pl.toggleClass( "hp_stopped", ended_by_progress || st == "paused" || st == "ended" || st == "stopped" || st == "canplay" );

        var icon = $pl.find(".hp_play .mdi");
        icon.removeClass("mdi-play mdi-pause mdi-refresh").addClass( window.m2_focus.getStatus() == "loading" ? "mdi-refresh" : ( playing ? "mdi-pause" : "mdi-play" ) );

        var muted = window.m2_focus.setting.get("muted");
        $pl.find(".hp_vol .mdi").removeClass("mdi-volume-high mdi-volume-off").addClass( muted ? "mdi-volume-off" : "mdi-volume-high" );

        if ( duration && duration > 0 ){
            var ratio = Math.min( 100, Math.max( 0, offset / duration * 100 ) );
            $pl.find(".hp_seek_fill").css( "width", ratio + "%" );
            $pl.find(".hp_cur").text( window._g.duration_hr( offset ) );
            $pl.find(".hp_dur").text( window._g.duration_hr( duration ) );
        }

        var act = window.m2_queue ? window.m2_queue.active.get() : false;
        if ( act ? ( act.data ? act.data.title : false ) : false ) {
            $pl.find(".hpt_t").text( act.data.title );
            $pl.find(".hpv_t").text( act.data.title );
        }

    },
    setup_seeker: function () {

        if (window.m2_focus.ui.seeker_timer) {
            window.m2_focus.ui.clear_seeker(true)
            if ( window.apk ? window.apk.m2 : false ) window.apk.m2.clear_seeker(true);
        }

        var get_duration = window.m2_focus.inform("duration")
        var get_offset = window.m2_focus.inform("seek")
        var get_buffered_ratio = window.m2_focus.inform("buffered")

        $.when(get_duration, get_offset, get_buffered_ratio).done(function (duration, offset, buffered_ratio) {

            var offset_ratio = Math.round(duration ? offset / duration * 100 : 0);

            $(document).find("#player .progress_bar .progress .progress_e").css("width", offset_ratio + "%")
            $(document).find("#player .progress_bar .progress .progress_b").css("width", buffered_ratio + "%")

            var time_cur = window._g.duration_hr( offset );
            if ( offset && ( offset > 0 || offset === 0 || offset === "0" ) && $(document).find("#player .progress_bar .progress .time.cur").text() != time_cur)
                $(document).find("#player .progress_bar .progress .time.cur").text(time_cur);

            var time_tot = window._g.duration_hr( duration );
            if ( duration && ( duration > 0 ) && $(document).find("#player .progress_bar .progress .time.tot").text() != time_tot)
                $(document).find("#player .progress_bar .progress .time.tot").text(time_tot)

            if ( window.apk ? window.apk.m2 : false ) window.apk.m2.update_seeker( duration, offset, buffered_ratio, offset_ratio )

            window.m2_focus.ui.hplayer_tick( duration, offset );

            try {
                if ( duration > 5*60 && Math.ceil(offset)%5 == 0 && offset > 30 ){
                    window.m2_focus.log( "NEW OFFSET: " + offset )
                    if ( window.cache.get( "muse_offset_i" ) !== offset ){
                        window.cache.set( "muse_offset_i", offset )
                        window.cache.set( "muse_offset_p", offset_ratio )
                        window.cache.set( "muse_offset_h", window.m2_queue.active.get().data.ot + window.m2_queue.active.get().data.hash )
                    }
                }
            } catch( Error ){

            }

        });

        window.m2_focus.ui.seeker_timer = setTimeout(
            window.m2_focus.ui.setup_seeker,
            1000
        );

    },
    clear_seeker: function($silent){
        if ( $silent !== true )
        window.m2_focus.ui.log( "Clearing seeker" )
        try {
            clearTimeout( window.m2_focus.ui.seeker_timer );
        } catch ( err ){}   
    },
    setup_player: function( displayData, sourceData ){

        window.m2_focus.log("Add player")

        if ( window.m2_thingie.has() && window.m2_thingie.isPlaying() )
            return;

        var newNaved = $(document).find("#player").hasClass("newNav");
        
        var preview_source = false
        if ( window.bof._safeGet( "sourceData.type[1].preview" ) ){
            preview_source = sourceData["type"][1]["preview"]
        }

        var cover = window.config.web.address + "api/assets/images/default_cover.png";
        if (displayData? displayData.cover : false) {

            const regex = /src(?:set)?="([^"]+)"/g;
            const matches = [];
            let match;
            while ((match = regex.exec(displayData.cover)) !== null) {
                matches.push(match);
            }
            const lastUrl = matches.length > 0 ? matches[matches.length - 1][1] : null;

            if (lastUrl) {
                cover = lastUrl
            } else {
                cover = displayData.cover
            }
        }

        window.ui.body.addClass( window.m2_focus.setting.get("repeat") ? "repeat_on" : "repeat_off" , true);
        window.ui.body.removeClass( !window.m2_focus.setting.get("repeat") ? "repeat_on" : "repeat_off" , true);

        var player = "";
        player += "<div id='player' class='clearafter" + 
        (window.m2_focus.setting.get("repeat") ? " repeat_on" : " repeat_off") + 
        (window.m2_focus.setting.get("muted") ? " muted" : " unmuted") + 
        ( preview_source ? " preview_source" : "" ) +
        ( newNaved ? " newNav" : "" ) +
        (window.m2_focus.ui.player_classes?(" " + window.m2_focus.ui.player_classes.join(" ")):"") +
        "'>";

        if ( window.app?window.app.config.mobile:false )
            player += "<div class='data_wrapper touch_sensitive' id='bof_muse_active'>";
        else
            player += "<div class='data_wrapper touch_sensitive item no_action' id='bof_muse_active'>";
        player += "<div class='source_data'>";
        player += "<div class='cover_holder'><div style='background-image:url(\"" + cover + "\")'></div></div>";
        player += "<div class='data'>";
        player += "<a href='" + displayData.link + "' class='_title'>" + displayData.title + "</a>";
        if (displayData.sub_title)
        player += "<a " + (displayData.sub_link ? " href='" + displayData.sub_link + "' " : "") + " class='_sub_title "+(preview_source?"_preview_wrapper":"")+"'>" + ( 
            preview_source ? ( "<span class='preview'>"+window.lang.return( "preview", { ucfirst: true } )+"</span>" ) : displayData.sub_title 
        ) + "</a>";
        player += "</div>";
        player += "<div class='button_wrapper more'><div><span class='mdi mdi-dots-vertical'></span></div></div>";
        player += "</div>";
        player += "</div>";

        player += "<div class='controls_wrapper clearafter'>";
        // if ( window.app.config.mobile )
        //     player += "<div class='control repeat que_repeat'><span class='mdi mdi-repeat'></span><span class='mdi mdi-repeat-off'></span></div>";
        player += "<div class='control prev' data-action='prev'><span class='mdi mdi-skip-previous'></span></div>";
        player += "<div class='control play' data-action='play'><span class='mdi mdi-refresh'></span></div>";
        player += "<div class='control next' data-action='next'><span class='mdi mdi-skip-next'></span></div>";
        // if ( window.app.config.mobile )
        //     player += "<div class='control shuffle que_shuffle'><span class='mdi mdi-shuffle'></span></div>";
        player += "</div>";

        player += "<div class='progress_bar'>";
        player += "<div class='progress'>\
          <input type='range' id='progress_range' min='0' max='100'>\
          <div class='progress_e'></div>\
          <div class='progress_b'></div>\
          <div class='time cur'>00:00</div>\
          <div class='time tot'>"+ window.general.duration_hr(displayData.duration) + "</div>\
          </div>";
        player += "</div>";

        // player += "<div class='button que_repeat'><span class='mdi mdi-repeat'></span><span class='mdi mdi-repeat-off'></span></div>";
        // player += "<div class='button que_shuffle'><span class='mdi mdi-shuffle'></span></div>";

        player += "<div class='buttons_wrapper touch_sensitive clearafter'>";
        if ( window.app?window.app.config.mobile:false ){
            player += "<div class='button muse_like_handler loading'><span class='mdi mdi-heart-outline'></span><span class='_t li'>Like</span><span class='_t unli'>"+window.lang.return( "unlike", { ucfirst: true } )+"</span></div>";
            player += "<div class='button volume_control'><span class='mdi mdi-volume-high'></span><span class='mdi mdi-volume-off'></span><span class='_t mu'>"+window.lang.return( "mute", { ucfirst: true } )+"</span><span class='_t unmu'>"+window.lang.return( "unmute", { ucfirst: true } )+"</span></div>";
            player += "<div class='button infinite_control'><span class='mdi mdi-infinity'></span><span class='mdi mdi-all-inclusive'></span>"+window.lang.return( "infinite", { ucfirst: true } )+": <span class='_t mu'>"+window.lang.return( "on", { ucfirst: true } )+"</span><span class='_t unmu'>"+window.lang.return( "off", { ucfirst: true } )+"</span></div>";
            player += "<div class='button que_repeat'><span class='mdi mdi-repeat'></span><span class='mdi mdi-repeat-off'></span>"+window.lang.return( "repeat", { ucfirst: true } )+": <span class='_t mu'>"+window.lang.return( "on", { ucfirst: true } )+"</span><span class='_t unmu'>"+window.lang.return( "off", { ucfirst: true } )+"</span></div>";
            player += "<div class='button que_shuffle'><span class='mdi mdi-shuffle'></span>"+window.lang.return( "shuffle", { ucfirst: true } )+"</div>";
        } else {
            player += "<div class='button muse_like_handler loading'><span class='mdi mdi-heart-outline'></span></div>";
            player += "<div class='button volume_control'><span class='mdi mdi-volume-high'></span><span class='mdi mdi-volume-off'></span></div>";
            player += "<div class='button muse_setting_handler'><span class='mdi mdi-cog-outline'></span></div>";
            player += "<div class='button que_toggle'><span class='mdi mdi-chevron-up'></span></div>";
        }
        player += "</div>";

        player += "</div>";

        window.ui.body.addClass("muse_active", true);

        window.m2_focus.ui.setup_seeker()
        window.m2_focus.ui.check_like()

        if ( $(document).find("#player").length ) {
            var $p = $(document).find("#player");
            $p.find(".cover_holder div").css("background-image", "url(\"" + cover + "\")");
            $p.find("._title").attr("href", displayData.link).text(displayData.title);
            if (displayData.sub_title) {
                $p.find("._sub_title").attr("href", displayData.sub_link || "").html(
                    preview_source ? ("<span class='preview'>" + window.lang.return("preview", { ucfirst: true }) + "</span>") : displayData.sub_title
                );
            }
            $p.find(".time.tot").text(window.general.duration_hr(displayData.duration));
            $p.find("#progress_range").val(0);
            $p.find(".progress_e").css("width", "0%");
            $p.find(".time.cur").text("00:00");
            $p.removeClass("repeat_on repeat_off muted unmuted preview_source newNav")
              .addClass(window.m2_focus.setting.get("repeat") ? "repeat_on" : "repeat_off")
              .addClass(window.m2_focus.setting.get("muted") ? "muted" : "unmuted")
              .addClass(preview_source ? "preview_source" : "")
              .addClass(newNaved ? "newNav" : "");
        } else {
            $("body").append(player);
        }
        window.m2_focus.ui.touch()

    },
    add_player_class: function( $class ){
        window.m2_focus.log( "Adding class " + $class );
        // window.m2_focus.ui.player_classes.push( $class );
        $(document).find("#player").addClass( $class );
    },
    hook: function () {

        var _ot = null;
        if ( window.bof._safeGet("m2_focus.sourceData.data.ot") )
        _ot = window.m2_focus.sourceData.data.ot;

        var _oh = null;
        if ( window.bof._safeGet("m2_focus.sourceData.data.hash") )
        _oh = window.m2_focus.sourceData.data.hash

        var hook = _ot + "_" + _oh;
        var status = window.m2_focus.status;

        window.m2_focus.log("Hooking sta:"+status)

        $(document).find("._play.muse_loading, ._play.muse_playing, ._play.muse_paused").find(".mdi")
            .removeClass("mdi-refresh mdi-pause mdi-play")
            .addClass("mdi-play");

        $(document).find(".muse_loading, .muse_playing, .muse_paused, .muse_focused")
            .removeClass("muse_loading muse_playing muse_paused muse_focused");

        $(document).find(".item.bof_" + hook).removeClass("muse_loading muse_playing muse_paused").addClass("muse_" + status + " muse_focused");

        $(document).find("._play.bof_" + hook)
            .removeClass("muse_loading muse_playing muse_paused muse_focused").addClass("muse_" + status + " muse_focused")
            .find(".mdi").removeClass("mdi-play mdi-pause mdi-refresh").addClass(status == "loading" ? "mdi-refresh" : (status != "playing" ? "mdi-play" : "mdi-pause"))
            .parents("._play").find("._t").text(status == "loading" ? "loading" : (status != "playing" ? "play" : "pause"));

        $(document).find(".item.bof_" + hook + " ._play")
            .removeClass("muse_loading muse_playing muse_paused muse_focused").addClass("muse_" + status + " muse_focused")
            .find(".mdi").removeClass("mdi-play mdi-pause mdi-refresh").addClass(status == "loading" ? "mdi-refresh" : (status != "playing" ? "mdi-play" : "mdi-pause"))
            .parents("._play").find("._t").text(status == "loading" ? "loading" : (status != "playing" ? "play" : "pause"));

        $(document).find("#player .controls_wrapper .control.play .mdi")
            .removeClass("mdi-refresh mdi-play mdi-pause")
            .addClass( status == "loading" ? "mdi-refresh" : ( status != "playing" ? "mdi-play" : "mdi-pause" ) );

        $(document).find("#player").removeClass( "paused playing loading stopped error canplay loaded played" ).addClass( status );

        $(document).find("#players")
            .toggleClass( "hp_paused", status != "playing" )
            .toggleClass( "hp_stopped", status == "paused" || status == "ended" || status == "stopped" || status == "canplay" )
            .find(".hp_play .mdi")
            .removeClass("mdi-play mdi-pause mdi-refresh")
            .addClass( status == "loading" ? "mdi-refresh" : ( status != "playing" ? "mdi-play" : "mdi-pause" ) );

        $(document).find(".queue .data_wrapper .list ._items .item.active_que").removeClass("active_que");
        var activeQue = window.m2_queue.active.get();
        if ( activeQue ){
            $(document).find(".queue .data_wrapper .list ._items .item#bof_queue_"+activeQue.data.ID).addClass("active_que");
        }

    },
    check_like: function(){

        if ( window.m2.isEmbed() )
            return;
        
        if ( window.m2_focus.ui.check_like_xhr ){
            try {
                window.m2_focus.ui.check_like_xhr.abort()
            } catch( err ){}
        }

        if ( !window.m2_queue.active.get() ? true : ( !window.m2_queue.active.get().data ? true : !window.m2_queue.active.get().data.ot ) )
        return

        window.m2_focus.check_like_xhr = window.becli.exe({
            endpoint: "muse_check_focus_status",
            liquid: true,
            ID: "check_liker_muse",
            post: {
                object_type: window.m2_queue.active.get().data.ot,
                object_hash: window.m2_queue.active.get().data.hash
            },
            callBack: function( sta, data ){
                $(document).find("#player .buttons_wrapper .button.muse_like_handler").removeClass("loading")
                if ( sta ? data.liked : false ){
                    $(document).find("#player .buttons_wrapper .button.muse_like_handler").addClass("liked")
                    $(document).find("#player .buttons_wrapper .button.muse_like_handler span.mdi").removeClass("mdi-heart-outline").addClass("mdi-heart")
                } else {
                    $(document).find("#player .buttons_wrapper .button.muse_like_handler").addClass("unliked")
                }
            },
            skip_apk: true
        }).client

    },
    openAnime: function(){

        if ( !(window.app?window.app.config.mobile:false) )
            return;

        if ( $("body").hasClass("queue_hide") )
            return;

        window.m2_queue.ui.previewFocus( true );
        var maxHeight = $(window).height() - (0+60);
        var maxJumps = maxHeight / 50;
        for ( var $i=1; $i<=Math.ceil(maxJumps); $i++ ){
            window.m2_focus.ui.openAnimeI(($i*50)>maxHeight?maxHeight:$i*50);
        }

    },
    openAnimeI: function($i){

        setTimeout( function(){
            window.m2_focus.ui.exeAnime( $i, true )
        }, $i/3 )

    },
    resetAnime: function(){

        if ( !(window.app?window.app.config.mobile:false) )
            return;

        window.m2_focus.log("Resetting Anime")

        $(document).find("#player").css("background-color","")
        $(document).find("body.mobile #player > div").css("opacity","")
        $(document).find("#player").css("right","").css("left","")
        $(document).find("#player").css("width","");
        $(document).find("#player").css("padding-right","").css("padding-left","");
        $(document).find("#player").css("height", (window.app?window.app.config.mobile:false) ? "60px" : "75px").css("transform", "none")
        $(document).find(".queue").removeClass("pulling").css( "top", "120vh" )
        $(document).find(".sidebar > div").css("opacity","")
        $(document).find("#player").css("border-color","")
        $(document).find("#player").css("bottom","")
        $(document).find("#player").css("border-radius","")
        $(document).find("body.mobile.que_active #player.newNav").removeClass("newNav")

        $("body.mobile .sidebar").css("--sidebar_bg_opacity", "")
        $(document).find(".sidebar").css("background","").css("height","").css("padding-top","")


    },
    exeAnime: function( _di, $system ){

        if ( !(window.app?window.app.config.mobile:false) )
            return;

        var mh = $(window).height() - (0+60);

        if ( _di > mh )
            _di = mh;

        if ( _di >= mh && $system === true ){
            window.m2_queue.ui.build(
                $("#player").offset().top,
                true
            )
        }

        var _h = (window.app?window.app.config.mobile:false) ? 60 : 75;
        var _d = _di + _h;
        var _nh = _d > _h ? _d : _h;

        if (_di > 70) {
            var _aah = _di - 70;
            if (_aah > 0) {
                var _aah2 = _aah / 5
                if (_aah2 > 75)
                    _aah2 = 75
                _nh = _nh + (_aah2)
            }

        }

        $(document).find("#player").css("height", _nh + "px");
        $(document).find(".queue").addClass("pulling").css( "top", $(document).find("#player").offset().top + "px" )

        var _maxDistanceForBgChange = 20;
        var _bgVar = $(document).find("#player").hasClass("colored") ? "player_bg_dyna" : "player_bg";
        if ( _bgVar == "player_bg_dyna" )
            _maxDistanceForBgChange = 1 

        var _maxDis_bg = ( _di / _maxDistanceForBgChange )
        _maxDis_bg = _maxDis_bg > 1 ? 1 : _maxDis_bg

        $(document).find("#player").css("background-color","rgba(var(--"+_bgVar+"),"+_maxDis_bg+")" )
        $(document).find("#player").css("border-color","rgba(var(--"+_bgVar+"),"+_maxDis_bg+")" )


        var _borderRadi = 10 - ( _di / 10 );
        _borderRadi = _borderRadi < 0 ? 0 : _borderRadi;
        $(document).find("#player").css("border-radius",_borderRadi+"px")
        
        var _sidebarBgD = _di / 40
        _sidebarBgD = _sidebarBgD > 1 ? 1 : _sidebarBgD;

        /*$(document).find(".sidebar").css("height","75px")
        .css("background","rgba( var(--player_bg), "+_sidebarBgD+") !important")
        .css("padding-top","5px")*/

        var _recudeSideOpa = _di / 70;
        _recudeSideOpa = 1 - ( _recudeSideOpa > 1 ? 1 : _recudeSideOpa )
        $(document).find(".sidebar > div").css("opacity",_recudeSideOpa)

        var _dii = _di - 70

        if ( _dii > 0 ){

            var _maxDistanceForTitleFade = 100
            var _maxDis_di = ( _dii / _maxDistanceForTitleFade )
            _maxDis_di = 1 - ( _maxDis_di > 1 ? 1 : _maxDis_di );

            $(document).find("body.mobile #player > div").css("opacity",_maxDis_di)

            var _reducePad = _dii / 10;
            _reducePad = 10 - ( _reducePad > 10 ? 10 : _reducePad )

            var _increaseWid = _dii / 10;
            _increaseWid = _increaseWid > 10 ? 10 : _increaseWid;

            $(document).find("#player").css("right",_reducePad+"px").css("left",_reducePad+"px")
            $(document).find("#player").css("width","calc(100% - "+(_reducePad*2)+"px)");
            $(document).find("#player").css("padding-right",7+_increaseWid+"px").css("padding-left",7+_increaseWid+"px");

            var _redocePlayerBot = 0 - ( _dii/5 )
            _redocePlayerBot = _redocePlayerBot > 0 ? _redocePlayerBot : 0 

            $(document).find("#player").css("bottom",_redocePlayerBot+"px")

        }

    },
    touch: function () {

        window.m2_focus.log( "UI.Touch.ini" );

        // $(document).find("#player").css("--preview_scroll_top",$(document).find(".queue").scrollTop()+"px")

        if (window.m2.isEmbed())
            return;

        if (!(window.app?window.app.config.mobile:false))
            return;

        if ( $("body").hasClass("queue_hide") )
            return;

        if (!document.querySelector("#player .touch_sensitive") ? true : document.querySelectorAll("#player .touch_sensitive").length == 0){
            return;
        }

        if ( window.m2_focus.ui.hammer_mc ){
            window.m2_focus.ui.hammer_mc.destroy();
            window.m2_focus.ui.hammer_mc = false;
        }

        var mc = new Hammer(document.querySelector("#player .touch_sensitive"));
        window.m2_focus.ui.hammer_mc = mc;

        mc.add(new Hammer.Pan({
            direction: Hammer.DIRECTION_ALL,
            threshold: 0
        }));

        var Direction = null;
        var Do = false;

        mc.on('pan', function (e) {

            window.m2_focus.log( "UI.Touch.onPan" );

            if (!Direction) {

                if (e.deltaY < -1) {
                    Direction = "top";
                } else if (e.deltaX < -6) {
                    Direction = "left"
                } else if (e.deltaX > 6) {
                    Direction = "right"
                }

            }
            if (Direction) {

                if (Direction == "top") {
                    if ( e.deltaY < 0 ){
                        var _di = Math.abs( e.deltaY );
                        if ( _di < 30 ){
                            window.m2_queue.ui.previewFocus( true );
                        }
                        window.m2_focus.ui.exeAnime( _di );
                    } else {
                        window.m2_focus.ui.resetAnime();
                    }
                }
                else {

                    if (Math.abs(e.deltaX) < 100) {
                        $(document).find("#player").css("transform", "translateX(" + e.deltaX + "px)")
                    } else {
                        // next // pre
                        if (Direction == "left")
                            Do = "next";
                        else
                            Do = "prev";
                    }

                }

            }
            if (e.isFinal === true) {

                if (Do == "next"){
                    window.m2.user.control.next();
                }
                else if (Do == "prev"){
                    window.m2.user.control.prev();
                }
                else if (Direction == "top" && Math.abs(e.deltaY) > 100){
                    window.m2_focus.log( "UI.Touch.onPan: Build" );
                    window.m2_queue.ui.build(
                        $("#player").offset().top,
                        true
                    )
                }
                else if (Direction == "top" ){
                    window.m2_focus.ui.resetAnime()
                }

                Direction = null;
                Do = null;

            }

        });

    }

}

window.m2_focus.recorder = {

    _played: 0,
    timer: null,
    start: function(){
        window.m2_focus.recorder.reset()
        window.m2_focus.recorder.exe()
    },
    stop: function(){
        window.m2_focus.recorder.reset()
    },
    reset: function(){
        if ( window.m2_focus.recorder.timer ){
            clearTimeout( window.m2_focus.recorder.timer )
            window.m2_focus.recorder.timer = null
        }
        window.m2_focus.recorder._played = 0
    },
    exe: function(){

        if ( window.m2_focus.getStatus() == "playing" ){
            window.m2_focus.recorder._played = window.m2_focus.recorder._played + 1
        }

        if ( window.m2_focus.recorder._played > window.m2.config.get("record_threshold") ){
            window.m2_focus.recorder.record()
        }
        else {
            window.m2_focus.recorder.timer = setTimeout( window.m2_focus.recorder.exe, 1000 )
        }

    },
    record: function () {

        window.becli.exe({
            endpoint: "muse_record",
            liquid: true,
            post: {
                object_type: window.m2_queue.active.get().data.ot,
                object_hash: window.m2_queue.active.get().data.hash
            }
        });

        this.reset()

    },

    report_source: function () {

        var promise = $.Deferred()
        window.m2_focus.report_promise = promise

        if ( window.m2_queue.active.get() ){
            window.becli.exe({
                liquid: true,
                ID: "muse_report",
                endpoint: "muse_report",
                post: {
                    data: JSON.stringify(window.m2_queue.active.get().data),
                    source: JSON.stringify(window.m2_queue.active.get().source),
                },
                callBack: function (sta, data) {
                    if (sta && data ? ( data.rep ? data.rep.new_youtube_id : false ) : false) {
                        promise.resolve({
                            youtube_id: data.rep.new_youtube_id
                        })
                    } else {
                        promise.resolve()
                    }
                }
            })
        } else {
            promise.resolve();
        }

        return promise;

    }

}

window.m2_focus.gcast = {

    client: null,

    log: function( text, level, ID ){
        window.m2_focus.log( "GCast " + text, level, ID )
    },
    isConnected: function($isActive){

        if ( !window.m2_focus.gcast.isLoaded() )
            return false;

        if ( !window.m2c_gcast_cache )
            return false;

        if ( !window.m2c_gcast_cache.client )
            return false;

        if ( !window.m2c_gcast_cache.client.connected )
            return false;

        if ( !window.m2c_gcast_cache.client.device )
            return false;

        if ( window.m2c_gcast_cache.client.device == "Chromecast" )
            return false;

        if ( $isActive === true ){
        }

        return true;

    },
    isLoaded: function(){

        if ( window.m2_focus.gcast.client )
            return true;

        if ( window.bof.loaded_extensions.includes("cast.min.js") || typeof(Castjs) == "function" )
            return true;

        return false;

    },
    load: function ( $promise ) {

        $promise = $promise ? $promise : $.Deferred()

        window.bof._loadExtension({
            name: "cast.min.js",
            path: "cast.min.js",
            base: "https://cdnjs.cloudflare.com/ajax/libs/castjs/5.3.0/",
            dir: "",
            skipNameCheck: true,
            version: false
        }).done(function () {
            setTimeout(function () {
                $promise.resolve();
            }, 250)
        }).fail(function () {
            $promise.reject("Loading cast.js failed");
        });

        return $promise;

    },
    connect: function () {

        var callBack = $.Deferred();
        new m2c_gcast( 
            {
                autoplay: true,
                volume: 100,
            }, 
            {
                title: "Connected",
                sub_title: "Ok! Connected",
            }, 
            {
                type: "free",
                address: $_bof_config.bof_assets_address + "other/connected.mp3"
            },
            function(id,event){
                console.log(event);
            },
            callBack,
            window._g.uniqid(10)
        );

        return callBack

    },
    disconnect: function(){
        window.m2c_gcast_cache.client.disconnect()
        window.m2c_gcast_cache.client = null;
    },
    deviceName: function(){
        if ( !window.m2c_gcast_cache.client )
            return
        if ( !window.m2c_gcast_cache.client.device )
            return;
        return window.m2c_gcast_cache.client.device;
    }

}

window.m2_focus.setting = (function () {

    var _i = {

        muted: false,
        volume: 100,
        repeat: false,
        infinite: true,
        speed: 1,

        type: "audio_quality_2",
        ini: null,

        thingie: null,
        thingie_has: false,
        
    };

    return {
        get: function (key) {
            return _i[key];
        },
        getAll: function(){
            return _i
        },
        set: function (key, value) {
            _i[key] = value;
            window.m2_focus.setting.save();
        },
        load: function () {
            var savedSetting = window.cache.get("muse_setting", false);
            if (savedSetting) {
                savedSetting = JSON.parse(savedSetting);
                _i = savedSetting;
                if ( savedSetting.muted ){
                    $(document).find("#player").removeClass("unmuted").addClass("muted");
                }
            }
        },
        save: function () {
            window.cache.set(
                "muse_setting",
                JSON.stringify(_i)
            );
        },
        unset: function() {
            window.cache.remove("muse_setting")
        }
    };

})()