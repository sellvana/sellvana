define(['vue', 'sv-hlp'],
	   function (Vue, SvHlp) {

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
		mixins: [SvHlp.mixins.common, SvHlp.mixins.form],
		data: function () {
			return {
				form: defForm
			}
		},
		methods: {
			buttonAction: function (act) {
				console.log(act);
			},
            updateBreadcrumbs: function (label) {
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
                SvHlp.sendRequest('GET', 'orders/form_data', {id: orderId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs(SvHlp._('Order #' + vm.form.order.unique_id));
                });
			},
			doDelete: function () {
				if (!confirm(SvHlp._('Are you sure you want to delete this order?'))) {
					return;
				}
				SvHlp.sendRequest('POST', 'orders/form_delete', {id: this.form.order.id}, function (response) {
					if (!response.ok) {

					}
				});
			},
			shipAllItems: function () {

			},
			markAsPaid: function () {

			},
			save: function (stayOnPage) {
				var vm = this;
				SvHlp.sendRequest('POST', 'orders/form_data', this.form.updates, function (response) {
					if (!response.ok) {

					}
                    for (var i in response.form) {
                        Vue.set(vm.form, i, response.form[i]);
                    }
                    if (!vm.form.updates) {
						Vue.set(vm.form, 'updates', {});
					}
                    if (!stayOnPage) {
                        vm.$router.go(-1);
                    }
				})
		    }
		},
		watch: {
			'form.order': function (order) {

			}
		}
    };
});