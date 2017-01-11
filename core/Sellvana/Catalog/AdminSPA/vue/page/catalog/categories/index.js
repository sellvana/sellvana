define(['lodash', 'sv-hlp', 'sv-comp-tree', 'sv-comp-grid'/*, 'json!sv-page-catalog-category-products-grid-config'*/],
    function (_, SvHlp, SvCompTree, SvCompGrid, gridConfig) {

    var defForm = {
        options: {},
        updates: {},
        tabs: [],

        product: {}
    };

    return {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.form],
        data: function () {
            return {
                tree: {},
                form: defForm
            }
        },
        computed: {
            curNode: function () {
                return {
                    id: this.form.category && this.form.category.id ? this.form.category.id : 1
                };
            }
        },
        components: {
            'sv-comp-tree': SvCompTree
        },
        methods: {
            updateBreadcrumbs: function () {
                var link = '/catalog/categories', label = '';
                if (this.form && this.form.category) {
                    link += '?id=' + this.form.category.id;
                    label = this.form.category.full_name.replace(/\|/, ' > ');
                }
                this.$store.commit('setData', {curPage: {
                    link: link,
                    label: label,
                    breadcrumbs: [
                        {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'},
                        {link:'/catalog/categories', label:'Navigation'}
                    ]
                }});
            },
            treeEvent: function (event) {
                console.log(event);
                switch (event.type) {
                    case 'select':
                        SvHlp.router.push('/catalog/categories?id=' + event.node.id);
                        break;
                }
            },
            fetchData: function ($route) {
                var vm = this, params = {};
                params.id = $route.query ? $route.query.id : 1;
                if (_.isEmpty(this.tree)) {
                    params.tree = 1;
                }
                SvHlp.sendRequest('GET', 'categories/form_data', params, function (response) {
                    if (response.tree) {
                        vm.tree = response.tree[0];
                    }
                    if (response.form) {
                        vm.processFormDataResponse(response);
                        vm.updateBreadcrumbs();
                    }
                });
            }
        }
    };
});