define(['sv-mixin-grid', 'json!sv-page-mailing-lists-grid-config'], function (SvMixinGrid, gridConfig) {
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
                link: '/mailing/lists',
                label: (('Lists')),
                breadcrumbs: [
                    {nav:'/mailing', label: (('Mailing'))}
                ]
            }});
        }
    };

    return Component;
});