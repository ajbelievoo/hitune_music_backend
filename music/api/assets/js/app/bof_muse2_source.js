"use strict";

window.m2_source = {

    log: function( text, level ){
        window.m2.log( "m2_source", text, level )
    },
    solve_cache: {},
    solve: function ( action, ot, hash, sources, Data ) {

        var promise = $.Deferred();
        var hook = ot + "_" + hash;

        if ( window.m2_source.solve_cache[ hook ] && !sources ) {
            window.m2_source.log( "Solving " + ot + ":" + hash + " -> Found in cache" )
            promise.resolve( window.m2_source.solve_cache[ hook ] );
            return promise;
        }

        var checkOffline = $.Deferred();

        window.m2_source.log( "Solving " + ot + ":" + hash )

        if (window.m2.isEmbed()) {
            checkOffline.reject();
        }
        else {
            window.bof_offline.db.get_objects(ot, hash)
            .done(function (dled_item) {
                checkOffline.resolve(dled_item);
            })
            .fail(function () {
                checkOffline.reject();
            })
        }

        checkOffline
        .done(function (dled_item) {
            window.m2_source.log( "Solving " + ot + ":" + hash + " -> Found in offline" )
            window.m2_source.solve_cache[ hook ] = [dled_item.data.muse];
            promise.resolve([dled_item.data.muse]);
        })
        .fail(function (err) {

            window.m2_source.log( "Solving " + ot + ":" + hash + " -> Not Found in offline" )

            if (sources) {
                window.m2_source.log( "Solving " + ot + ":" + hash + " -> Already has sources" )
                promise.resolve(sources);
            }
            else {

                var _mIni = window.m2_focus.setting.get("ini");
                var _mThingie = window.m2_focus.setting.get("thingie");

                window.m2_source.log( "Solving " + ot + ":" + hash + " -> Requesting from API" )

                window.m2_source.xhr = window.becli.exe({

                    // ID: "muse_request_source",
                    liquid: true,
                    endpoint: "muse_request_source",

                    post: {
                        action: action,
                        object_type: ot,
                        object_hash: hash,
                        type: window.m2_focus.setting.get("type"),
                        ID: Data ? (Data.reqed_id ? Data.reqed_id : null) : null,
                        ini: _mIni,
                        thingie: _mThingie
                    },

                    callBack: function (sta, data, args) {
                        if (sta ? data.sources : false) {
                            window.m2_source.log( "Solving " + ot + ":" + hash + " -> API solved the request" )
                            window.m2_source.solve_cache[ hook ] = data.sources;
                            promise.resolve(data.sources);
                        } else if ( data.aborted ) {
                            window.m2_source.log( "Solving " + ot + ":" + hash + " -> API request aborted" )
                            promise.reject({
                                skip: false,
                                message: "Request Aborted"
                            })
                        } else {
                            window.m2_source.log( "Solving " + ot + ":" + hash + " -> API failed" )

                            if ( data.no_access ){
                                window.app.actions.get_unlock_solution(
                                    ot,
                                    hash,
                                )
                            }
                            
                            promise.reject({
                                skip: false,
                                skip_wait: true,
                                message: data.messages[0],
                                display: true
                            });
                        }
                    },

                    skip_apk: true

                }).client

            }

        })

        return {
            promise: promise,
            checkOffline: checkOffline
        }

    },
    cancelXhr: function(){
        if ( window.m2_source.xhr ){
            try {
                window.m2_source.xhr.abort();
            } catch( err ){}
        }
    }

}

window.m2_source.raaz = {

    cache: {},
    getCache: function( hook ){
        if ( window.m2_source.raaz.cache[ hook ] ) return window.m2_source.raaz.cache[ hook ];
        var cached = window.cache.get( "raaz_" + hook );
        if ( cached ) {
            try {
                var parsed = JSON.parse( cached );
                if ( parsed.time ? ( new Date().getTime() - parsed.time < 3600 * 1000 ) : false ){
                    window.m2_source.raaz.cache[ hook ] = parsed.data;
                    return parsed.data;
                }
            } catch( err ){}
        }
        return null;
    },
    setCache: function( hook, data ){
        window.m2_source.raaz.cache[ hook ] = data;
        window.cache.set( "raaz_" + hook, JSON.stringify({
            time: new Date().getTime(),
            data: data
        }) );
    },
    bofify_url: function( $source, $sourceData ){
        if ( $source ? ( $source.type ? ( $source.type[0] == "audio" || $source.type[0] == "video" ) && $source.type[1].address : false ) : false ){
            var $url = $source.type[1].address
            $source.type[1].address = $url + ( ( $url.includes("?") ? "&" : "?" ) + "bof_offline=" + $sourceData.data.ID + "-" + $sourceData.data.ot + "-" + $sourceData.data.hash )
        }
        return $source
    },
    parse: function (sourceData) {

        var promise = $.Deferred();
        var hook = sourceData.data.ot + "_" + sourceData.data.hash;
        var cached = window.m2_source.raaz.getCache( hook );
        if ( cached ) {
            window.m2_source.log("Raaz.Parse: cached");
            promise.resolve( cached );
            return promise;
        }

        var fetch_promise = $.Deferred()
        var source = sourceData.source;

        if ( source ? ( source.type ? ( source.type[1].fetch ? source.type[1].fetch === true : false ) : false ) : false ) {

            window.m2_source.log("Fetch: " + JSON.stringify(source.type[1]));

            var active = window.m2_queue.active.get()
            var source_promise = window.m2_source.solve("type_switch", active.data.ot, active.data.hash ).promise;
            source_promise
            .done(function (sources) {
                fetch_promise.resolve(sources[0]["source"])
            })
            .fail(function (err) {
                fetch_promise.reject({
                    skip: true,
                    skip_wait: true,
                    message: "Fetching switched-type failed"
                })
            })

        } else {
            window.m2_source.log("No Raaz Fetch. Continue");
            fetch_promise.resolve(source)
        }

        fetch_promise
        .done(function(fetchedSource){

            sourceData.source = fetchedSource

            if ( fetchedSource ? ( fetchedSource.type ? ( fetchedSource.type[1] ? ( fetchedSource.type[1].raaz ? fetchedSource.type[1].raaz === true : false ) : false ) : false ) : false ) {

                window.m2_source.log("Raaz.Parse: " + JSON.stringify(fetchedSource.type[1]));

                $(document).find("#player").addClass("raaz_requesting")

                var requestPromise = window.m2_source.raaz.request(sourceData);
    
                requestPromise
                .done(function (solvedRaaz) {
                    var finalSource = window.m2_source.raaz.bofify_url( solvedRaaz, sourceData );
                    window.m2_source.raaz.setCache( hook, finalSource );
                    promise.resolve( finalSource );
                    $(document).find("#player").removeClass("raaz_requesting")
                })
                .fail(function (failedRaaz) {
                    promise.reject(failedRaaz);
                    $(document).find("#player").removeClass("raaz_requesting")
                });
    
                window.m2_source.raaz.requestPromise = requestPromise;
            
            }
            else {
                var finalSource = window.m2_source.raaz.bofify_url( fetchedSource, sourceData );
                window.m2_source.raaz.setCache( hook, finalSource );
                promise.resolve( finalSource );
            }

        })
        .fail(function(err){
            promise.reject(err)
        })

        return promise;

    },
    request: function (sourceData) {

        var promise = $.Deferred();
        var raazData = sourceData.source["type"][1];
        var simplified_data = {
            object_type: sourceData.data.ot,
            object_hash: sourceData.data.hash,
            title: sourceData.data.title,
            sub_title: sourceData.data.sub_title,
            duration: sourceData.data.duration
        };

        window.m2_source.raaz.xhr = window.becli.exe({
            // ID: "muse_solve_raaz",
            liquid: true,
            endpoint: "muse_solve_raaz",
            post: $.extend(
                simplified_data,
                raazData,
                {
                    youtube_piped_instances: window.m2_source.raaz.instances ? window.m2_source.raaz.instances.length > 0 : false
                }
            ),
            callBack: function (sta, data, args) {

                if (sta) {

                    window.m2_source.log("Raaz.Request.Success")

                    if (data.youtube_piped_browser ? data.youtube_piped_browser == true : false) {

                        window.m2_source.log("Raaz.Request.yt_piped -> Run")

                        var ytPipedPromise = window.m2_source.youtube_piped.run( raazData, data.youtube_id, data )

                        ytPipedPromise
                        .done(function (ytpipedData) {
                            window.m2_source.log("Raaz.Request.yt_piped -> Success")
                            promise.resolve(ytpipedData);
                        })
                        .fail(function (ytDataFail) {

                            window.m2_source.log("Raaz.Request.yt_piped -> Failed " + ytDataFail.message)
                            if (!ytDataFail.kill) {
                                window.m2_source.log("Raaz.Request.yt_piped -> Failed -> Fallback to youtube")
                                promise.resolve({
                                    type: [
                                        "youtube", {
                                            youtube_id: data.youtube_id
                                        }
                                    ]
                                });
                            } 

                        });

                        window.m2_source.raaz.ytPipedPromise = ytPipedPromise

                    } else {
                        promise.resolve(data);
                    }

                }
                else if ( data.aborted ){
                    promise.reject({
                        message: "Raaz.Request.Xhr.Aborted",
                        skip: false
                    });
                } 
                else {
                    window.m2_source.log("Raaz.Request.Failure: " + data.messages[0], 1)
                    promise.reject({
                        message: data.messages[0],
                        skip_wait: true,
                        display: true
                    });
                }
            },
            skip_apk: true
            
        }).client

        return promise;

    },
    killActive: function(){

        window.m2_source.log("Raaz.Parse.Kill");

        if (window.m2_source.raaz.requestPromise ? window.m2_source.raaz.requestPromise.state() == "pending" : false) {
            window.m2_source.log("Raaz.Parse.Kill -> Canceling pre request promise");
            window.m2_source.raaz.requestPromise.reject({
                skip: false,
                message: "Raaz.Parse inner collapse"
            })
        }

        if (window.m2_source.raaz.ytPipedPromise ? window.m2_source.raaz.ytPipedPromise.state() == "pending" : false){
            window.m2_source.log("Raaz.Parse.Kill -> Canceling pre piped promise");
            window.m2_source.raaz.ytPipedPromise.reject({
                skip: false,
                message: "Raaz.Parse inner collapse",
                kill: true
            })
        }

        if (window.m2_source.raaz.xhr){
            try {
                window.m2_source.raaz.xhr.abort();
            } catch( err ){}
        }

    },

}

window.m2_source.youtube_piped = {

    promise: null,
    id: null,
    instances: null,
    type: null,
    failed: 0,
    run: function ( raazData, youtube_id, response ) {

        window.m2_source.log("Raaz.Youtube_piped_browser Run");

        var promise = $.Deferred();

        if (response.youtube_piped_urls ? Object.values(response.youtube_piped_urls).length > 0 : false) {
            window.m2_source.youtube_piped.instances = Object.values(response.youtube_piped_urls);
            window.m2_source.youtube_piped.type = response.youtube_piped_type;
        }

        window.m2_source.youtube_piped.id = youtube_id;
        window.m2_source.youtube_piped.failed = 0;

        if (!window.m2_source.youtube_piped.instances) {
            promise.resolve({
                type: [
                    "youtube",
                    {
                        youtube_id: youtube_id
                    }
                ]
            });
        }
        else {
            window.m2_source.log("Raaz.Youtube_piped_browser Checking " + window.m2_source.youtube_piped.instances.length + " instances");
            for (var i = 0; i < window.m2_source.youtube_piped.instances.length; i++) {
                var youtube_piped_instance = window.m2_source.youtube_piped.instances[i];
                window.m2_source.youtube_piped.run_instance(youtube_piped_instance, youtube_id);
            }
        }

        window.m2_source.youtube_piped.promise = promise;
        return promise;

    },
    run_instance: function (youtube_piped_instance, youtube_id) {

        var base = youtube_piped_instance + (youtube_piped_instance.endsWith("/") ? "" : "/");
        var urls = [
            base + "api/v1/streams/" + youtube_id,
            base + "streams/" + youtube_id
        ];

        var run = function (urlIndex) {
            $.ajax({
                url: urls[urlIndex],
                timeout: 4500,
                success: function (data, status, responseData) {
                    if (status == "success") {

                        var _ss = null;
                        if (window.m2_source.youtube_piped.type[0] == "audio" && data.audioStreams ? data.audioStreams.length > 0 : false) {
                            _ss = data.audioStreams;
                        }
                        else if (window.m2_source.youtube_piped.type[0] == "video" && data.videoStreams ? data.videoStreams.length > 0 : false) {
                            _ss = data.videoStreams;
                        }
                        if (_ss ? _ss.length > 0 : false) {

                            var chosenURL = null;
                            var chosenMIME = null;
                            var chosenURLScore = 0;

                            $.each(_ss, function (index, source) {

                                if (source["videoOnly"])
                                    return true;

                                var choose = false;
                                var score = (window.m2_source.youtube_piped.type[0] === "audio") ? parseInt(source["quality"]) : source["width"];

                                if (chosenURL === null) {
                                    choose = true;
                                } else if (window.m2_source.youtube_piped.type[1] === "hq" && score > chosenURLScore) {
                                    choose = true;
                                } else if (window.m2_source.youtube_piped.type[1] === "lq" && score < chosenURLScore) {
                                    choose = true;
                                }

                                if (choose) {
                                    chosenURL = source["url"];
                                    chosenMIME = source["mimeType"];
                                    chosenURLScore = score;
                                }

                            });

                            if (chosenURL) {
                                $.ajax({
                                    url: chosenURL,
                                    type: 'HEAD',
                                    success: function (headerResponse, status, xhr) {
                                        var statusCode = xhr.status;
                                        if (statusCode >= 200 && statusCode <= 210) {
                                            window.m2_source.log("Raaz.Youtube_piped_browser instance:" + youtube_piped_instance + " success");
                                            if (window.m2_source.youtube_piped.id == youtube_id && window.m2_source.youtube_piped.promise.state() == "pending") {

                                                window.m2_source.youtube_piped.promise.resolve({
                                                    type: [
                                                        window.m2_source.youtube_piped.type[0],
                                                        {
                                                            address: chosenURL,
                                                            type: 'free',
                                                            format: window.m2_source.youtube_piped.type[0] == "audio" ? false : chosenMIME
                                                        }
                                                    ]
                                                });
                                                window.m2_source.log("Raaz.Youtube_piped_browser instance:" + youtube_piped_instance + " success -> resolved -> " + chosenURL + " - " + chosenMIME );

                                            }
                                        } else {
                                            window.m2_source.youtube_piped.run_instance_failed(youtube_id);
                                        }
                                    },
                                    error: function () {
                                        window.m2_source.youtube_piped.run_instance_failed(youtube_id);
                                    }
                                });
                            } else {
                                window.m2_source.youtube_piped.run_instance_failed(youtube_id);
                            }

                        } else {
                            if (urlIndex + 1 < urls.length) {
                                run(urlIndex + 1);
                            } else {
                                window.m2_source.youtube_piped.run_instance_failed(youtube_id);
                            }
                        }

                    }
                },
                error: function (responseData, status, err) {
                    if (urlIndex + 1 < urls.length) {
                        run(urlIndex + 1);
                    } else {
                        window.m2_source.youtube_piped.run_instance_failed(youtube_id);
                    }
                },
            });
        };

        run(0);

    },
    run_instance_failed: function (youtube_id) {
        if (window.m2_source.youtube_piped.id == youtube_id) {
            window.m2_source.youtube_piped.failed = window.m2_source.youtube_piped.failed + 1;
        }
        if (window.m2_source.youtube_piped.failed == window.m2_source.youtube_piped.instances.length) {
            window.m2_source.log("Raaz.Youtube_piped_browser -> All instances failed!");
            window.m2_source.youtube_piped.promise.reject({
                skip: false,
                message: "All instances failed"
            })
        }
    }

}

window.m2_source.lyrics = {

    cache: {
        ready: false
    },
    get: function () {

        var promise = $.Deferred();
        var active = window.m2_queue.active.get();

        if (!active.data.lyrics) {
            promise.reject("No lyrics available");
            return promise;
        }

        window.becli.exe({
            endpoint: "muse_fetch_lyrics",
            post: active.data,
            liquid: true,
            ID: "fetch_lyrics",
            callBack: function (sta, data) {
                if (sta)
                    promise.resolve(data);
                else
                    promise.reject(data);
            }
        });

        return promise;

    },
    display: function () {

        var active = window.m2_queue.active.get();

        if (window.m2_source.lyrics.cache.ready && window.m2_source.lyrics.cache.ID === active.data.ID) {
            window.m2_source.lyrics.set("lyrics", window.m2_source.lyrics.cache.ready);
        }
        else {

            window.m2_source.lyrics.set("loading");
            window.m2_source.lyrics.get().done(function (result) {
                window.m2_source.lyrics.set("lyrics", result);
                window.m2_source.lyrics.cache.ready = result;
                window.m2_source.lyrics.cache.ID = active.data.ID
            }).fail(function (error) {
                window.m2_source.lyrics.set("failed", error.messages[0]);
            });

        }

    },
    set: function (sta, data) {

        if (sta == "loading") {
            $(document).find(".queue .data_wrapper .tBody .lyrics").html("<div class='loader'><span class='mdi mdi-refresh spin'></span></div>");
        }
        else if (sta == "failed") {
            $(document).find(".queue .data_wrapper .tBody .lyrics").html(
                "<div class='error'><span class='mdi mdi-emoticon-sad-outline'></span>" + data + "</div>"
            );
        }
        else {
            if (data.type == "musixmatch") {
                $(document).find(".queue .data_wrapper .tBody .lyrics").html(
                    "<div class='text'>" + data.lyrics.lyrics_body.replace(/\n/g, "<br />") + "</div>" +
                    "<div class='copyright'>" + data.lyrics.lyrics_copyright.replace(/\n/g, "<br />") + "</div>" +
                    ("<img src='" + data.lyrics.pixel_tracking_url + "' alt='tracking' class='tracking_img'>")
                );
            }
            else if (data.type == "local") {
                $(document).find(".queue .data_wrapper .tBody .lyrics").html(
                    "<div class='text'>" + data.lyrics + "</div>"
                );
            }
        }

    }

}
