define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-catalog-fields-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    var Component = {
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

    return Component;
});