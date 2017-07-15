define(['sv-mixin-grid', 'json!sv-page-catalog-products-grid-config'], function (SvMixinGrid, gridConfig) {
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
                link: '/catalog/products',
                label: 'Products',
                breadcrumbs: [
                    {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'}
                ]
            }});
        },
        methods: {
            doBulkAction: function (type, args) {
                this.doDefaultBulkAction(type, args);
                console.log(type, args);
            }
        }
    };
});