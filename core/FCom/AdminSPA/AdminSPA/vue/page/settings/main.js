define(['vue', 'sv-hlp', 'json!sv-page-settings-config'], function (Vue, SvHlp, settingsConfig) {

    return {
        store: SvHlp.store,
        mixins: [SvHlp.mixins.common],
        data: function () {
            return {
                settings: {
                    config: settingsConfig,
                    data: {}
                },
                curTab: null,
                panels: [],
                panelsOpen: {}
            }
        },
        computed: {
            pageTitle: function () {
                return this.curTab ? this.curTab.label : '';
            }
        },
        methods: {
            fetchData: function () {
                var vm = this;
                SvHlp.sendRequest('GET', 'settings/data', {}, function (response) {
                    Vue.set(vm.settings, 'data', response.data);
                });
            },
            switchTab: function (tab) {
                this.curTab = tab;
            },
            togglePanel: function (panel) {
                Vue.set(this.panelsOpen, panel.path, !this.panelsOpen[panel.path]);
            }
        },
        created: function () {
            this.$store.commit('setData', {curPage: {
                link: '/settings',
                label: 'Settings',
                breadcrumbs: [
                    {nav:'/system', label: 'System', icon_class:'fa fa-cog'}
                ]
            }});
            this.fetchData();
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