define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-vendors-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    return {
        store: SvHlp.store,
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