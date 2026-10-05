"use strict";

window.m2_queue = {

    _i: null,
    _items: [],

    log: function( text, level, ID ){
        window.m2.log( ( ID ? ( ID + ":" ) : ( window.m2_queue.ID ? window.m2_queue.ID + ":" : "" ) ) + "m2_queue", text, level )
    },
    get: function(){
        return window.m2_queue._items
    },
    clear: function(){
        window.m2_queue._items = [];
        window.m2_queue._i = 0;
        window.m2_queue.save();
        // Don't build UI when clearing - prevents empty queue overlay on page load
        // if ( window.m2_queue.ui )
        // window.m2_queue.ui.build();
    },
    set: function( $items ){

        window.m2_queue._items = $items;
        window.m2_queue._i = 0;

        window.m2_queue.log( "Set " + ( $items ? $items.length : 0 ) + " item(s)" )

        window.m2_queue.active.setFocus();
        if(!$("body").hasClass("queue_disable_auto"))
        window.m2_queue.ui.build()
        window.m2_queue.save()

    },
    extend: function ($sources, $action) {

        window.m2_queue.log( "Extending " + $action );

        if ( $action == "focus" || !window.m2_queue._items.length ) {
            window.m2_queue.set( $sources );
        }
        else if ($action == "last" || window.m2_queue._items.length == 1) {
            window.m2_queue._items = window.m2_queue._items.concat( $sources )
        }
        else {
          var _items = window.m2_queue._items
          var _itemsBefAndSelf = _items.slice( 0, window.m2_queue._i+1 )
          var _itemsAfter = _items.slice( window.m2_queue._i+1 )
          window.m2_queue._items = _itemsBefAndSelf.concat( $sources ).concat( _itemsAfter )
        }

        window.m2_queue.save()

    },

    getItem: function( $id ){
        
        if ( !window.m2_queue._items ? true : !window.m2_queue._items.length )
        return

        if ( window.m2_queue._items[ $id ] )
        return window.m2_queue._items[ $id ]

        for ( var i=0; i<window.m2_queue._items.length; i++ ){
            var $i = window.m2_queue._items[ i ]
            if ( $i.data.ID == $id )
            return $i
        }

    },
    removeItem: function( $id ){

        window.m2_queue.log("remove_item id:"+$id)

        if ( !window.m2_queue._items ? true : !window.m2_queue._items.length )
        return

        var cL = window.m2_queue._items.length
        var nL = window.m2_queue._items.length-1

        if ( cL == 1 ){
            window.m2.close()
            return
        }

        var $i = null
        for ( var i=0; i<window.m2_queue._items.length; i++ ){
            if ( window.m2_queue._items[ i ].data.ID == $id )
            $i = i
        }

        var newI = $i
        if ( newI >= nL )
        newI = 0

        window.m2_queue._i = newI
        window.m2_queue._items = window.m2_queue._items.slice( 0, $i ).concat( window.m2_queue._items.slice( $i+1 ) )
        window.m2_queue.save()
        window.m2_queue.ui.build()

    },

    save: function () {

        if (window.m2.isEmbed())
        return;

        if (!window.m2.config.get("queue_save"))
        return;

        window.cache.set("muse_que", JSON.stringify(window.m2_queue._items));
        window.cache.set("muse_que_i", window.m2_queue._i+1);

    },
    load: function () {

        if (window.m2.isEmbed())
        return;

        if (!window.m2.config.get("queue_save"))
        return;

        window.m2_queue._i = window.cache.get("muse_que_i")-1

        var cachedItems = window.cache.get("muse_que") ? JSON.parse( window.cache.get("muse_que") ) : null
        var items = []
        if ( cachedItems ? cachedItems.length : false ){
            for ( var i=0; i<cachedItems.length; i++ ){
                if ( cachedItems[i] ? ( cachedItems[i].data && cachedItems[i].source && cachedItems[i].types ) : false ){
                    items.push( cachedItems[i] )
                }
            }
        }
        window.m2_queue._items = items

    },

    next: function( $type ){

        $type = $type ? $type : "soft";
        window.m2_queue.log( "Next: " + $type )

        var hasNext = window.m2_queue.hasNext({
            infinite: true
        });

        if ( !hasNext && $type == "soft" ){
            window.m2_queue.active.setFocus({
                autoplay: window.m2_focus.setting.get("repeat")
            })
            window.m2_queue.log( "Next -> No next" )
            return;
        }

        var hasNextQueue = window.m2_queue.hasNext({
            infinite: false
        });

        if ( hasNextQueue ) {
            window.m2_queue.log( "Upping the queue I" );
            window.m2_queue._i = window.m2_queue._i + 1;
            window.m2_queue.active.setFocus();
            if(window.m2_queue.ui.isOpen())
            window.m2_queue.ui.build()
            window.m2_queue.save()
            return;
        }

        if ( hasNext ){
            window.m2_queue.log( "Loading from Infinite" );
            var pop = window.m2_infinite.queue.pop()
            window.m2_focus.appendObject("last", pop.ot, pop.hash).done(function () {
                window.m2_queue.log("Upping the queue I");
                window.m2_queue._i = window.m2_queue._i + 1;
                window.m2_queue.save()
                window.m2_queue.active.setFocus();
                if (window.m2_queue.ui.isOpen())
                window.m2_queue.ui.build()
            })
            return;
            // load infinite
        }

        window.m2_queue._i = 0;
        window.m2_queue.active.setFocus();
        if(window.m2_queue.ui.isOpen())
        window.m2_queue.ui.build()
        window.m2_queue.save()

    },
    hasNext: function( args ){

        if ( ( window.m2_queue._i === null || !window.m2_queue._items ) ? true : !window.m2_queue._items.length ){
            window.m2_queue.log( "HasNext: Failed -> No i or items" );
            return;
        }

        var checkInfinite = args ? args.infinite : false
        var hasNextQueue = window.m2_queue._items.length > window.m2_queue._i+1;

        if ( !checkInfinite ){
            if ( !hasNextQueue )
            window.m2_queue.log( "HasNext: Failed -> no nextQueue && infinite is off" );
            return hasNextQueue;
        }

        if ( hasNextQueue ){
            window.m2_queue.log( "HasNextQueue: OK -> hasNextQueue" );
            return hasNextQueue;
        }

        var hasNextInfinite = window.m2_infinite.hasNext();
        if ( hasNextInfinite ){
            window.m2_queue.log( "HasNextQueue: OK -> hasNextInfinite" );
        } else {
            window.m2_queue.log( "HasNextQueue: Failed -> hasNextInfinite" );
        }
        return hasNextInfinite;

    },

    prev: function(){

        if ( !window.m2_queue.hasPrev() ){
            window.m2_queue.active.setFocus()
            return;
        }

        if ( window.m2_queue._i > 0 ){
            window.m2_queue._i = window.m2_queue._i - 1;
        } else {
            window.m2_queue._i = window.m2_queue._items.length - 1
        }
        
        window.m2_queue.active.setFocus();
        if(window.m2_queue.ui.isOpen())
        window.m2_queue.ui.build()
        window.m2_queue.save()

    },
    hasPrev: function(){

        if ( window.m2_queue._i > 0 )
        return true;

        if ( window.m2_queue._items ? window.m2_queue._items.length >= 2 : false )
        return true;


    },

    shuffle: function(){

        if ( ( window.m2_queue._i === null || !window.m2_queue._items ) ? true : !window.m2_queue._items.length ){
            window.m2_queue.log( "Shuffle: Failed -> No i or items" );
            return;
        }

        if ( window.m2_queue._items.length < 2 ){
            window.m2_queue.log( "Shuffle: Failed -> Just 1 in queue" );
            return;
        }

        var activeI = window.m2_queue._items[ window.m2_queue._i ]
        var newItems = [].concat( window.m2_queue._items )
        newItems.splice( window.m2_queue._i, 1 )
        newItems = window._g.array_shuffle( newItems )
        newItems.unshift( activeI )

        window.m2_queue._items = newItems;
        window.m2_queue._i = 0;
        window.m2_queue.ui.buildQueueList()
        window.m2_focus.ui.hook()
        window.m2_queue.save()

    },

    switch_type: function( newType ){
        var queue = window.m2_queue._items
        for ( var i=0; i<queue.length; i++){

            if (i==window.m2_queue._i)
            continue

            var _q = queue[i];
            var _q_isNewType_active = null
            var _q_isNewType_available = null

            if ( _q ? ( _q.types ? _q.types.sources : false ) : false ){

                for ( var z=0; z<Object.keys(_q.types.sources).length; z++ ){

                    var _k = Object.keys(_q.types.sources)[z]
                    var _s = _q.types.sources[ _k ]

                    if ( _s.sources ? _s.sources.length : false ){
                        for ( var zz=0; zz<_s.sources.length; zz++ ){
                            var _ss = _s.sources[zz]
                            if ( _ss.hook == newType ){
                                _q_isNewType_available = true;
                                if ( _ss.active ){
                                    _q_isNewType_active = true;
                                }
                            }
                        }
                    }


                }

                if ( _q_isNewType_available && !_q_isNewType_active ){
                    for ( var z=0; z<Object.keys(_q.types.sources).length; z++ ){

                        var _k = Object.keys(_q.types.sources)[z]
                        var _s = _q.types.sources[ _k ]

                        if ( _s.sources ? _s.sources.length : false ){
                            for ( var zz=0; zz<_s.sources.length; zz++ ){
                                var _ss = _s.sources[zz]
                                if ( _ss.active ){
                                    queue[i].types.sources[_k].sources[zz].active = false
                                    queue[i].types.sources[_k].active = false
                                }
                                if ( _ss.hook == newType ){
                                    queue[i].types.sources[_k].sources[zz].active = true
                                    queue[i].types.sources[_k].active = true
                                    queue[i].source = {
                                        type: [
                                            newType.split("_")[0],
                                            {
                                                fetch: true
                                            }
                                        ]
                                    }
                                }
                            }
                        }



                    }

                }

            }

        }
        window.m2_queue._items = queue
        window.m2_queue.save()
    }

}

window.m2_queue.ui =  {

    opener: false,
    opened: false,
    player_place: "normal",
    log: function( text, level, ID ){
        window.m2_queue.log( "UI " + text, level, ID )
    },
    buildQueueList: function(){

        var que = "";
        var items = window.m2_queue.get();

        if (items) {
            for (var i = 0; i < Object.keys(items).length; i++) {
                var item_key = Object.keys(items)[i];
                var item = items[item_key];
                if ( !item ? true : ( !item.data ? true : !item.data.ot ) ){
                    continue
                }
                que += "<div class='item bof_" + item.data.ot + "_" + item.data.hash + " clearafter' id='bof_queue_" + item.data.ID + "' data-i='"+i+"'>";
                que += "<div class='cover_holder'><div class='cover' style='background-image:url(\"" + item.data.cover + "\")'></div></div>";
                que += "<div class='detail'>";
                que += "<div class='title'>" + item.data.title + "</div>";
                que += "<div class='sub_title'>" + item.data.sub_title + "</div>";
                que += "</div>";
                que += "<div class='duration'>" + window._g.duration_hr(item.data.duration) + "</div>";
                que += "</div>";
            }
        }

        $(document).find(".queue .list .items").html(que);

    },
    build: function ( offset, $preloaded ) {

        if ( window.m2.config.get("queue_hide") )
        return;

        window.m2_queue.log( "Building" );

        if (window.m2.isEmbed())
        return;

        if ($("body").hasClass("queue_hide"))
        return;

        window.pageBuilder.widget.item.closeMenu();

        if ( window.app.config.mobile ? $preloaded !== true : false ){

            window.m2_queue.log( "Building.Mob" );

            if ( $("body").hasClass("que_active_open") ){
                window.m2_queue.ui.previewFocus( true );
                // $(document).find("#player").css("--preview_scroll_top",$(document).find(".queue").scrollTop()+"px")
                setTimeout(function(){
                    $("#player").detach().insertAfter(".queue #preview")
                },10)  
                setTimeout(function(){
                    $("#player").detach().insertAfter(".queue #preview")
                },100)        
                return
            }
            
            window.m2_focus.ui.openAnime();
            window.m2_queue.log( "Building -> Anime Instead" );
            return

        }

        if (!window.m2_queue.jqUI) {

            window.m2_queue.jqUI = window.bof._loadExtension({
                path: "js/third/jquery-ui-1.13.0.custom/jquery-ui.min.js",
                name: "jquery_ui_custom",
                skipNameCheck: true,
                base: "bof_assets",
                dir: false,
                skipNameCheck: true,
                version: false
            })
        }

        if ( window.m2_focus.setting.get("infinite") && !$(document).find("body").hasClass("muse_infinite") ){
            window.ui.body.addClass( "muse_infinite", true );
            window.m2_infinite.on();
        }

        var source = window.m2_queue.active.get();
        window.m2_queue.ui.buildQueueList();

        if ( offset !== undefined )
        $(document).find(".queue").css("top", ( offset > 0 ? offset : 0 ) + "px");

        if ( $preloaded !== true )
        $(document).find(".queue #preview").html("");

        setTimeout(function () {

            $(document).find(".queue").stop(false).animate({ top: 0 }, 150);

            window.ui.body.addClass("que_active", true);

            window.m2_queue.ui.opened = true;

            window.m2_queue.ui.popOut();
            window.m2_queue.ui.opener =
            setTimeout(function () {

                if ( $preloaded !== true ){
                    window.m2_queue.ui.previewFocus();
                }
                else {
                    setTimeout(function () {
                        window.m2_queue.ui.move_frame("queue");
                        window.pageBuilder.widget.item.listen();
                    }, 50)
                }
            
                window.ui.body.addClass("que_active_open", true);
                window.m2_focus.ui.hook()
                window.m2_infinite.ui.build()
                window.m2_focus.ui.touch()

                if ( window.app.config.mobile ){
                    $("#player").detach().insertAfter(".queue #preview")
                }

                if ( window.m2_queue.jqUI ? window.m2_queue.jqUI.state() == "resolved" : false ){
                    $(document).find(".queue .data_wrapper .list ._items").sortable({
                        revert: true,
                        scroll: false,
                        zIndex: 99,
                        stop: function () {

                            var iC = $(document).find(".queue .data_wrapper .list ._items .item").length
                            var sorted = []
                            var newI = 0
                            for (var i = 0; i < iC; i++) {
                                var $i = $($(document).find(".queue .data_wrapper .list ._items .item")[i])
                                $i.attr("data-i",i)
                                var active = $i.hasClass("active_que")
                                var id = $i.attr("id").substr("bof_queue_".length)
                                var data = null;
                                window.m2_queue._items.forEach(function (item) {
                                    if (item.data.ID == id)
                                        data = item
                                })
                                if (active) {
                                    newI = i
                                }
                                sorted.push(data)
                            }

                            window.m2_queue._items = sorted
                            window.m2_queue._i = newI
                            window.m2_queue.save()

                        }
                    })
                }

                $(document).find("body.mobile.que_active #player").addClass("newNav")

            }, 50);

            window.m2_focus.ui.resetAnime()
            window.m2_focus.ui.hook()
            window.pageBuilder.widget.item.listen();

        }, 20);


    },
    destroy: function(){

        window.m2_queue.log( "Destroying" );

        window.m2_queue.ui.opened = false;
        window.ui.body.removeClass("que_active", true);
        window.ui.body.removeClass("que_active_open", true);
        if ( window.app.config.mobile ){
            $("#player").detach().insertAfter(".queue")
        }
        window.m2_queue.ui.move_frame("normal");
        $(document).find(".queue").stop(false).animate({ top: "150vh" }, 350);
        window.m2_focus.ui.resetAnime()
        clearTimeout(window.m2_queue.ui.opener);
        window.m2_focus.ui.touch()
        $(document).find("body.mobile.que_active #player.newNav").removeClass("newNav")

    },
    isOpen: function(){
        return window.m2_queue.ui.opened;
    },

    previewFocus: function ( $preload ) {

        if ( window.m2.isEmbed() )
        return;

        if ( !window.m2_queue.ui.isOpen() && !$preload )
        return;

        /*if (window.muse.player.setup.audio.cache.streamMetaDataTimer) {
            window.muse.player.setup.audio.streamMetaData()
        }*/

        var activeThingie = window.m2_thingie.has() && window.m2_thingie.isPlaying()

        var active = window.m2_queue.active.get();
        var active_que_id = active.data.ID;
        var preview = null;
        var brand_html = "<div class='hitune_brand'><span class='mdi mdi-music-note'></span><span class='_t'>" + ( window.app && window.app.config && window.app.config.name ? window.app.config.name : "Hitune Music" ) + "</span></div>";
        var types = "<div class='types clearafter'>";
        if (active.types) {
            for (var i = 0; i < Object.keys(active.types.sources).length; i++) {
                var _k = Object.keys(active.types.sources)[i];
                var type_sources = active.types.sources[_k];
                types += "<div data-type='" + _k + "' class='type type_" + _k + " count_" + type_sources.count + " " + (type_sources.count == 1 ? "single" : "parent") + " " + (type_sources.locked ? "locked" : "") + " " + (type_sources.active ? "active" : "") + "'>";
                types += "<div class='type_title'>" + _k + "</div>";
                types += "</div>";
            }
        }
        types += "</div>";

        var graph_html = "";
        var item_html = "";

        if (active.data ? active.data.preview : false)
            preview = active.data.preview;

        var preview_html = "<div class='preview_wrapper source_count_" + (active.types ? active.types.count : "0") + "'>"
        item_html = "<div class='item no_action text_wrapper bof_"+ active.data.ot + "_" + active.data.hash + "' id='que_muse_active'>\
            <a href='"+ active.data.link + "' class='title'>" + active.data.title + "</a>\
            <a href='"+ active.data.sub_link + "' class='sub_title'>" + active.data.sub_title + "</a>\
            <div class='button_wrapper more'><div><span class='mdi mdi-dots-vertical'></span></div></div>\
            </div>"

        if ( window.app.config.mobile ){
            
            preview_html = "<div class='preview_wrapper source_count_" + (active.types ? active.types.count : "0") + "'>"

            if (preview ? ( preview.type == "image" && preview.image ) : false) {
                preview_html = "<div class='bg_image_wrapper'>"+ preview.image + "</div>" + preview_html
            }

            item_html = "<div class='item action_menu text_wrapper bof_"+ active.data.ot + "_" + active.data.hash + "' id='que_muse_active'>\
            <a href='"+ active.data.link + "' class='title'>" + active.data.title + "</a>\
            <a href='"+ active.data.sub_link + "' class='sub_title'>" + active.data.sub_title + "</a>\
            <div class='button_wrapper more'><div><span class='mdi mdi-dots-vertical'></span></div></div>\
            </div>"

        }

        graph_html = "<div class='graph_wrapper type_"+ active.source.type[0] + " p_type_" + (preview ? preview.type : "un") + (
            active.source.type[1].hls ? ( active.source.type[1].type === "audio" ? " hls_audio hlsed " : " hlsed " ) : ""
        ) + "'>";

        if (preview ? ( preview.type == "image" && preview.image ) : false) {
            graph_html += "<div class='image_wrapper'>"+ preview.image + "</div>"
        }

        if ( activeThingie ){
            preview_html = "<div class='preview_wrapper source_count_1 thingie_attached'>";
            item_html = "<div class='item no_action text_wrapper' id='bof_queue_ad'>\
              <a target='_blank' href='"+ window.m2_thingie.get().url + "' class='title'>" + window.m2_thingie.get().title + "</a>\
              <a target='_blank' href='"+ window.m2_thingie.get().url + "' class='sub_title'>"+window.lang.return( "sponsor", { ucfirst: true } )+"</a>\
              <div id='thingie_skip'>\
                <span class='_rt'></span>\
                <span class='skip cant_skip btn btn-secondary'>"+window.lang.return( "skip", { ucfirst: true } )+"</span>\
                <a target='_blank' class='btn btn-secondary' href='"+window.m2_thingie.get().url+"'>"+window.lang.return( "visit", { ucfirst: true } )+"</a>\
              </div>\
            </div>"
            graph_html = "<div class='graph_wrapper type_audio p_type_banner'>"
            if ( window.m2_thingie.getType() == "audio" ){
                graph_html += "<div class='thingie_wrapper'>\
                <a class='banner_wrapper' style='background-image:url(\""+ window.m2_thingie.get().banner + "\")' href='" + window.m2_thingie.get().url + "'></a>\
                </div>";
            }
            types = "";
        }

        graph_html += "</div>"

        if ( window.app.config.mobile ){
            preview_html = preview_html + "<div class='types_wrapper'>"+types+"</div>" + graph_html + item_html + "</div>" + brand_html
        }
        else {
            preview_html += item_html + graph_html + types + "</div>" + brand_html
        }

        $(document).find(".queue #preview").html(preview_html).attr("class", "").addClass(active.data.ot);
        $(document).find(".queue.second_tab").removeClass("second_tab");
        $(document).find(".queue .tab.active").removeClass("active");
        $(document).find(".queue .tab._queue").addClass("active");
        $(document).find(".queue .tab._lyrics").removeClass("has_not").removeClass("has").addClass(active.data.lyrics ? "has" : "has_not")

        $(document).find(".queue").css("--preview_graph_height",$(document).find(".queue #preview .preview_wrapper .graph_wrapper").height()+"px")

        if ( !$preload ){
            setTimeout(function () {
                window.m2_queue.ui.move_frame("queue");
                window.pageBuilder.widget.item.listen();
            }, 100)
        }

    },
    move_frame: function ( $place, $force ) {

        if ($place == window.m2_queue.ui.player_place && $force !== true)
            return;

        window.m2_queue.ui.player_place = $place;

        if ($place == "queue") {

            $(document).find(".queue")[0].scrollTop = 0
            var graphPos = $(document).find(".queue .graph_wrapper").offset();

            if (graphPos) {
                var $graph = $(document).find(".queue .graph_wrapper");
                var gWidth = $graph.outerWidth();
                var gHeight = $graph.outerHeight();
                var $playerEl = $(document).find("#player");
                var playerTop = $playerEl.length ? $playerEl.offset().top : $(window).height();
                var availHeight = playerTop - graphPos.top - 8;
                if (availHeight > 0 && gHeight > availHeight) {
                    var gScale = availHeight / gHeight;
                    gHeight = Math.round(availHeight);
                    gWidth = Math.round(gWidth * gScale);
                    graphPos.left = graphPos.left + (($graph.outerWidth() - gWidth) / 2);
                }
                $(document)
                    .find("#players")
                    .css("top", graphPos.top + "px")
                    .css("left", graphPos.left + "px")
                    .css("width", gWidth + "px")
                    .css("height", gHeight + "px")
            }

        }
        else if ($place == "normal") {

            $(document)
                .find("#players")
                .css("top", "")
                .css("left", "")
                .css("width", "")
                .css("height", "")

        }
        else if ($place == "hide") {

            $(document)
                .find("#players")
                .css("left", "200vh")

        }

    },
    popOut: function () {

        if (window.m2.isEmbed())
        return;

        window.m2_queue.ui.move_frame("hide");

    },

}

window.m2_queue.active = {

    log: function( text, level, ID ){
        window.m2_queue.log( "Active " + text, level, ID )
    },
    get: function(){

        if ( window.m2_queue._i === null )
        return;

        if ( !window.m2_queue._items ? true : !window.m2_queue._items.length )
        return;

        return window.m2_queue._items[ window.m2_queue._i ];

    },
    setButtons: function( newButtons ){

        window.m2_queue._items[ window.m2_queue._i ].data.buttons.items = newButtons

    },
    update: function (newSources, hook, id) {

        if (window.m2_queue._i === null)
        return;

        if (!window.m2_queue._items ? true : !window.m2_queue._items.length)
        return;

        window.m2_queue._items[ window.m2_queue._i ].source = newSources

        var types = window.m2_queue._items[ window.m2_queue._i ]["types"]
        if ( types["sources"] ){
            for ( var i=0; i<Object.keys(types["sources"]).length; i++ ){
                var _k = Object.keys(types["sources"])[i]
                var _i = types["sources"][_k]
                if ( _i.active ){
                    _i.active = false
                }
                if ( hook === _k ){
                    _i.active = true
                }
                var type_sources = _i["sources"]
                if ( type_sources ? Object.keys(type_sources).length : false ){
                    for ( var z=0; z<type_sources.length; z++ ){
                        var type_source = type_sources[z]
                        if ( type_source.active ){
                            type_source.active = false
                        }
                        if ( type_source.hash === id ){
                            type_source.active = true
                            _i.active = true
                        }
                        if ( !id && type_source.hook === hook ){
                            type_source.active = true
                            _i.active = true
                        }
                    }
                }
            }
        }

        window.m2_queue._items[ window.m2_queue._i ].types = types
        window.m2_queue.save()

    },
    setFocus: function(exeArgs){

        var active = window.m2_queue.active.get();
        if ( !active ) return
        
        window.m2_queue.log( "setFocus" );
        return window.m2_focus.set( active, exeArgs );

    },
    changeFocus: function( queI, queID ){

        if ( queI == window.m2_queue._i ){
            window.m2_queue.log("changeFocus -> Already set");
            return;
        }

        window.m2_queue.log("ChangeFocus -> to " + queID)

        window.m2_queue._i = queI;
        window.m2_queue.active.setFocus();
        window.m2_queue.ui.build()
        window.m2_queue.save()

    }

}
