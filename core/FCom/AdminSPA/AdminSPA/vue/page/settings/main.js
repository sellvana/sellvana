define(['lodash', 'jquery', 'deep-diff', 'vue', 'sv-mixin-form', 'json!sv-page-settings-config', 'sv-comp-form-field', 'sv-comp-form-ip-mode'],
    function (_, $, DeepDiff, Vue, SvMixinForm, settingsConfig, SvCompFormField, SvCompFormIpMode) {

    return {
        mixins: [SvMixinForm],
        data: function () {
            return {
                settings: {
                    config: settingsConfig,
                    data: {},
                    data_old: {}
                },
                curTab: null,
                panels: [],
                panelsOpen: {},
                search_value: '',
                search_options: [],
                search_loading: false,
                site: {}
            }
        },
        computed: {
            pageTitle: function () {
                return this.curTab ? this.curTab.label : '';
            }
        },
        methods: {
            fetchData: function (to, from) {
                var vm = this;
                if (this.$route.params[0]) {
                    var path = '/' + this.$route.params[0], i1, l1, n1, i2, l2, n2;
                    for (i1 = 0, l1 = this.settings.config.nav.length; i1 < l1; i1++) {
                        n1 = this.settings.config.nav[i1];
                        if (_.isEmpty(n1.children)) {
                            continue;
                        }
                        for (i2 = 0, l2 = n1.children.length; i2 < l2; i2++) {
                            n2 = n1.children[i2];
                            if (n2.path === path) {
                                this.switchTab(n2);
                            }
                        }
                    }
                }
                if (!from) {
                    this.sendRequest('GET', 'settings/form_data', {}, function (response) {
                        vm.$set(vm.settings, 'data', response.data);
                        // Vue.set(vm.settings, 'data_old', _.extend({}, response.data));
                        vm.updateBreadcrumbs();
                    });
                }
            },
            saveAll: function () {
                var vm = this, data = this.settings.data;//DeepDiff.diff(this.settings.data_old, this.settings.data);
                this.sendRequest('POST', 'settings/form_data', {data: data}, function (response) {
                    // Vue.set(vm.settings, 'data_old', _.extend({}, vm.settings.data));
                });
            },
            switchTab: function (tab) {
                this.curTab = tab;
                this.$router.push('/settings' + tab.path);
                this.updateBreadcrumbs(tab.label, tab.path);
                // this.$store.commit('setData', {curPage: {
                //     label: tab.label,
                //     breadcrumbs: [
                //         {nav:'/system', label: 'System', icon_class:'fa fa-cog'},
                //         {link:'/settings', label: 'Settings'}
                //     ]
                // }});
            },
            togglePanel: function (panel, force) {
                this.$set(this.panelsOpen, panel.path, force ? true : !this.panelsOpen[panel.path]);
            },
            searchLimitText: function (count) {
                return this._((('and {count} other results')), {count:count});
            },
            search: function (query, loading) {
                var i, j, form, field, found = [], cnt = 0, queryLower = query.toLowerCase();
                // loading(true);
                this.search_loading = true;
                for (i in this.settings.config.forms) {
                    form = this.settings.config.forms[i];
                    if (!form || !form.config || !form.config.fields) {
                        continue;
                    }
                    for (j = 0; j < form.config.fields.length; j++) {
                        field = form.config.fields[j];
                        if (!query
                            || (form.label && form.label.toLowerCase().indexOf(queryLower) !== -1)
                            || (field.label && field.label.toLowerCase().indexOf(queryLower) !== -1)
                        ) {
                            found.push({form: i, field: field.name, label: form.label + ' > ' + field.label});
                            cnt++;
                        }
                        if (cnt === 10) {
                            break;
                        }
                    }
                    if (cnt === 10) {
                        break;
                    }
                }
                this.search_options = found;
                this.search_loading = false;
            },
            searchCustomLabel: function (value) {

            },
            searchSelect: function (value) {
                var parts = value.form.split('/'), path, i1, l1, n1, i2, l2, n2, i3, l3, n3;

                for (i1 = 0, l1 = this.settings.config.nav.length; i1 < l1; i1++) {
                    path = '/' + parts[1];
                    n1 = this.settings.config.nav[i1];
                    if (n1.path !== path || !n1.children) {
                        continue;
                    }
                    path += '/' + parts[2];
                    for (i2 = 0, l2 = n1.children.length; i2 < l2; i2++) {
                        n2 = n1.children[i2];
                        if (n2.path !== path || !n2.children) {
                            continue;
                        }
                        this.switchTab(n2);
                        path += '/' + parts[3];
                        for (i3 = 0, l3 = n2.children.length; i3 < l3; i3++) {
                            n3 = n2.children[i3];
                            if (n3.path !== path) {
                                continue;
                            }
                            this.togglePanel(n3, true);
                        }
                    }
                }
            },
            updateBreadcrumbs: function (page, link) {
                var curPage;
                if (page) {
                    curPage = {
                        link: '/settings' + (link || ''),
                        label: page,
                        breadcrumbs: [
                            {nav:'/system', label: 'System', icon_class:'fa fa-cog'},
                            {nav:'/settings', label: 'Settings'}
                        ]
                    }
                } else {
                    curPage = {
                        link: '/settings',
                        label: 'Settings',
                        breadcrumbs: [
                            {nav:'/system', label: 'System', icon_class:'fa fa-cog'}
                        ]
                    };
                }
                this.$store.commit('setData', {curPage: curPage});
            }
        },
        watch: {
            curTab: function (tab) {
                if (!tab.children) {
                    this.panels = {};
                    return;
                }
                var vm = this, deps = [], i, j, panel, paths = [], roles = [], panels = {};
                this.panels = {};
                for (i = 0; i < tab.children.length; i++) {
                    panel = tab.children[i];
                    deps = deps.concat(panel.require); // combine all requires to load at the same time
                    for (j = 0; j < panel.require.length; j++) {
                        paths.push(panel.path); // to know which dep belongs to which panel, by path
                        roles.push(j); // 0 - component, 1 - template
                    }
                    panels[panel.path] = panel;
                }
                require(deps, function () {
                    for (var i = 0; i < arguments.length; i++) {
                        var arg = arguments[i];
                        if (roles[i] === 0) { // component
                            if (arg) {
                                panels[paths[i]].component = arg;
                            } else {
                                panels[paths[i]].component = {
                                    props: ['settings']
                                };
                            }
                        } else { // template
                            if (arg) {
                                panels[paths[i]].component.template = arg;
                            }
                        }

                    }
                    vm.panels = panels;
                    //Vue.set(this.panelsOpen, tab.children[0].path, true);
                });
            }
        }
    };
});