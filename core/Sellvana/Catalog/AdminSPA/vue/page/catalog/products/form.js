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
                        {link:'/catalog/products', label:'Products'}
                    ]
                }});
            },
			fetchData: function () {
                var orderId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'products/form_data', {id: orderId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs(vm.form.product.product_name);
                });
			},
			doDelete: function () {
                var vm = this;
				if (!confirm(SvHlp._('Are you sure you want to delete this product?'))) {
					return;
				}
				this.sendRequest('POST', 'products/form_delete', {id: this.form.product.id}, function (response) {
					if (response.status) {
                        vm.$router.go(-1);
					}
				});
			},
			save: function (stayOnPage) {
				var vm = this;
				this.action_in_progress = stayOnPage ? 'save-continue' : 'save';
				
				if (!this.validateForm()) {
                    vm.action_in_progress = false;
					return;
				}
				this.sendRequest('POST', 'products/form_data?id=' + this.form.product.id, this.form.product, function (response) {
					if (response.form) {
                        vm.processFormDataResponse(response);
                        vm.updateBreadcrumbs(vm.form.product.product_name);
					}
                    for (var i in response.form) {
                        //Vue.set(vm.form, i, response.form[i]);
                    }
                    if (!vm.form.updates) {
						//Vue.set(vm.form, 'updates', {});
					}
                    if (!stayOnPage) {
                        vm.$router.go(-1);
                    }
                    vm.action_in_progress = false;
				})
		    }
		}
    };
});