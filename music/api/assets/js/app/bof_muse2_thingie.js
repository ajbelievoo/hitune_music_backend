"use strict";

window.m2_thingie = {

    log: function (text, level) {
        window.m2.log("m2_thingie", text, level)
    },

    has: function(){

        var has = false

        if ( window.m2.isEmbed() ){
            has = false
        }
        else if ( window.m2_thingie.cache ){
            has = true
        }
        else if ( window.m2_focus.setting.get("thingie_has") ) {
            has = true
            window.m2_thingie.check()
        }
        else {
            has = false
        }

        window.m2_thingie.log( "has: " + (has?"yes":"no") )

        return has;

    },

    get: function(){
        return window.m2_thingie.cache[1]
    },

    getType: function(){
        return window.m2_thingie.cache[0]
    },

    check: function () {

        var promise = $.Deferred()
        
        if ( window.m2.isEmbed() ){
            promise.reject()
            return promise
        }

        if ( window.m2_thingie.cache ){
            promise.resolve()
            return promise
        }

        if ( window.m2_thingie.playing ){
            promise.reject()
            return promise
        }

        var _mIni = null;
        var _mCheck = window.m2_focus.setting.get("check");
        var _mThingie = window.m2_focus.setting.get("thingie");
        var _mThingie_interval = window.m2.config.get("thingie_interval");

        if (window.m2_focus.setting.get("ini"))
            _mIni = window.m2_focus.setting.get("ini");

        else {
            _mIni = window._g._mt();
            window.m2_focus.setting.set("ini", _mIni);
        }

        var _m = _mIni;
        if (_mThingie)
        _m = _mThingie;

        window.m2_thingie.log("thingie.ini: " + _mIni + " - " + ((window._g._mt() - _mIni) / 1000));
        window.m2_thingie.log("thingie.last: " + _mThingie + " - " + ((window._g._mt() - _mThingie) / 1000));
        window.m2_thingie.log("thingie.check: " + _mCheck + " - " + ((window._g._mt() - _mCheck) / 1000));
        window.m2_thingie.log("thingie.interval: " + _mThingie_interval);

        if (_mThingie_interval === -1 ? window._g._mt() - _mCheck < (1 * 60 * 1000) : false) {
            window.m2_thingie.log("thingie.interval: disabled. return",3);
            promise.reject()
            return promise
        }

        if ( window.m2.config.get("thingie_offset") ? ( window._g._mt() - _mIni < window.m2.config.get("thingie_offset") * 60 * 1000 ) : false ) {
            window.m2_thingie.log("thingie.ini: fresh. return",3);
            promise.reject()
            return promise
        }

        if (_mThingie && _mThingie_interval ? window._g._mt() - _mThingie < _mThingie_interval * 60 * 1000 : false) {
            window.m2_thingie.log("thingie.last: fresh. return",3);
            promise.reject()
            return promise
        }

        // Never ad-gate the very first track of a session — persisted
        // ini/thingie/check timestamps from an old session must not let an ad
        // fire before (or instead of) the user's first song.
        if ( !window.m2_focus._played_once ){
            window.m2_thingie.log("thingie: no ads before first song. return",3);
            promise.reject()
            return promise
        }

        window.becli.exe({
            liquid: true,
            endpoint: "get_the_thingie",
            post: {
                placement: "bof_AUDI0"
            },
            callBack: function (sta, data) {
                window.m2_focus.setting.set("check", window._g._mt());
                if (!sta) {
                    _mThingie_interval = -1;
                    window.m2_thingie.log("thingie.get: none. return",3);
                    window.m2_focus.setting.set("thingie_has", 0);
                    promise.reject()
                } else {
                    window.m2_focus.setting.set("thingie_has", 1);
                    window.m2_thingie.cache = [ data.thingie_type, data.thingie ];
                    window.m2_thingie.log("thingie.get: gotOne!",3);
                    promise.resolve()
                }
            }
        });

        return promise

    },

    isPlaying: function(){
        return window.m2_thingie.playing
    },

    play: function (autoplay) {

        var thingiePlayPromise = $.Deferred()

        if ( window.m2_thingie.playing ){
            thingiePlayPromise.resolve()
            window.m2_thingie.log("Already playing")
            return thingiePlayPromise
        }

        var ID = window._g.uniqid(10);
        var adData = window.m2_thingie.get()
        var adType = window.m2_thingie.getType()
        var cli_promise = $.Deferred()

        if ( window.m2.config.get("hotfix") ? ( window.m2.hotfix_inied ? !window.m2.hotfix_inied.includes( adType ) : true ) : false ){
            window.m2_thingie.log("Playing rejected. IOS hotfix. Users has not played " + adType + " source yet")
            thingiePlayPromise.reject()
            return thingiePlayPromise
        }

        // Capture the interrupted track's position so it can resume there after the ad
        try {
            var _dp = window.m2_focus.inform("duration"), _sp = window.m2_focus.inform("seek");
            $.when( _dp, _sp ).always(function( d, s ){
                if ( typeof d === "number" && d > 0 && typeof s === "number" && s > 0 )
                    window.m2_focus._pendingResume = Math.min( 99, s / d * 100 );
            });
        } catch(e){}

        window.m2_focus.cancelPreSetObject();
        window.m2_focus.cancelPreSet();
        window.m2_focus.cancelPreCli();
        window.m2_focus.ui.clear_seeker()

        window.m2_thingie.playing = autoplay;
        window.m2_thingie._capped = false;
        window.m2_thingie.ID = ID;

        var cli = new m2c(
            {
                autoplay: true,
                volume: 100,
                muted: false,
                speed: 1
            },
            {
                title: "Ads"
            },
            [
                adType,
                adData
            ],
            window.m2_thingie.eventHandler,
            cli_promise,
            ID
        )

        cli_promise
        .done(function () {
            window.m2_thingie.log("Initiating cli -> Success", 2, ID)
            window.m2_thingie.seeker = window.m2_thingie.seek(true)
            if ( autoplay ){
                window.m2_focus.setStatus("playing")
                window.m2_focus.ui.hook()
            } else {
                window.m2_focus.setStatus("paused")
                window.m2_focus.ui.hook()
            }
            if (!window.m2.config.get("queue_disable_auto"))
            window.m2_queue.ui.build()
            thingiePlayPromise.resolve()
            window.m2_thingie.log("CLI Running")
        })
        .fail(function (err) {
            window.m2_thingie.log("Initiating cli -> Failure -> " + err.message, 1, ID)
            window.m2_thingie.errorHandler(err)
            thingiePlayPromise.reject()
        })

        var _adUrl = ( adData.url && adData.url !== "null" && adData.url !== "undefined" ) ? adData.url : "#";
        var _adBanner = ( adData.banner && adData.banner !== "null" && adData.banner !== "undefined" ) ? adData.banner : ( window.config.web.address + "api/assets/images/icon_512.png" );
        $(document).find("#player").addClass("thingie_attached")
        $(document).find("#player.thingie_attached .controls_wrapper").after("<div class='thingie_indicator'><div><a href='"+_adUrl+"' target='_blank'>"+adData.title+"</a><a href='"+_adUrl+"' target='_blank'>"+window.lang.return( "sponsor", { ucfirst: true } )+"</a></div></div>")
        $(document).find("#player.thingie_attached .data_wrapper a").attr("href",_adUrl)
        $(document).find("#player .data_wrapper .cover_holder div").css("background-image","url('"+_adBanner+"')")
        window.m2_queue.ui.previewFocus()

        window.m2_thingie.cli = cli
        return thingiePlayPromise

    },

    seek: function($mother){

        if ( window.m2_thingie.cli ? window.m2_thingie.cli.informer : false ){
            var duration_p = window.m2_thingie.cli.informer("duration")
            var seek_p = window.m2_thingie.cli.informer("seek")
    
            duration_p.done(function(duration){
                seek_p.done(function(seek){

                    // Cap ad playback at thingie_max_seconds — self-promo files
                    // can be full-length tracks; a 2+ minute ad is bad UX.
                    var _maxAd = parseInt( window.m2.config.get("thingie_max_seconds") ) || 30;
                    if ( typeof seek === "number" && seek >= _maxAd && !window.m2_thingie._capped ){
                        window.m2_thingie._capped = true;
                        window.m2_thingie.log("Ad hit max duration (" + _maxAd + "s) -> ending", 1)
                        try {
                            window.m2_thingie.cli.controller("seek", 99)
                        } catch(e){
                            window.m2_thingie.cleanup()
                            window.m2_focus.setting.set( "thingie_has", 0 )
                            window.m2_queue.active.setFocus()
                        }
                        return
                    }

                    if (duration && seek) {

                        var dur_eff = Math.min(duration, _maxAd)
                        var remain = Math.round(dur_eff - seek)
                        $(document).find(".queue #preview .text_wrapper #thingie_skip ._rt").text(remain)

                        var offset_ratio = Math.round(dur_eff ? seek / dur_eff * 100 : 0);

                        $(document).find("#player .progress_bar .progress .progress_e").css("width", offset_ratio + "%")

                        var time_cur = window._g.duration_hr(seek);
                        if (seek && (seek > 0 || seek === 0 || seek === "0") && $(document).find("#player .progress_bar .progress .time.cur").text() != time_cur)
                            $(document).find("#player .progress_bar .progress .time.cur").text(time_cur);

                        var time_tot = window._g.duration_hr(dur_eff);
                        if (dur_eff && (dur_eff > 0) && $(document).find("#player .progress_bar .progress .time.tot").text() != time_tot)
                            $(document).find("#player .progress_bar .progress .time.tot").text(time_tot)

                    }

                    if ( seek > window.m2.config.get("thingie_skipable_threshold") ){
                        $(document).find(".queue #preview .text_wrapper #thingie_skip .btn.cant_skip").removeClass("cant_skip")
                    }

                })
            })
        }

        setTimeout(function(){
            window.m2_thingie.seek()
        },500)

    },

    skip: function(){

        if ( !window.m2.config.get("thingie_skipable") ){
            window.m2_thingie.log("Skipping failed. Skipping disabled",1)
            return
        }
        var seek_p = window.m2_thingie.cli.informer("seek") 
        seek_p.done(function(seek){

            if ( seek < window.m2.config.get("thingie_skipable_threshold") ){
                window.m2_thingie.log("Skipping failed. Threshold not reached",1)
                return
            }

            window.m2_thingie.cli.controller("seek",99)

        });

    },

    // Reset all ad state + strip the sponsor UI from the player.
    // Used by "ended", mid-ad "error" and init-failure paths so a dead ad
    // can never leave the player showing a stuck "Sponsor" banner.
    cleanup: function(){

        if ( window.m2_thingie.cli ){
            try { window.m2_thingie.cli.cleanup() } catch(e){}
            window.m2_thingie.cli = null
        }
        window.m2_thingie.playing = false
        window.m2_thingie._capped = false
        window.m2_thingie.cache = false
        clearTimeout( window.m2_thingie.seeker )
        window.m2_thingie.seeker = null
        $(document).find("#player").removeClass("thingie_attached")
        $(document).find("#player .thingie_indicator").remove()
        window.m2_queue.ui.previewFocus()

    },

    eventHandler: function( ID, event ){
        
        if ( ID != window.m2_thingie.ID )
            return;

        if ( event == "ended" ){
            window.m2_thingie.cleanup()
            window.m2_focus.setting.set( "thingie", window._g._mt() )
            window.m2_focus.setting.set( "thingie_has", 0 )
            setTimeout(function(){
                window.m2_queue.active.setFocus()
            },750)
        }
        else if ( event == "error" ){
            // Ad audio died mid-play — drop ad state and resume the song.
            window.m2_thingie.log( "Ad errored mid-play -> resuming track", 1 )
            window.m2_thingie.cleanup()
            window.m2_focus.setting.set( "thingie_has", 0 )
            setTimeout(function(){
                window.m2_queue.active.setFocus()
            },250)
        }
        window.m2_thingie.log( "eventHandler " + event, 1 )

    },

    errorHandler: function ( err ){
        window.m2_thingie.log( "errorHandler " + err.message, 1 )
        // Init failure: the song path already continues via the rejected
        // thingie_play_promise — only clear any half-attached ad UI/state.
        window.m2_thingie.cleanup()
        window.m2_focus.setting.set( "thingie_has", 0 )
    }

}
