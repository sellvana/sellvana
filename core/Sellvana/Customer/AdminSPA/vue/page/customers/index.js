define(['sv-mixin-common', 'sv-comp-grid', 'json!sv-page-customers-grid-config'], function (SvMixinCommon, SvCompGrid, gridConfig) {
    return {
        mixins: [SvMixinCommon],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            }
        },
        components: {
            'sv-comp-grid': SvCompGrid
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