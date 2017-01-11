define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-catalog-products-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
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
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/catalog/products',
                label: 'Products',
                breadcrumbs: [
                    {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'}
                ]
            }});
        }
    };
});