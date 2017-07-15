define(['sv-mixin-grid', 'sv-comp-grid', 'json!sv-page-modules-grid-config'], function (SvMixinGrid, SvCompGrid, gridConfig) {
    return {
        mixins: [SvMixinGrid],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            };
        },
        methods: {
            doGridAction: function (act) {
                var vm = this;
                switch (act.name) {
                    case 'migrate':
                        this.sendRequest('POST', '/modules/migrate', {}, function (response) {
                            console.log(response);
                        });
                        break;
                    case 'reset_cache':
                        this.sendRequest('POST', '/modules/reset_cache', {}, function (response) {
                            console.log(response);
                        });
                        break;
                }
            }
        },
        created: function () {
            this.$store.commit('setData', {curPage: {
                link: '/modules',
                label: 'Manage Modules',
                breadcrumbs: [
                    {nav:'/modules', label: 'Modules'}
                ]
            }});
        }
    };
});