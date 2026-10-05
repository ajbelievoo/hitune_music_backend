// Shared auth helper for distribution.hitune.in pages.
// BOF sessions live in localStorage (dm_*), which is per-origin. On music.hitune.in,
// cache.js mirrors sess_* keys to .hitune.in cookies so this subdomain can reuse them.
window.distAuth = {

  _cookie: function (name) {
    var m = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  },

  sess: function () {
    var id = localStorage.getItem('dm_sess_id');
    var key = localStorage.getItem('dm_sess_key');
    if (!id || !key) {
      id = this._cookie('hitune_sess_id');
      key = this._cookie('hitune_sess_key');
    }
    return { id: id, key: key };
  },

  headers: function (base) {
    var s = this.sess();
    if (s.id && s.key) {
      base['x-bof-sess-id'] = s.id;
      base['x-bof-sess-key'] = s.key;
    }
    return base;
  },

  loginUrl: function () {
    return 'https://music.hitune.in/userAuth?dback=' + encodeURIComponent(window.location.href);
  }

};
