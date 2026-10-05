"use strict";

class m2c {

    constructor( exeArgs, displayData, sourceArgs, eventHandler, callBack, ID ){

        this.ID = ID;
        this.exeArgs = exeArgs;
        this.sourceArgs = sourceArgs;
        this.displayData = displayData;
        this.callBack = callBack;
        this.icy_timer = null;
        this.icy_xhr = null;

        this.log = ( text, level ) => {
            window.m2.log( this.ID + ":m2c" , text, level )
        }

        var sub = false;
        var subType = this.sourceArgs[0];

        this.log("Initiating")
        window.m2_focus.setStatus( "loading" );
        window.m2_focus.ui.hook();

        $(window).trigger( "m2c_ini", [ ID, subType, displayData, sourceArgs ] )

        if ( subType == "youtube" ){
            this.log("Initiating YouTube's cli")
            sub = new m2c_youtube( 
                this.exeArgs, 
                displayData, 
                {
                    youtube_id: this.sourceArgs[1]["youtube_id"]
                },
                eventHandler,
                callBack,
                ID
            );
        }
        else if ( subType == "soundcloud" ){
            this.log("Initiating SoundCloud's cli")
            sub = new m2c_soundcloud( 
                this.exeArgs, 
                displayData, 
                {
                    soundcloud_id: this.sourceArgs[1]["soundcloud_id"] ? this.sourceArgs[1]["soundcloud_id"] : this.sourceArgs[1]["ID"]
                },
                eventHandler,
                callBack,
                ID
            );
        }
        else if ( subType == "video" ){
            this.log("Initiating Video's cli")
            sub = new m2c_video(
                this.exeArgs,
                displayData,
                this.sourceArgs[1],
                eventHandler,
                callBack,
                ID
            );
        }
        else if ( subType == "audio" ){
            this.log("Initiating Audio's cli")
            sub = new m2c_audio(
                this.exeArgs,
                displayData,
                this.sourceArgs[1],
                eventHandler,
                callBack,
                ID
            );
        }
        else if ( window["m2c_" + subType] ){
            this.log("Initiating "+subType+"'s cli")
            sub = new window[ "m2c_" + subType ]( 
                this.exeArgs, 
                displayData, 
                this.sourceArgs[1],
                eventHandler,
                callBack,
                ID
            );
        }
        else {
            callBack.reject({
                skip: true,
                hard: true,
                message: "Unkown muse type -> " + subType,
                display: false
            })
            return;
        }

        callBack.done(function(){

            console.log("Initiating "+subType+"'s cli finished")

            var hotfix = $.Deferred()
            if ( window.m2.config.get("hotfix") ?
              ( ( window.m2.hotfix_inied ? !window.m2.hotfix_inied.includes( subType ) : true ) && subType != "soundcloud" )
            : false ){

                console.log("iPhone/Safari HOTFIX! -> "+subType+" -> Mute -> Play -> Pause. Start in mute. Fuck u Apple :)");

                if ( !window.m2.hotfix_inied )
                window.m2.hotfix_inied = []
                window.m2.hotfix_inied.push( subType )

                sub.controller("volume",0)
                setTimeout(function(){
                    sub.controller("volume",0)
                    setTimeout(function(){
                        sub.controller("play")
                        setTimeout(function(){
                            sub.controller("pause")
                            setTimeout(function(){
                                hotfix.resolve(true)
                            },75)
                        },75)
                    },75)
                },75)
                
            } else {
                hotfix.resolve(false)
            }

            hotfix.done(function(hotfixed){

                if ( exeArgs.autoplay ){
                    console.log("Autoplay -> Yeah")
                    sub.controller("play")
                } else {
                    console.log("Autoplay -> Nah")
                }
    
                if ( exeArgs.speed ){
                    sub.controller("speed",exeArgs.speed)
                }

                if ( !hotfixed ){
                    sub.controller("volume",exeArgs.muted?0:exeArgs.volume)
                }
                else {
                    $(document).find("#player").addClass("hotFixed")
                    window.m2.user.control.mute()
                }
    
                window.ui.body.addClass("muse_player_active", true);
                window.ui.body.addClass("muse_" + subType + "_active", true);
                $(document).find("#players").removeClass(["video", "audio", "soundcloud", "youtube", "vimeo"]).addClass(subType);

                if (window.m2_queue && window.m2_queue.ui && window.m2_queue.ui.player_place === "queue") {
                    setTimeout(function () {
                        window.m2_queue.ui.move_frame("queue", true);
                    }, 50);
                }

            })

        }).fail(function(err){
            console.log("Initiating "+subType+"'s cli failed")
            console.log(err)
        })

        this.subType = this.sourceArgs[0];
        this.sub = sub;
        this.controller = sub.controller;

        this.informer = ( hook ) => {

            var promise = $.Deferred();
    
            try {
                var get = sub.informer(hook);
                if (typeof get == 'object') {
                    get
                    .done(function (data) {
                        promise.resolve(data);
                    })
                    .fail(function (err) {
                        promise.reject(err);
                    })
                } else {
                    promise.resolve(Math.round(get));
                }
            } catch (err) {
                promise.reject({
                    message: "inform failed"
                })
            }
    
            return promise;
    
        }

    }
    icy_reader(){

        window.m2_focus.cli.log("Icy Reader")

        try {
            if ( window.m2_focus.cli.icy_timer )
                clearTimeout( window.m2_focus.cli.icy_timer )
            if ( window.m2_focus.cli.icy_xhr )
                window.m2_focus.cli.icy_xhr.abort()
        } catch( err ){}

        window.m2_focus.cli.icy_xhr = window.becli.exe({
            endpoint: "muse_stream_heads",
            post: {
                object: window.m2_focus.cli.displayData.ot,
                hash: window.m2_focus.cli.displayData.hash,
                ID: window.m2_focus.cli.displayData.ID,
                url: window.m2_focus.cli.sourceArgs[1]["address"] ? window.m2_focus.cli.sourceArgs[1]["address"] : null
            },
            callBack: function (sta, data) {
                if ( sta ? ( data.name ) : false ){
                    $(document).find("#player .data_wrapper .source_data ._title").text(data.desc)
                    $(document).find("#player .data_wrapper .source_data ._sub_title").html("<span style='color:rgba(var(--c_orange))'>" + data.name + "</span>")
                    $(document).find(".queue #preview .text_wrapper>a.title").text(data.desc)
                    $(document).find(".queue #preview .text_wrapper>a.sub_title").html("<span style='color:rgba(var(--c_orange))'>" + data.name + "</span>")
                }
            },
            skip_apk: true
        }).client

        window.m2_focus.cli.icy_timer = setTimeout(function () {
            window.m2_focus.cli.icy_reader()
        }, 15000);

    }
    cleanup(){

        this.log("Cleanup " + this.subType)

        $(window).trigger( "m2c_end", [ this.ID, this.subType, this.displayData, this.sourceArgs ] )

        window.ui.body.removeClass("muse_player_active", true);
        window.ui.body.removeClass("muse_" + this.subType + "_active", true);

        try {
            this.sub.cleanup()
        } catch( err ){}
        try {
            this.callBack.reject({
                skip: false,
                message: "Halted",
                display: false
            })
        } catch( err ){}

        this.sub = null;
        this.controller = null;
        this.informer = null;
        this.ID = null;
        this.subType = null;
        this.sourceArgs = null;
        this.displayData = null;

        if ( this.icy_timer )
        clearTimeout( this.icy_timer );

        if ( this.icy_xhr )
        this.icy_xhr.abort()

        this.icy_timer = null;

    }

}