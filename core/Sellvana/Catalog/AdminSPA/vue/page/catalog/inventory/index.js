define(['sv-mixin-grid', 'sv-comp-grid', 'json!sv-page-catalog-inventory-grid-config'], function (SvMixinGrid, SvCompGrid, gridConfig) {
    return {
        mixins: [SvMixinGrid],
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