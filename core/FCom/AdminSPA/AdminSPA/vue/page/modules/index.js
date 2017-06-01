define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-modules-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    return {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.grid],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            };
        },
        methods: {
            checkUpdates: function () {

            },
            runMigrationScripts: function () {

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