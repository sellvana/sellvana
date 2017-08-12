define(['sv-mixin-grid', 'json!sv-page-sales-custom-states-grid-config'], function (SvMixinGrid, gridConfig) {
    return {
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
                link: '/sales/custom-states',
                label: 'Custom States',
                breadcrumbs: [
                    {nav:'/sales', label:'Sales', icon_class:'fa fa-line-chart'}
                ]
            }});
        }
    };
});