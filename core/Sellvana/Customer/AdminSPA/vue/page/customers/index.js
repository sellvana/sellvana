define(['sv-mixin-grid', 'json!sv-page-customers-grid-config'], function (SvMixinGrid, gridConfig) {
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
                link: '/customers/customers',
                label: 'Customers',
                breadcrumbs: [
                    {nav:'/customers', label:'Customers', icon_class:'fa fa-user'},
                ]
            }});
        }
    };
});