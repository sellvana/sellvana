define(['vue', 'sv-mixin-form'], function (Vue, SvMixinForm) {

	return {
		mixins: [SvMixinForm],
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
				if (!confirm(this._(('Are you sure you want to delete this product?')))) {
					return;
				}
				this.sendRequest('POST', 'products/form_delete', {id: this.form.product.id}, function (response) {
					if (response.status) {
                        vm.$router.push('/catalog/products');
					}
				});
			},
			save: function (stayOnPage) {
				var vm = this;
				this.$store.commit('actionInProgress', stayOnPage ? 'save-continue' : 'save');
				if (!this.validateForm()) {
                    this.$store.commit('actionInProgress', false);
                    alert(this._(('Please correct form errors before submitting.')));
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
                        vm.$router.push('/catalog/products');
                    }
                    vm.$store.commit('actionInProgress', false);
				})
		    }
		}
    };
});