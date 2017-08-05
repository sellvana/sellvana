define(['vue', 'sv-mixin-form'], function (Vue, SvMixinForm) {

	return {
		mixins: [SvMixinForm],
		data: function () {
			return {
				field: {}
			}
		},
		methods: {
            updateBreadcrumbs: function (label) {
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: label,
                    breadcrumbs: [
                        {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'},
                        {link:'/catalog/fields', label:'Fields'}
                    ]
                }});
            },
			fetchData: function () {
                var orderId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'catalogfields/form_data', {id: orderId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs(vm.form.field.field_name);
                });
			},
			doDelete: function () {
                var vm = this;
				if (!confirm(this._(('Are you sure you want to delete this field?')))) {
					return;
				}
				this.sendRequest('POST', 'catalogfields/form_delete', {id: this.form.field.id}, function (response) {
					if (response.status) {
                        vm.$router.push('/catalog/fields');
					}
				});
			},
			save: function (stayOnPage) {
				var vm = this;
                this.$store.commit('actionInProgress', stayOnPage ? 'save-continue' : 'save');
				
				if (!this.validateForm()) {
                    this.$store.commit('actionInProgress', false);
					return;
				}
				this.sendRequest('POST', 'catalogfields/form_data?id=' + this.form.field.id, this.form.field, function (response) {
					if (response.form) {
                        vm.processFormDataResponse(response);
                        vm.updateBreadcrumbs(vm.form.field.field_name);
					}
                    for (var i in response.form) {
                        //Vue.set(vm.form, i, response.form[i]);
                    }
                    if (!vm.form.updates) {
						//Vue.set(vm.form, 'updates', {});
					}
                    if (!stayOnPage) {
                        vm.$router.push('/catalog/fields');
                    }
                    vm.$store.commit('actionInProgress', false);
				})
		    }
		}
    };
});