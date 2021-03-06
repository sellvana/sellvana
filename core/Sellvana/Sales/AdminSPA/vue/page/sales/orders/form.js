define(['vue', 'sv-mixin-form'],
	   function (Vue, SvMixinForm) {

	var defForm = {
		options: {},
        updates: {},
        tabs: [],

        order: {},
        items: {},
        shipments: {},
        payments: {},
        returns: {},
        refunds: {},
        cancellations: {}
    };

	return {
		mixins: [SvMixinForm],
		data: function () {
			return {
				form: defForm
			}
		},
		methods: {
            updateBreadcrumbs: function (label) {
            	var label = !this.form.config.title ? (('Loading...')) : (this.form.order.id ? this.form.order.unique_id : 'New Order');
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: label,
                    breadcrumbs: [
                        {nav:'/sales', label:'Sales', icon_class:'fa fa-line-chart'},
                        {link:'/sales/orders', label:'Orders'}
                    ]
                }});
            },
			fetchData: function () {
                var orderId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'orders/form_data', {id: orderId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs(this._((('Order #{id}')), {id: vm.form.order.unique_id}));
                });
			},
			doFormAction: function (action) {
				var vm = this;
				switch (action.name) {
					case 'update-form':
						action.form.config.tabs = this.form.config.tabs;
						this.$set(this, 'form', action.form);
						break;

					case 'delete':
						if (!confirm(this._((('Are you sure you want to delete this {type}?')), {type: action.entity.entity_type}))) {
							return;
						}
						var postData = {
							order_id: this.form.order.id,
							entity_type: action.entity.entity_type,
							entity_id: action.entity.id
						};
						this.sendRequest('POST', 'orders/entity_delete', postData, function (response) {
                            response.form.config.tabs = vm.form.config.tabs;
                            vm.$set(vm, 'form', response.form);
						});
						break;
				}
			},
			doDelete: function () {
				if (!confirm(this._(('Are you sure you want to delete this order?')))) {
					return;
				}
				this.sendRequest('POST', 'orders/form_delete', {id: this.form.order.id}, function (response) {
					if (!response.ok) {

					}
				});
			},
			shipAllItems: function () {
				var vm = this, postData = {order_id: this.form.order.id};
				this.sendRequest('POST', 'orders/ship_all_items', postData, function (response) {
					if (response.form) {
                        response.form.config.tabs = vm.form.config.tabs;
                        vm.$set(vm, 'form', response.form);
					}
				});
			},
			markAsPaid: function () {
                var vm = this, postData = {order_id: this.form.order.id};
                this.sendRequest('POST', 'orders/mark_as_paid', postData, function (response) {
                    if (response.form) {
                        response.form.config.tabs = vm.form.config.tabs;
                        vm.$set(vm, 'form', response.form);
                    }
                });
			},
			save: function (stayOnPage) {
				var vm = this, postData = {
					form: {
						order: this.form.order
					}
				};
                if (!this.validateForm()) {
                    return;
                }
				this.sendRequest('POST', 'orders/form_data', postData, function (response) {
					if (!response.ok) {

					}
                    for (var i in response.form) {
                        vm.$set(vm.form, i, response.form[i]);
                    }
                    if (!vm.form.updates) {
						vm.$set(vm.form, 'updates', {});
					}
                    if (!stayOnPage) {
                        vm.$router.push('/sales/orders');
                    }
				})
		    }
		},
		watch: {

		}
    };
});