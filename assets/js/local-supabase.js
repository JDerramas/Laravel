/**
 * local-supabase.js — Autonomous Local MySQL Supabase Drop-In Client
 * Navotas Polytechnic College (NPC) ELMS
 * 
 * Replaces remote Supabase Cloud client with instant local MySQL queries:
 * - 0ms cloud latency
 * - Zero dependency on *.supabase.co
 * - Native Real-time event polling on local MySQL
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
                        _limit = n;
                        return queryObj;
                    },
                    single: function() {
                        _single = true;
                        return executeQuery();
                    },
                    then: function(onFulfilled, onRejected) {
                        return executeQuery().then(onFulfilled, onRejected);
                    },
                    insert: function(rows) {
                        var body = Array.isArray(rows) ? rows : [rows];
                        return fetch('/api/rest.php?table=' + encodeURIComponent(_table), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(body)
                        }).then(function(r) { return r.json(); })
                          .then(function(data) { return { data: data, error: null }; })
                          .catch(function(err) { return { data: null, error: err }; });
                    },
                    update: function(vals) {
                        return {
                            eq: function(col, val) {
                                return fetch('/api/rest.php?table=' + encodeURIComponent(_table) + '&' + encodeURIComponent(col) + '=eq.' + encodeURIComponent(val), {
                                    method: 'PATCH',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(vals)
                                }).then(function(r) { return r.json(); })
                                  .then(function(data) { return { data: data, error: null }; })
                                  .catch(function(err) { return { data: null, error: err }; });
                            }
                        };
                    },
                    delete: function() {
                        return {
                            eq: function(col, val) {
                                return fetch('/api/rest.php?table=' + encodeURIComponent(_table) + '&' + encodeURIComponent(col) + '=eq.' + encodeURIComponent(val), {
                                    method: 'DELETE'
                                }).then(function(r) { return r.json(); })
                                  .then(function(data) { return { data: data, error: null }; })
                                  .catch(function(err) { return { data: null, error: err }; });
                            }
                        };
                    }
                };

                return queryObj;
            },
            channel: function(name) {
                var listeners = [];
                var pollInterval = null;
                var lastCheck = new Date().toISOString();

                var channelObj = {
                    on: function(event, config, callback) {
                        listeners.push({ event: event, config: config, callback: callback });
                        return channelObj;
                    },
                    subscribe: function() {
                        if (!pollInterval) {
                            pollInterval = setInterval(function() {
                                listeners.forEach(function(l) {
                                    var tbl = (l.config && l.config.table) || 'attendance_records';
                                    fetch('/api/realtime.php?table=' + encodeURIComponent(tbl) + '&since=' + encodeURIComponent(lastCheck))
                                        .then(function(r) { return r.json(); })
                                        .then(function(res) {
                                            if (res && res.data && res.data.length > 0) {
                                                lastCheck = res.timestamp || new Date().toISOString();
                                                res.data.forEach(function(row) {
                                                    try {
                                                        l.callback({ eventType: 'INSERT', new: row });
                                                    } catch(e) {
                                                        console.error('[Realtime] callback error:', e);
                                                    }
                                                });
                                            }
                                        })
                                        .catch(function() {});
                                });
                            }, 1500);
                        }
                        return channelObj;
                    },
                    unsubscribe: function() {
                        if (pollInterval) clearInterval(pollInterval);
                    }
                };
                return channelObj;
            },
            auth: {
                getUser: function() {
                    return Promise.resolve({ data: { user: { id: 'usr-current', email: 'user@navotaspolytechniccollege.edu.ph' } }, error: null });
                },
                getSession: function() {
                    return Promise.resolve({ data: { session: { user: { id: 'usr-current' } } }, error: null });
                },
                signOut: function() {
                    window.location.href = '/logout.php';
                    return Promise.resolve({ error: null });
                },
                onAuthStateChange: function(cb) {
                    return { data: { subscription: { unsubscribe: function() {} } } };
                }
            }
        };
    }

    window.createLocalSupabaseClient = createLocalClient;
    window.supabase = {
        createClient: function() {
            return createLocalClient();
        }
    };
})();
