define(['sv-mixin-grid', 'json!sv-page-vendors-grid-config'], function (SvMixinGrid, gridConfig) {
    return {
        mixins: [SvMixinGrid],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            }
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