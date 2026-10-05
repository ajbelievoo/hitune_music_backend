"use strict";

class m2c_youtube {
    constructor( exeArgs, displayData, sourceData, eventHandler, callBack, ID ) {

        this.ID = ID
        this.exeArgs = exeArgs
        this.sourceData = sourceData
        this.eventHandler = eventHandler ? eventHandler : function( ID, eventName ){
            this.log( "UncatchedEvent: " + eventName, 4  )
        };
        this.callBack = callBack ? callBack : $.Deferred();
        window.m2c_youtube_cache.id = this.ID;

        this.exe = {
            pre: () => {

                this.log( "Pre.Start", 4  )

                var promise = $.Deferred();
                var assetPromise = $.Deferred();

                if ( !this.sourceData.youtube_id ){

                    this.log( "Pre.Start.Failed -> NO youtube_id!", 1  )
                    promise.reject({
                        hard: false,
                        message: "No YouTube ID given"
                    })
                    return promise;

                }

                window.bof._loadExtension({
                    name: "iframe_api",
                    path: "iframe_api",
                    base: "https://www.youtube.com/",
                    dir: "",
                    skipNameCheck: true,
                    cache: false,
                    version: false
                }).done(() => {
                    assetPromise.resolve();
                }).fail(() => {
                    assetPromise.reject({
                        hard: true,
                        message: "Loading Youtube iFrame JS failed"
                    });
                });

                $.when(
                    assetPromise, 
                    window.m2c_youtube_cache.iframe_promise
                ).done(() => {
                    promise.resolve();
                    this.log( "Pre.Done", 3 )
                }).fail((err) => {
                    promise.reject(err);
                    this.log( "Pre.Failed", 1 )
                });

                return promise;

            },
            run: () => {

                this.log( "Run.Start", 4 )

                var runPromise = null;

                if (!window.m2c_youtube_cache.iframe) {
                    this.log( "Run.Start.First", 4 )
                    runPromise = this.exe.runFirst();
                } else {
                    this.log( "Run.Start.Secondary", 4 )
                    runPromise = this.exe.runSecondary();
                }

                return runPromise;

            },
            runFirst: () => {
                
                var promise = $.Deferred();

                var ytplayer = new YT.Player('youtube', {
                    width: "100%",
                    height: "100%",
                    videoId: this.sourceData.youtube_id,
                    events: {
                        onReady: (event) => {
                            if (window.m2c_youtube_cache.cli)
                            window.m2c_youtube_cache.cli.event("onReady", event);
                            promise.resolve();
                        },
                        onStateChange: (event) => {
                            if ( window.m2c_youtube_cache.cli )
                            window.m2c_youtube_cache.cli.event("onStateChange",event);
                        },
                        onError: (event) => {
                            console.log(event);
                            if ( window.m2c_youtube_cache.cli )
                            window.m2c_youtube_cache.cli.event("onError",event);
                        },
                        onApiChange: (event) => {
                            if ( window.m2c_youtube_cache.cli )
                            window.m2c_youtube_cache.cli.event("onApiChange",event);
                        },
                    },
                    playerVars: {
                        autoplay: false,
                        rel: 0,
                        showinfo: 0,
                        disablekb: 1,
                        controls: 0,
                        modestbranding: 1,
                        iv_load_policy: 3,
                        playsinline: 1,
                        host: window.location.protocol + '//www.youtube.com',
                    },
                });

                window.m2c_youtube_cache.iframe = ytplayer
                return promise;

            },
            runSecondary: () => {

                window.m2c_youtube_cache.iframe.loadVideoById(this.sourceData.youtube_id);
                window.m2c_youtube_cache.iframe.pauseVideo();

                /*if ( this.exeArgs.autoplay )
                window.m2c_youtube_cache.iframe.playVideo();

                else
                window.m2c_youtube_cache.iframe.pauseVideo();*/

                return $.Deferred().resolve();

            },
            halt: () => {

                this.log( "Halt", 1 )
                this.halted = true

                try {
                    window.m2c_youtube_cache.iframe.stopVideo();
                } catch( $err ){}

            }
        }
        
        this.log = ( text, level ) => {
            window.m2.log( this.ID + ":m2c_youtube", text, level )
        }
        this.event = ( eventName, event ) => {
            
            this.log( "Event: " + eventName, 4  )

            if ( this.ID != window.m2c_youtube_cache.id ){
                this.log( "Event: " + eventName + " -> Failed. Different ID", 4  )
                return;
            }

            if ( eventName == "onReady" ) {
                this.eventHandler(this.ID,"loadeddata")
            }
            else if ( eventName == "onStateChange" ){
                if ( event.data == 1 ){
                    this.log( "Event: onStateChange.Playing", 4  )
                    this.eventHandler(this.ID,"playing")
                }
                else if ( event.data == 2 ){
                    this.log( "Event: onStateChange.Paused", 4  )
                    this.eventHandler(this.ID,"paused")
                }
                else if ( event.data == 3 ){
                    this.log( "Event: onStateChange.Loading", 4  )
                    this.eventHandler(this.ID,"loading")
                }
                else if ( event.data == 0 ){
                    this.log( "Event: onStateChange.Ended", 4  )
                    this.eventHandler(this.ID,"ended")
                }
            }
            else if ( eventName == "onError" ){
                this.eventHandler(this.ID,"error",event.data)
                console.log( event )
            }
            else if ( eventName == "onApiChange" ){
                console.log( event )
            }
            
        }
        this.informer = ( hook ) => {

            if (ID != window.m2c_youtube_cache.id)
            return;

            if (!window.m2c_youtube_cache.cli)
            return;

            if ( !window.m2c_youtube_cache.iframe )
            return;

            if ( hook == "seek" ){
                return window.m2c_youtube_cache.iframe.getCurrentTime()
            }
            else if ( hook == "duration" ){
                return window.m2c_youtube_cache.iframe.getDuration()
            }
            else if ( hook == "volume" ){
                return window.m2c_youtube_cache.iframe.getVolume()
            }
            else if ( hook == "buffered" ){
                return 0;
            }

        }
        this.controller = ( action, data ) => {

            if ( ID != window.m2c_youtube_cache.id )
            return;

            if ( !window.m2c_youtube_cache.cli )
            return;

            if ( !window.m2c_youtube_cache.iframe )
            return;

            this.log( "Controller -> " + action );

            if ( action == "play" ){
                window.m2c_youtube_cache.iframe.playVideo();
            }
            else if ( action == "pause" || action == "stop" ){
                window.m2c_youtube_cache.iframe.pauseVideo();
            }
            else if ( action == "set_volume" || action == "volume" ){
                window.m2c_youtube_cache.iframe.setVolume( data ) 
                if ( data > 0 )
                window.m2c_youtube_cache.iframe.unMute()
                else
                window.m2c_youtube_cache.iframe.mute()
            }
            else if ( action == "set_speed" || action == "speed" ){
                window.m2c_youtube_cache.iframe.setPlaybackRate( parseFloat( data ) ) 
            }
            else if ( action == "seek" ){
                window.m2c_youtube_cache.iframe.seekTo( window.m2c_youtube_cache.iframe.getDuration() * ( data / 100 ) )
            }  
            else if ( action == "full_screen" ){
                var _p = document.getElementById("players");
                if ( _p && _p.requestFullscreen )
                    _p.requestFullscreen();
                else
                    window.m2c_youtube_cache.iframe.getIframe().requestFullscreen()
            }

        }
        this.cleanup = () => {

            this.log("Cleanup")
            this.exe.halt();
            window.m2c_youtube_cache.cli = null;
            window.m2c_youtube_cache.id = null;

        }

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
        
        window.m2c_youtube_cache.cli = this;

    }

}

window.m2c_youtube_cache = {
    cli: null,
    iframe_promise: $.Deferred(),
    iframe: null,
    id: null
};

function onYouTubeIframeAPIReady() {
    window.m2c_youtube_cache.iframe_promise.resolve();
}