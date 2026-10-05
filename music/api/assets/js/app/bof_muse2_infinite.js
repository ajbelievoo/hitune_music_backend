"use strict";

window.m2_infinite = {

    log: function( text, level ){
        window.m2.log( "m2_infinite", text, level )
    },

    hasNext: function () {

        if (window.m2.config.get("queue_hide_infinite"))
        return false

        if (!window.m2_focus.setting.get( "infinite"))
        return false

        if ( this.queue.get() ? this.queue.get().length : false )
        return true
        return false

    },

    off: function (){
        window.ui.body.removeClass( "muse_infinite", true );
        window.m2_focus.setting.set( "infinite", false )
    },
    on: function (){

        $(document).find("#player").addClass("repeat_off").removeClass("repeat_on");
        window.ui.body.addClass( "repeat_off", true);
        window.ui.body.removeClass( "repeat_on", true);
        window.ui.body.addClass( "muse_infinite", true );
        window.m2_focus.setting.set( "infinite", true )
        window.m2_infinite.start()

    },

    _startData: null,
    start: function (object_type, object_hash, sourceArgs, displayData) {

        if (window.m2.config.get("queue_hide_infinite"))
        return;

        if (window.m2.isEmbed())
        return

        window.m2_infinite.log( "Start" )

        $(document).find(".queue .list .next_items").html( "" );

        if ( !object_type || !object_hash ) {
            var _activeItem = ( window.m2_queue && window.m2_queue.active ) ? window.m2_queue.active.get() : null;
            if ( _activeItem && _activeItem.data && _activeItem.data.ot && _activeItem.data.hash ) {
                object_type = _activeItem.data.ot;
                object_hash = _activeItem.data.hash;
            }
            else if ( this._startData ) {
                this.queue.resetPage();
                this.queue.request();
                return;
            }
            else return;
        }

        var widget_id = null;
        if (displayData?displayData.widget:false) {
            if (displayData.widget === "offline")
                widget_id = "offline";
            else if (typeof (displayData.widget) == "string")
                widget_id = displayData.widget;
            else
                widget_id = displayData.widget.ID ? displayData.widget.ID : ( displayData.id ? displayData.id : displayData.ID )
        }

        var curPage = window.ui.page.curr();

        this._startData = {
            endpoint_app: window.location.search,
            object_type: object_type,
            object_hash: object_hash,
            widget_id: widget_id,
            page_name: window.bof._safeGet( "name", curPage ) ? curPage.name : null,
            page_url: window.bof._safeGet( "u_args.urlData.url.full", curPage ) ? curPage.u_args.urlData.url.full : null,
            page_ot: window.bof._safeGet( "data.becli.single.data.ot", curPage ) ? curPage.data.becli.single.data.ot : null,
            page_hash: window.bof._safeGet( "data.becli.single.data.hash", curPage ) ? curPage.data.becli.single.data.hash : null,
        }

        this.queue.resetPage()
        this.queue.request()

    }

}

window.m2_infinite.queue = {

    log: function( text, level ){
        window.m2_infinite.log( "Queue " + text, level )
    },

    _items: null,

    get: function(){
        return this._items;
    },

    pop: function(){

        var pop = this._items.shift()
        if ( this.pageMore && ( !this._items || this._items.length < 5 ) )
        this.request()
        return pop

    },

    resetPage: function(){

        this._items = []
        this.page = 1
        this.pageMore = true

    },

    getPage: function($increase){

        var p = this.page + 1 - 1
        if ( $increase )
        this.page = this.page + 1;

        return p

    },

    set: function( $newItems, buildUI ){

        this._items = (this._items?this._items:[]).concat( $newItems )
        if ( buildUI === true ){
            window.m2_infinite.ui.build()
        }

    },

    request: function () {

        window.m2_infinite.log("request");

        if (window.m2.config.get("queue_hide_infinite"))
        return;

        if (!window.user.online()) {

            if(!window.m2_infinite.offline_is_set){
                var offlineInfinite = []
                window.bof_offline.db.list().done(function (items) {
                    if (items) {
                        for (var z = 0; z < Object.keys(items).length; z++) {
                            var itemKey = Object.keys(items)[z];
                            var item = items[itemKey];
                            offlineInfinite.push(item["data"]["muse"]["data"]);
                        }
                        window.m2_infinite.queue.set( offlineInfinite, true )
                    }
                });
                window.m2_infinite.offline_is_set = true
            }
            return

        }

        var requestData = window.m2_infinite._startData

        if ( !requestData ){
            window.m2_infinite.log("Request failed -> No initialRequestData", 1);
            return;
        }

        requestData.queue = JSON.stringify( this.simplify_queue() )
        requestData.infinite = JSON.stringify( this.simplify_infinite() )
        requestData.seed = requestData.queue 
        requestData.page = this.getPage( true )

        window.becli.exe({
            endpoint: "muse_infinite" + requestData.endpoint_app,
            liquid: true,
            ID: "muse_infinite",
            post: requestData,
            callBack: function (sta, data, args) {
                if (sta?(data.items?data.items.length:false):false) {
                    window.m2_infinite.queue.set( data.items, true )
                } else {
                    window.m2_infinite.queue.pageMore = false
                }
            }
        });

    },

    simplify_queue: function () {

        var _items = window.m2_queue.get()
        var _items_s = {};
        if (_items) {
            for (var i = 0; i < Object.keys(_items).length; i++) {
                var _item_k = Object.keys(_items)[i];
                var _item = _items[_item_k];
                if (_item.data ? _item.data.hash : false) {
                    _items_s[i] = {
                        object_type: _item.data.ot,
                        object_hash: _item.data.hash,
                        title: _item.data.title
                    };
                }
            }
        }

        return _items_s;

    },

    simplify_infinite: function () {

        var _items = this.get()
        var _items_s = {};
        if (_items) {
            for (var i = 0; i < Object.keys(_items).length; i++) {
                var _item_k = Object.keys(_items)[i];
                var _item = _items[_item_k];
                _items_s[i] = {
                    object_type: _item.ot,
                    object_hash: _item.hash,
                    title: _item.title
                };
            }
        }

        return _items_s;

    },

}

window.m2_infinite.ui = {

    log: function( text, level ){
        window.m2_infinite.log( "UI " + text, level )
    },
    build: function () {

        var queue = window.m2_infinite.queue.get()
        if (!queue) return;
        if (!window.m2_queue.ui.isOpen()) return;

        window.m2_infinite.log("Building " + Object.keys(queue).length + " items")

        var que = "";
        for (var i = 0; i < Object.keys(queue).length; i++) {
            var item_k = Object.keys(queue)[i];
            var item = queue[item_k];
            que += "<div class='item bof_" + item.ot + "_" + item.hash + " no_action clearafter' id='bof_queue_" + item_k + "'>";
            que += "<div class='cover_holder'><div class='cover' style='background-image:url(\"" + item.cover + "\")'></div></div>";
            que += "<div class='detail'>";
            que += "<div class='title'>" + item.title + "</div>";
            if (item.sub_data)
                que += "<div class='sub_title'>" + item.sub_data + "</div>";
            que += "</div>";
            que += "</div>";
        }

        $(document).find(".queue .list .next_items").html(que);

    }

}
