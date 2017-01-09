define(['jquery', 'lodash', 'vue', 'vue-router', 'vuex', 'accounting', 'moment', 'sortable', 'vue-ckeditor', 'vue-select2',
    'ckeditor', 'select2'],
    function ($, _, Vue, VueRouter, Vuex, Accounting, Moment, Sortable, VueCkeditor, VueSelect2) {

        Vue.use(VueRouter);
        Vue.use(Vuex);

        String.prototype.supplant = function (o) {
            return this.replace(/{([^{}]*)}/g, function (a, b) {
                var r = o[b];
                return typeof r === 'string' || typeof r === 'number' ? r : a;
            });
        };

        function translate(original, args) {
            var translated = typeof original === 'string' ? original : '' + original; // implement Sellvana logic
            return translated.supplant(args);
        }

        Vue.directive('sortable', {
            inserted: function(el, binding) {
                el.sortableInstance = Sortable.create(el, binding.value);
            },
            unbind: function (el) {
                el.sortableInstance.destroy();
            }
        });

        Vue.component('ckeditor', VueCkeditor);

        Vue.component('select2', VueSelect2);

        Vue.filter('_', translate);

        Vue.filter('currency', function (value, currencyCode) {
            //TODO: implement config by currencyCode
            var symbol = '$', precision = 2, thousand = ',', decimal = '.', format = '%s%v';
            return Accounting.formatMoney(value, symbol, precision, thousand, decimal, format);
        });

        Vue.filter('date', function (value, format) {
            switch (format || '') {
                case '': format = 'MMMM Do YYYY, h:mm:ss a'; break;
                case 'short': format = 'MM Do \'YY'; break;
            }
            return Moment(value, 'YYYY-MM-DD hh:mm:ss').format(format);
        });

        Vue.filter('ago', function (value) {
            return Moment(value, 'YYYY-MM-DD hh:mm:ss').fromNow();
        });

        Vue.filter('size', function (value) {
            if (typeof value === 'object') {
                return Object.keys(value).length;
            } else if (value.isArray()) {
                return value.length;
            } else {
                return 1;
            }
        });
console.log(SvAppData.nav_tree);
        var store = new Vuex.Store({
            strict: true,
            state: {
                env: SvAppData.env,
                user: SvAppData.user,
                navTree: SvAppData.nav_tree,
                csrfToken: SvAppData.csrf_token,
                favorites: SvAppData.favorites,
                messages: [
                    // {type: 'error', text: 'Error message'},
                    // {type: 'warning', text: 'Warning message'},
                    // {type: 'success', text: 'Success message'},
                    // {type: 'info', text: 'Info message'}
                ],
                curPage: {
                    link: '/',
                    label: '',
                    icon_class: '',
                    breadcrumbs: [
                    ]
                }
            },
            mutations: {
                setData: function (state, data) {
                    for (key in data) {
                        Vue.set(state, key, data[key]);
                    }
                },
                personalizeGridColumn: function (state, data) {
                    var grid = data.grid, col = data.col;
                    if (!state.personalize) {
                        Vue.set(state, 'personalize', {});
                    }
                    if (!state.personalize.grid) {
                        Vue.set(state.personalize, 'grid', {});
                    }
                    if (!state.personalize.grid[grid.config.id]) {
                        Vue.set(state.personalize.grid, grid.config.id, {});
                    }
                    if (!state.personalize.grid[grid.config.id].columns) {
                        Vue.set(state.personalize.grid[grid.config.id], 'columns', {});
                    }
                    if (!state.personalize.grid[grid.config.id].columns[col.field]) {
                        Vue.set(state.personalize.grid[grid.config.id].columns, col.field, 1);
                    } else {
                        Vue.set(state.personalize.grid[grid.config.id].columns, col.field, 0);
                    }
                },
                addFavorite: function (state, fav) {
                    var favs = state.favorites || [];
                    for (var i = 0; i < favs.length; i++) {
                        if (favs[i].link === fav.link) {
                            return;
                        }
                    }
                    favs.push(fav);
                    Vue.set(state, 'favorites', favs);
                },
                removeFavorite: function (state, fav) {
                    var favs = state.favorites;
                    for (var i = 0; i < favs.length; i++) {
                        if (favs[i].link === fav.link) {
                            favs.splice(i, 1);
                            break;
                        }
                    }
                    Vue.set(state, 'favorites', favs);
                },
                logout: function (state) {
                    Vue.set(state, 'user', {});
                },
                addMessage: function (state, message) {
                    state.messages.push(message);
                },
                removeMessage: function (state, message) {
                    for (var i = 0, l = state.messages.length; i < l; i++) {
                        if (_.isEqual(state.messages[i], message)) {
                            state.messages.splice(i, 1);
                            break;
                        }
                    }
                }
            }
        });

        store.registerModule('ui', {
            //namespaced: true,
            state: {
                ddCurrent: false,
                mainNavOpen: true,
                pageClickCounter: 0
            },
            mutations: {
                ddToggle: function (state, ddName) {
                    state.ddCurrent = state.ddCurrent === ddName ? false : ddName;
                },
                mainNavToggle: function (state) {
                    state.mainNavOpen = !state.mainNavOpen;
                },
                pageClick: function (state) {
                    state.pageClickCounter++;
                },
                windowResize: function (state, width) {
                    state.mainNavOpen = width > 1024;
                }
            }
        });

        function routeView(args) {
            return function (resolve, reject) {
                require(args, function (component, template) {
//console.log(args, component, template);
                    if (!component) {
                        component = {};
                    }
                    if (template) {
                        component.template = template;
                    }
                    resolve(component);
                });
            }
        }

        function sendRequest(method, path, request, success, error) {
            var data = request;
            if (method === 'GET' && request) {
                path += (path.match(/\?/) ? '&' : '?') + $.param(data);
                data = null;
            }
            if (method === 'POST' || method === 'DELETE') {
                data['X-CSRF-TOKEN'] = store.state.csrfToken;
            }
            return $.ajax({
                method: method,
                url: store.state.env.root_href + path,
                data: data,//JSON.stringify(data),
                success: function (response) {
                    processResponse(response);
                    success && success(response);
                },
                error: function (response) {
                    processResponse(response);
                    error && error(response);
                }
            });
        }

        function processResponse(response) {
            var storeData = {};
            if (response._login) {
                storeData.user = {};
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
            if (response._messages) {
                storeData.messages = store.state.messages.concat(response._messages);
            }
            store.commit('setData', storeData);

            if (response._redirect) {
                //console.log(response._redirect);
            }
        }

        var routes = [];
        for (var i in SvAppData.routes) {
            var r = SvAppData.routes[i], route = {path: r.path, component: routeView(r.require)};
            if (r.children) {
                route.children = [];
                for (var j in r.children) {
                    var child = r.children[j];
                    route.children.push({path: child.path, component: routeView(child.require)});
                }
            }
            routes.push(route);
        }
        var router = new VueRouter({
            routes: routes
        });

        var mixins = {
            common: {
                store: store,
                data: function () {
                    return {

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
                        return this.$store.state.ui.pageClickCounter;
                    },

                    ///////////////////////// TRANSLATIONS
                    _: function () {
                        return translate;
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
                    }
                },
                methods: {
                    ddToggle: function (ddName, ddRoot) {
                        if (ddRoot) {
                            Vue.set(ddRoot, 'ddCurrent', ddRoot.ddCurrent === ddName ? false : ddName);
                        } else {
                            store.commit('ddToggle', ddName);
                        }
                    },
                    ddStay: function () { /* dummy */ }
                }
            },
            form: {
                data: function () {
                    return {
                        tab: false
                    };
                },
                computed: {
                    getOption: function () {
                        return function (type, value) {
                            if (!this.form.options[type]) {
                                return {};
                            }
                            if (!value) {
                                return this.form.options[type];
                            }
                            return this.form.options[type][value];
                        }
                    }
                },
                methods: {
                    switchTab: function (tab) {
                        this.tab = tab;
                    },
                    goBack: function () {
                        this.$router.go(-1);
                    },
                    addUpdates: function (part) {
                        Vue.set(this.form, 'updates', _.extend({}, this.form.updates, part));
                    },
                    processFormDataResponse: function (response) {
                        processResponse(response);

                        if (!response.form.updates) {
                            response.form.updates = {};
                        }

                        var deps = [], i, l, vm = this;
                        for (i = 0, l = response.form.tabs.length; i < l; i++) {
                            deps.push(response.form.tabs[i].component);
                        }
                        require(deps, function () {
                            var tabs = response.form.tabs
                            for (i = 0, l = response.form.tabs.length; i < l; i++) {
                                Vue.set(vm.form.tabs[i], 'component_config', arguments[i]);
                            }
                            vm.switchTab(response.form.tabs[0].name);
                        });
                        Vue.set(this, 'form', response.form);
                    }
                },
                watch: {
                    '$route': 'fetchData'
                },
                created: function () {
                    this.updateBreadcrumbs(translate('Loading data...'));
                    this.fetchData();
                },
                beforeRouteLeave: function (to, from, next) {
                    // TODO: doesn't trigger on route args change (?id=5)
                    if (Object.keys(this.form.updates).length > 1) {
                        if (!confirm('There are unsaved changes, are you sure you want to leave?')) {
                            console.log(this.form.updates);
                            next(false);
                        }
                    } else {
                        next();
                    }
                }
            }
        };

        $(window).resize(function (ev) {
            store.commit('windowResize', $(window).width());
        });

        return {
            _: translate,
            sendRequest: sendRequest,
            processResponse: processResponse,
            mixins: mixins,
            router: router,
            store: store
        }
    });