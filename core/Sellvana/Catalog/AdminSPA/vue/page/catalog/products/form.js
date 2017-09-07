define(['lodash', 'vue', 'sv-mixin-form'], function (_, Vue, SvMixinForm) {

	return {
		mixins: [SvMixinForm],
		methods: {
            updateBreadcrumbs: function (label) {
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: _.get(this.form, 'config.title', (('Loading...'))),
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
				var postData = { product: this.form.product };
				this.sendRequest('POST', 'products/form_data?id=' + this.form.product.id, postData, function (response) {
					if (response.form) {
                        vm.processFormDataResponse(response);
                        vm.updateBreadcrumbs();
					}
                    if (!response.error && !stayOnPage) {
                        vm.$router.push('/catalog/products');
                    }
                    vm.$store.commit('actionInProgress', false);
				})
		    }
		}
    };
});