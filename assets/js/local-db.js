/**
 * local-db.js — Autonomous Local MySQL Database Client
 * Navotas Polytechnic College (NPC) ELMS
 * 
 * Direct high-speed local MySQL REST queries via /api/rest.php:
 * - 0ms cloud latency
 * - Zero external dependencies
 * - Pure offline local database operations
 */

(function() {
    function createLocalClient() {
        return {
            from: function(table) {
                var _table = table;
                var _select = '*';
                var _order = null;
                var _limit = null;
                var _filters = [];
                var _single = false;

                function executeQuery() {
                    var qs = 'table=' + encodeURIComponent(_table) + '&select=' + encodeURIComponent(_select);
                    if (_order) qs += '&order=' + encodeURIComponent(_order);
                    if (_limit) qs += '&limit=' + encodeURIComponent(_limit);
                    if (_filters.length) qs += '&' + _filters.join('&');

                    return fetch('/api/rest.php?' + qs)
                        .then(function(r) { return r.json(); })
                        .then(function(res) {
                            var data = res;
                            if (_single) {
                                data = Array.isArray(res) ? (res[0] || null) : res;
                            } else if (!Array.isArray(res)) {
                                data = res ? [res] : [];
                            }
                            return { data: data, error: null };
                        })
                        .catch(function(err) {
                            console.warn('[LocalDB] Query error for table ' + _table + ':', err);
                            return { data: null, error: err };
                        });
                }

                var queryObj = {
                    select: function(fields) {
                        _select = fields || '*';
                        return queryObj;
                    },
                    eq: function(col, val) {
                        _filters.push(encodeURIComponent(col) + '=eq.' + encodeURIComponent(val));
                        return queryObj;
                    },
                    neq: function(col, val) {
                        _filters.push(encodeURIComponent(col) + '=neq.' + encodeURIComponent(val));
                        return queryObj;
                    },
                    order: function(col, opts) {
                        _order = col + (opts && opts.ascending === false ? '.desc' : '.asc');
                        return queryObj;
                    },
                    limit: function(n) {
                        _limit = parseInt(n, 10);
                        return queryObj;
                    },
                    single: function() {
                        _single = true;
                        _limit = 1;
                        return executeQuery();
                    },
                    then: function(resolve, reject) {
                        return executeQuery().then(resolve, reject);
                    }
                };

                return queryObj;
            },
            auth: {
                getUser: function() {
                    return fetch('/api/rest.php?action=get_user')
                        .then(function(r) { return r.json(); })
                        .catch(function() { return { data: { user: null }, error: null }; });
                },
                signOut: function() {
                    window.location.href = '/logout.php';
                    return Promise.resolve();
                }
            }
        };
    }

    // Expose both localDB and legacy window.supabase for complete compatibility
    window.localDB = createLocalClient();
    window.supabase = window.localDB;
})();
