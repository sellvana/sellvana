define(['vue', 'sv-hlp'], function (Vue, SvHlp) {

	return {
		mixins: [SvHlp.mixins.common, SvHlp.mixins.form],
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
				if (!confirm(SvHlp._('Are you sure you want to delete this field?'))) {
					return;
				}
				this.sendRequest('POST', 'catalogfields/form_delete', {id: this.form.field.id}, function (response) {
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
                        vm.$router.go(-1);
                    }
                    vm.action_in_progress = false;
				})
		    }
		}
    };
});