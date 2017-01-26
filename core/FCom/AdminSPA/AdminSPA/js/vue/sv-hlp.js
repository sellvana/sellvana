define(['jquery', 'lodash', 'vue', 'vue-router', 'vuex', 'accounting', 'moment', 'sortable',
        'vue-ckeditor', 'vue-select2', 'spin', 'ladda', 'nprogress',
        'sv-comp-form-layout', 'sv-comp-form-translations',
        'ckeditor', 'select2'],
    function ($, _, Vue, VueRouter, Vuex, Accounting, Moment, Sortable,
              VueCkeditor, VueSelect2, Spin, Ladda, NProgress,
              SvCompFormLayout, SvCompFormTranslations
    ) {

        Vue.use(VueRouter);
        Vue.use(Vuex);

        function translate(original, args) {
            var translated = typeof original === 'string' ? original : '' + original; // implement Sellvana logic
            return translated.supplant(args);
        }

        String.prototype.supplant = function (o) {
            return this.replace(/{([^{}]*)}/g, function (a, b) {
                var r = o[b];
                return typeof r === 'string' || typeof r === 'number' ? r : a;
            });
        };

        String.prototype._ = function (args) { return translate(this, args); }

        Vue.directive('sortable', {
            inserted: function(el, binding) {
                el.sortableInstance = Sortable.create(el, binding.value);
            },
            unbind: function (el) {
                el.sortableInstance.destroy();
            }
        });

        Vue.directive('ladda', {
            bind: function (el, binding) {
                var $el = $(el);
                $el.addClass('ladda-button').wrapInner('<span class="ladda-label"></span>');
                if (!$el.attr('data-style')) {
                    $el.attr('data-style', binding.value.style || 'zoom-out');
                }
                if (!$el.attr('data-spinner-size')) {
                    $el.attr('data-spinner-size', binding.value.spinner_size || 20);
                }

                el.ladda = Ladda.create(el);
            },
            update: function (el, binding) {
                if (binding.value.on && !binding.oldValue.on) {
                    el.ladda.start();
                }  else if (!binding.value.on && binding.oldValue.on) {
                    el.ladda.stop();
                }
                if (_.isNumber(binding.value.progress)) {
                    el.ladda.setProgress(binding.value.progress);
                }
            }
        });

        Vue.component('ckeditor', VueCkeditor);

        Vue.component('select2', VueSelect2);

        Vue.component('jsontree', {
            template: '<div></div>',
            props: ['json'],
            mounted: function () {
                $(this.$el).html(JSONTree.create(this.json));
            },
            watch: {
                json: function (json) {
                    $(this.$el).html(JSONTree.create(this.json));
                }
            }
        });

        Vue.component('checkbox', {
            template: '<label><input type="checkbox" v-model="internal"><div class="checkbox-block" :style="blockStyle"><div class="checkbox-elem" :style="elemStyle"></div></div></label>',
            props: ['value', 'height', 'width'],
            data: function () {
                return {
                    internal: null
                }
            },
            computed: {
                blockStyle: function () {
                    var style = {};
                    if (this.height) {
                        style.height = this.height + 'px';
                    }
                    if (this.width) {
                        style.width = this.width + 'px';
                    }
                    return style;
                },
                elemStyle: function () {
                    var style = {};
                    if (this.height) {
                        style.height = style.width = (this.height - 2) + 'px';
                    }
                    return style;
                }
            },
            created: function () {
                this.internal = this.value;
            },
            watch: {
                value: function (value) {
                    this.internal = this.value;
                },
                internal: function (internal) {
                    this.$emit('input', internal);
                }
            }
        });

        Vue.filter('@', function (path) { return _.at(this, [path])[0]; });

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

        function sendRequest(method, path, request, success, error, lastTry) {
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
                    processResponse(response);
                    if (response._retry && !lastTry) {
                        return sendRequest(method, path, data, success, error, true);
                    }
                    if (success) {
                        success(response);
                    }
                    NProgress.done();
                },
                error: function (response) {
                    NProgress.inc();
                    processResponse(response);
                    if (error) {
                        error(response);
                    }
                    NProgress.done();
                }
            });
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

        function processResponse(response) {
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

        requirejs.onError = function (err) {
console.log('onError', err.xhr);
            if (err.xhr) {
                if (err.xhr.status === 401) {
                    router.push('/login');
                }
            } else {
                console.log(err);
            }
        };

        requirejs.config({
            config: {
                text: {
                    onXhrComplete: function (xhr, url) {
                        var response = xhr.response;
                        if (_.isString(response) && response[0] === '{') {
                            response = JSON.parse(response);
                        } else {
                            return;
                        }
                        if (response._login) {
                            router.push('/login');
                        }
                    }
                }
            }
        });

        var mixins = {
            common: {
                store: store,
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
                    ddStay: function () { /* dummy */ },
                    sendRequest: sendRequest
                }
            },
            form: {
                data: function () {
                    return {
                        tab: false,
                        form: {
                            options: {},
                            updates: {},
                            tabs: []
                        }
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
                    },
                    formTabLabel: function () {
                        if (!this.form || !this.form.tabs || _.isEmpty(this.form.tabs)) {
                            return '';
                        }
                        for (var i = 0, l = this.form.tabs.length; i < l; i++) {
                            if (this.form.tabs[i].name === this.tab) {
                                return this.form.tabs[i].label;
                            }
                        }
                        return '';
                    }
                },
                methods: {
                    switchTab: function (tab) {
                        this.tab = tab;
                        //this.$router.go({query: {tab: tab}});
                    },
                    goBack: function () {
                        this.$router.go(-1);
                    },
                    addUpdates: function (part) {
                        Vue.set(this.form, 'updates', _.extend({}, this.form.updates, part));
                    },
                    processFormDataResponse: function (response) {
                        if (!response.form) {
                            console.log('No form object in response', response);
                            return;
                        }
                        if (!response.form.updates || _.isArrayLike(response.form.updates)) {
                            response.form.updates = {};
                        }

                        var deps = [], i, l, vm = this;
                        for (i = 0, l = response.form.tabs.length; i < l; i++) {
                            deps.push(response.form.tabs[i].component);
                        }
                        require(deps, function () {
                            var tabs = response.form.tabs;
                            for (i = 0, l = response.form.tabs.length; i < l; i++) {
                                Vue.set(vm.form.tabs[i], 'component_config', arguments[i]);
                            }
                            if (!this.tab) {
                                vm.switchTab(response.form.tabs[0].name);
                            }
                        });
                        Vue.set(this, 'form', response.form);
                    }
                },
                watch: {
                    '$route': 'fetchData'
                },
                created: function () {
                    this.updateBreadcrumbs(translate('Loading data...'));
                    this.fetchData(this.$route);
                },
                beforeRouteLeave: function (to, from, next) {
                    // TODO: doesn't trigger on route args change (?id=5)
                    if (this.form.updates && Object.keys(this.form.updates).length > 1) {
                        if (!confirm('There are unsaved changes, are you sure you want to leave?')) {
                            console.log(this.form.updates);
                            next(false);
                        } else {
                            next();
                        }
                    } else {
                        next();
                    }
                }
            },
            formTab: {
                components: {
                    'sv-comp-form-layout': SvCompFormLayout,
                    'sv-comp-form-translations': SvCompFormTranslations
                },
                data: function () {
                    return {
                        currentTranslation: false
                    }
                },
                computed: {
                    fieldClass: function () {
                        var vm = this;
                        return function (field) {
                            return {};
                        }
                    }
                },
                methods: {
                    validate: function (field, value) {
                        var config = this.formValidationConfig;
                        if (!config.fields || !config.fields[field]) {
                            return;
                        }
                        var tab = config.fields[field].tab;
                        for (var i = 0, l = this.form.tabs.length; i < l; i++) {
                            if (this.form.tabs[i].name === tab) {
                                Vue.set(this.form.tabs[i], 'edited', true);
                                break;
                            }
                        }
                    },
                    toggleTranslations: function (field) {
                        this.currentTranslation = {
                            field: field
                        };
                    },
                    processTranslationsEvent: function (args) {
                        console.log(args);
                    }
                }
            }
        };

        $(window).resize(function (ev) {
            store.commit('windowResize', $(window).width());
        });

        Vue.component('dropdown', {
            mixins: [mixins.common],
            props: ['id', 'label'],
            template: '<div class="dropdown action" :class="{open:ddOpen(id)}">' +
                '<a href="#" class="dropdown-toggle" @click.prevent.stop="ddToggle(id)">' +
                    '<span>{{label}}</span><span class="caret-back"><b class="caret"></b></span></a>' +
                '<div class="dropdown-menu" @click.stop><slot></slot></div></div>'
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