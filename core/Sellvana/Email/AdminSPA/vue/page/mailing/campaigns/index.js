define(['sv-mixin-grid', 'json!sv-page-mailing-campaigns-grid-config'], function (SvMixinGrid, gridConfig) {
    var Component = {
        mixins: [SvMixinGrid],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            }
        },
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/mailing/campaigns',
                label: (('Campaigns')),
                breadcrumbs: [
                    {nav:'/mailing', label: (('Mailing'))}
                ]
            }});
        }
    };

    return Component;
});