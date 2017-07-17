define(['sv-mixin-common', 'sv-comp-grid', 'json!sv-page-vendors-grid-config'], function (SvMixinCommon, SvCompGrid, gridConfig) {
    return {
        mixins: SvMixinCommon,
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
        methods: {
            addVendor: function () {
                this.$router.push('/vendors/form');
            }
        },
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/catalog/vendors',
                label: 'Vendors',
                breadcrumbs: [
                    {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'},
                ]
            }});
        }
    };
});