define(['jquery', 'vue', 'vuex', 'select2', 'accounting', 'moment'], function ($, Vue, Vuex, Bootstrap, Accounting, Moment) {

    // Translations, usage: <t>String<t> or <t tag="div" :args="{p:page, m:max}">Page {p} of {m}</t>
    //TODO: implement Sellvana logic
    Vue.component('t', {
        props: {
            'tag' : { type: String, default: 'span' },
            'args': { type: Object, default: function () { return {}; } }
        },
        render: function (h) {
            var data = {}, children = this.$slots.default;
            /*
             var translated = _(result[0].text, this.args);
             if (!translated.match(/^\{\{/)) {
                children = translated;
             }
             */
            return h(this.tag, data, children);
        }
    });

    Vue.component('select2', {
        props: ['options', 'value'],
        template: '<select><slot></slot></select>',
        mounted: function () {
            var vm = this;
            $(this.$el).val(this.value).select2({ data: this.options }).on('change', function () { vm.$emit('input', this.value); });
        },
        watch: {
            value: function (value) { $(this.$el).select2('val', value); },
            options: function (options) { $(this.$el).select2({ data: options }); }
        },
        destroyed: function () { $(this.$el).off().select2('destroy'); }
    });

    // Vue.component('sv-dropdown', {
    //     props: {
    //         'tag' : { type: String, default: 'li' },
    //         'args': { type: Object, default: function () { return {}; } }
    //     },
    //     render: function (h) {
    //         var data = {}, children = this.$slots.default;
    //         return h(this.tag, data, children);
    //     }
    // });

    Vue.filter('_', function (value, args) {
        //TODO: implement
        return value;
    });

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

    Vue.use(Vuex);
    var store = new Vuex.Store({
        strict: true,
        state: {
            env: {},
            user: {},
            navTree: {},
            curPage: {
                link: '/',
                label: '',
                icon_class: '',
                breadcrumbs: [
                ]
            },
            favorites: []
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
            }
        }
    });

    store.registerModule('ui', {
        //namespaced: true,
        state: {
            ddCurrent: false,
            mainNavOpen: true
        },
        mutations: {
            ddToggle: function (state, ddName) {
                state.ddCurrent = state.ddCurrent === ddName ? false : ddName;
            },
            mainNavToggle: function (state) {
                state.mainNavOpen = !state.mainNavOpen;
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

    var modules = {};

    function processResponse(response) {
        var storeData = {};
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
console.log(storeData);
        store.commit('setData', storeData);

        if (response._redirect) {
            console.log(response._redirect);
        }
    }

    return {
        data: {

        },
        methods: {
            routeView: routeView,
            setModules: function (newModules) { modules = newModules; },
            sendRequest: function (method, path, request, success, error) {
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
            },
            processResponse: processResponse
        },
        views: {

        },
        mixins: {
            common: {
                data: function () {
                    return {

                    };
                },
                computed: {
                    ////////////////////// UI URLS
                    assetUrl: function () {
                        return function (module, path) {
                            return modules[module] ? modules[module].src_root + '/AdminSPA/' + path : '';
                        }
                    },
                    componentUrl: function() {
                        return function (module, path, type) {
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
                        return function(ddName) {
                            return store.state.ui.ddCurrent === ddName;
                        };
                    },

                    ///////////////////////// TRANSLATIONS
                    _: function () {
                        return function (str, args) {
                            //TODO: implement Sellvana logic
                            return str;
                        }
                    }
                },
                methods: {
                    ddToggle: function (ddName) {
                        store.commit('ddToggle', ddName);
                    },
                    ddStay: function () { /* dummy */ }
                }
            },
            i18n: {
                computed: {
                }
            }
        },
        store: store,
        _: function (str, args) {
            // implement translation logic
            return str;
        }
    };
});