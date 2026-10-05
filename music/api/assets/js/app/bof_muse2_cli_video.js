"use strict";

class m2c_video {
    constructor( exeArgs, displayData, sourceData, eventHandler, callBack, ID ) {

        this.ID = ID
        this.exeArgs = exeArgs
        this.sourceData = sourceData
        this.eventHandler = eventHandler ? eventHandler : function( ID, eventName ){
            this.log( "UncatchedEvent: " + eventName, 4  )
        };
        this.callBack = callBack ? callBack : $.Deferred();
        window.m2c_video_cache.id = this.ID;

        this.exe = {
            pre: () => {

                this.log("Pre.Start", 4)
                var promise = $.Deferred();
                window.bof._loadExtension({
                    name: "video.min.js",
                    path: "video.min.js",
                    base: "https://vjs.zencdn.net/8.18.1/",
                    dir: "",
                    skipNameCheck: true,
                    version: false
                }).done(function () {
                    promise.resolve();
                }).fail(function () {
                    promise.reject({
                        hard: true,
                        message: "Loading videojsjs failed"
                    });
                });

                $(document).find("#players.hls_audio").removeClass("hls_audio")
                window.ui.body.removeClass( "hls_audio", true );

                if (sourceData.type ? sourceData.type == "audio" : false) {
                    $(document).find("#players").addClass("hls_audio")
                    window.ui.body.addClass( "hls_audio", true );
                    if (window.m2.isEmbed()) {
                        window.ui.body.addClass("muse_audio_active", true);
                        window.ui.body.removeClass("muse_video_active", true);
                    }
                }

                return promise;

            },
            run: () => {

                this.log("Run.Start", 4)

                $(document).find("#videojs").html("<video playsinline crossorigin='anonymous'></video>");

                var runPromise = $.Deferred();

                var player = $(document).find("#videojs video")[0];
                var videojs = null;
                var videojs_config = {
                    debug: false,
                    // autoplay: exeArgs.autoplay,
                    autoplay: false,
                    aspectRatio: '16:9',
                    loop: false,
                    repeat: false,
                };

                window.m2c_video_cache.error = null

                player.addEventListener("error", (event) => {
                    window.m2c_video_cache.error = event.target.error.message
                })

                if (sourceData.hls) {
                    this.log("HLS Start");
                    videojs_config["html5"] = {
                        vhs: {},
                    };
                    videojs = window.videojs(player, videojs_config);
                    videojs.src({
                        src: sourceData.address,
                        type: 'application/x-mpegURL',
                    });
                    window.m2_focus.setStatus("loaded");
                }
                else {
                    this.log("NoHLS Start");
                    let newSource = document.createElement('source');
                    newSource.src = sourceData.address;
                    if (sourceData.format) newSource.type = sourceData.format;
                    player.appendChild(newSource);
                    videojs = window.videojs(player, videojs_config);
                    window.m2_focus.setStatus("loaded");
                }

                if (!videojs) {

                    if (!window.m2.isEmbed())
                    window.app.becli.alert(false, window.lang.return("failed"));

                    window.m2c_video_cache.cli.log("Player ini failed");

                }
                else {

                    window.m2c_video_cache.videojs = videojs;

                    videojs.on('ready', function (event) {
                        if ( window.m2.config.get("hotfix") ){
                            window.m2c_video_cache.cli.event("canplay");
                            window.m2c_video_cache.cli.event("loadeddata");
                            runPromise.resolve();
                        }
                    });
                    videojs.on('play', function () {
                        window.m2c_video_cache.cli.event("playing");
                    });
                    videojs.on('playing', function () {
                        window.m2c_video_cache.cli.event("playing");
                    });
                    videojs.on('pause', function () {
                        window.m2c_video_cache.cli.event("paused");
                    });
                    videojs.on('seeking', function () {
                        window.m2c_video_cache.cli.event("seeked");
                    });
                    videojs.on('seeked', function () {
                        window.m2c_video_cache.cli.event("playing");
                    });
                    videojs.on('ended', function () {
                        window.m2c_video_cache.cli.event("ended");
                    });
                    videojs.on('waiting', function () {
                        window.m2c_video_cache.cli.event("loading");
                    });
                    videojs.on('canplay', function () {
                        window.m2c_video_cache.cli.event("canplay");
                        window.m2c_video_cache.cli.event("loadeddata");
                        runPromise.resolve();
                    });
                    videojs.on('canplaythrough', function () {
                    });
                    videojs.on('error', function ($err) {
                        window.m2c_video_cache.cli.event("error",window.m2c_video_cache.error);
                    }); 

                }

                runPromise.done(function(){

                    if ( exeArgs.muted ){
                        window.m2c_video_cache.videojs.volume( 0 );
                    }
                    else if ( exeArgs.volume ){
                        window.m2c_video_cache.videojs.volume( exeArgs.volume );
                    }

                });

                return runPromise;

            },
            halt: () => {

                this.log("Halt", 1)

                try {
                    window.m2c_video_cache.videojs.pause();
                } catch( err ){}

                try {
                    window.m2c_video_cache.videojs.dispose();
                } catch( err ){}

            }
        }
        
        this.log = ( text, level ) => {
            window.m2.log( this.ID + ":m2c_video", text, level )
        }
        this.event = ( eventName, event ) => {

            this.log( "Event: " + eventName, 4  )

            if ( this.ID != window.m2c_video_cache.id ){
                this.log( "Event: " + eventName + " -> Failed. Different ID", 4  )
                return;
            }

            this.eventHandler( this.ID, eventName )

        }
        this.informer = ( hook ) => {

            if (ID != window.m2c_video_cache.id)
            return;

            if (!window.m2c_video_cache.cli)
            return;

            if ( !window.m2c_video_cache.videojs )
            return;

            if ( hook == "seek" ){
                return window.m2c_video_cache.videojs.currentTime()
            }
            else if ( hook == "duration" ){
                return window.m2c_video_cache.videojs.duration()
            }
            else if ( hook == "volume" ){
                return window.m2c_video_cache.videojs.volume()
            }
            else if ( hook == "buffered" ){
                return Math.round( window.m2c_video_cache.videojs.bufferedPercent() * 100 )
            }

        }
        this.controller = ( action, data ) => {

            if ( ID != window.m2c_video_cache.id )
            return;

            if ( !window.m2c_video_cache.cli )
            return;

            if ( !window.m2c_video_cache.videojs )
            return;

            this.log( "Controller -> " + action );

            if ( action == "play" ){
                window.m2c_video_cache.videojs.play();
            }
            else if ( action == "pause" || action == "stop" ){
                window.m2c_video_cache.videojs.pause();
            }
            else if ( action == "stop" ){
                window.m2c_video_cache.videojs.pause();
                window.m2c_video_cache.videojs.dispose();
            }
            else if ( action == "set_volume" || action == "volume" ){
                window.m2c_video_cache.videojs.volume( parseFloat( data/100 ) )
                if ( data > 0 )
                    window.m2c_video_cache.videojs.muted(false)
                else
                    window.m2c_video_cache.videojs.muted(true)
    
            }
            else if ( action == "set_speed" || action == "speed" ){
                window.m2c_video_cache.videojs.playbackRate( parseFloat( data ) ) 
            }
            else if ( action == "seek" ){
                window.m2c_video_cache.videojs.currentTime( parseFloat( window.m2c_video_cache.videojs.duration() * ( data / 100 ) ) )
            }  
            else if ( action == "full_screen" ){
                var _p = document.getElementById("players");
                if ( _p && _p.requestFullscreen )
                    _p.requestFullscreen();
                else
                    window.m2c_video_cache.videojs.requestFullscreen()
            }

        }
        this.cleanup = () => {

            this.log("Cleanup")
            this.exe.halt();
            window.m2c_video_cache.cli = null;
            window.m2c_video_cache.id = null;

        }

        window.m2c_video_cache.cli = this;

        // Initialize the process
        this.exe.pre().done(() => {
            this.log("Process.pre -> Done");
            this.exe.run().done(() => {
                this.log("Process.run -> Done");
                this.callBack.resolve();
            }).fail((err) => {
                this.log("Process.run -> Failed");
                this.callBack.reject({
                    message: "Running cli failed",
                    skip: true
                });
            });
        }).fail((err) => {
            this.log("Process.pre -> Failed");
            this.callBack.reject({
                message: "Preparing cli failed",
                skip: true
            });
        });  

    }

}

window.m2c_video_cache = {
    cli: null,
    videojs: null,
    id: null
};
