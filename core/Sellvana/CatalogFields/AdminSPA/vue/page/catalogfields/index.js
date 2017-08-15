define(['sv-mixin-grid', 'sv-comp-grid', 'json!sv-page-catalog-fields-grid-config'], function (SvMixinGrid, SvCompGrid, gridConfig) {
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
        created: function () {
            this.$store.commit('setData', {curPage: {
                link: '/catalog/fields',
                label: 'Custom Fields',
                breadcrumbs: [
                    {nav:'/catalog', label:'Catalog'}
                ]
            }});
        }
    };
});