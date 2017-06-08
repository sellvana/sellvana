define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-catalog-inventory-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    return {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.grid],
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
                link: '/catalog/inventory',
                label: 'Inventory',
                breadcrumbs: [
                    {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'}
                ]
            }});
        }
    };
});