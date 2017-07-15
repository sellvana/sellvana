define(['lodash', 'vue', 'sv-store', 'sv-router', 'nprogress', 'sv-app-data'], function (_, Vue, store, router, NProgress, SvAppData) {

    function translate(text, args) {
        if (_.isObject(text)) {
            args = _.cloneDeep(text);
            text = text.text || text[0];
        }
        var translated = typeof text === 'string' ? text : '' + text; // implement Sellvana logic
        return translated.supplant(args);
    }

    var commonMixin = {
        store: store,
        router: router,
        data: function () {
            return {
                action_in_progress: ''
            };
        },
        computed: {
            ////////////////////// UI URLS
            assetUrl: function () {
                var vm = this;
                return function (module, path) {
                    var modules = SvAppData.modules;
                    return modules[module] ? modules[module].src_root + '/AdminSPA/' + path : '';
                }
            },
            componentUrl: function() {
                var vm = this;
                return function (module, path, type) {
                    var modules = SvAppData.modules;
                    type = type || 'component';
                    var url = modules[module].src_root + '/AdminSPA/vue/' + type + '/' + path;
                    if (path.match(/\.html$/)) {
                        url = 'text!' + url;
                    }
                    return url;
                }
            },

            //////////////////////// UI COMPONENTS
            ddOpen: function () {
                return function(ddName, ddRoot) {
                    ddRoot = ddRoot || store.state.ui;
                    return ddRoot.ddCurrent === ddName;
                };
            },
            pageClickCounter: function () {
                return store.state.ui.pageClickCounter;
            },

            ///////////////////////// MISC
            select2Options: function () {
                return function (options) {
                    var kvs = [], i;
                    for (i in options) {
                        kvs.push({id: i, text: options[i]});
                    }
                    return kvs;
                }
            },
            length: function () {
                return function (value) {
                    if (typeof value === 'object') {
                        return Object.keys(value).length;
                    } else if (value.isArray()) {
                        return value.length;
                    } else {
                        return 1;
                    }
                }
            },
            svgIconLink: function () {
                return function (icon) {
                    return '<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="' +
                        SvAppData.modules.FCom_AdminSPA.src_root + '/AdminSPA/img/icons.svg#' + icon + '"></use>';
                }
            }
        },
        methods: {
            _: function (text, args) {
                if (_.isObject(text)) {
                    args = _.cloneDeep(text);
                    text = text.text || text[0];
                }
                var translated = typeof text === 'string' ? text : '' + text; // TODO: implement Sellvana logic
                return translated.supplant(args);
            },

            ddToggle: function (ddName, ddRoot) {
                if (ddRoot) {
                    Vue.set(ddRoot, 'ddCurrent', ddRoot.ddCurrent === ddName ? false : ddName);
                } else {
                    store.commit('ddToggle', ddName);
                }
            },
            ddStay: function () { /* dummy */ },

            sendRequest: function(method, path, request, success, error, lastTry) {
                var vm = this, data = request;
                if (!_.isObject(data) || _.isArrayLike(data)) {
                    console.log('Invalid request', request);
                    return;
                }
                if (method === 'GET' && request) {
                    path += (path.match(/\?/) ? '&' : '?') + $.param(data);
                    data = null;
                }
                if (method === 'POST' || method === 'PUT' || method === 'DELETE') {
                    data['X-CSRF-TOKEN'] = store.state.csrfToken;
                }
                if (lastTry) {
                    data._last_try = lastTry;
                    NProgress.set(.5);
                } else {
                    NProgress.start();
                }
                return $.ajax({
                    method: method,
                    url: store.state.env.root_href.replace(/\/$/, '') + '/' + path.replace(/^\//, ''),
                    data: data,//JSON.stringify(data),
                    success: function (response) {
                        NProgress.inc();
                        vm.processResponse(response);
                        if (response._retry && !lastTry) {
                            return vm.sendRequest(method, path, data, success, error, true);
                        }
                        if (success) {
                            success(response);
                        }
                        NProgress.done();
                    },
                    error: function (response) {
                        NProgress.inc();
                        vm.processResponse(response);
                        if (error) {
                            error(response);
                        }
                        NProgress.done();
                    }
                });
            },

            processResponse: function(response) {
                var storeData = {};
                if (response._login) {
                    storeData.user = {};
                    router.push('/login');
                }
                if (response._user) {
                    storeData.user = response._user;
                }
                if (response._personalize) {
                    storeData.personalize = response._personalize;
                }
                if (response._permissions) {
                    storeData.permissions = response._permissions;
                }
                if (response._local_notifications) {
                    storeData.localNotifications = response._local_notifications; //TODO: merge
                }
                if (response._nav) {
                    storeData.navTree = response._nav;
                }
                if (response._csrf_token) {
                    storeData.csrfToken = response._csrf_token;
                }
                if (response._messages && !response._retry) {
                    storeData.messages = store.state.messages.concat(response._messages);
                }
                store.commit('setData', storeData);

                if (response._redirect) {
                    router.push(response._redirect);
                }
            }
        }
    };

    return commonMixin;
})