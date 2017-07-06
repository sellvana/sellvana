define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-catalog-products-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
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
                link: '/catalog/products',
                label: 'Products',
                breadcrumbs: [
                    {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'}
                ]
            }});
        },
        methods: {
            doBulkAction: function (act) {
                switch (act.name) {
                    case 'apply':
                        var vm = this, postData = {
                            do: 'bulk-update',
                            ids: Object.keys(this.grid.rows_selected),
                            data: this.grid.popup.form.product
                        };
                        this.sendRequest('POST', this.grid.config.data_url, postData, function (response) {
                            console.log(response);
                        });
                        break;
                    case 'close':
                        this.grid.popup = null;
                        break;
                }
                console.log(act);
            }
        }
    };
});