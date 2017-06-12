define(['vue', 'sv-hlp'], function (Vue, SvHlp) {

	return {
		mixins: [SvHlp.mixins.common, SvHlp.mixins.form],
		data: function () {
			return {
				product: {},
				product_old: {},
				inventory: {},
				inventory_old: {}
			}
		},
		methods: {
            updateBreadcrumbs: function (label) {
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: label,
                    breadcrumbs: [
                        {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'},
                        {link:'/catalog/inventory', label:'Inventory'}
                    ]
                }});
            },
			fetchData: function () {
                var orderId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'inventory/form_data', {id: orderId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs(vm.form.inventory.inventory_sku);
                });
			}
		}
    };
});