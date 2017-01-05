define(['vue', 'sv-hlp', 'sv-comp-grid', 'sv-comp-form', 'text!sv-page-catalog-products-form-main-tpl'],
	   function (Vue, SvHlp, SvCompGrid, SvCompForm, tabMainTpl) {

	var defForm = {
        options: {},
        updates: {},
        tabs: [],

        product: {}
    };

	var TabMain = {
		template: tabMainTpl,
		props: {
            form: {
            	default: defForm
            }
        },
		data: function () {
			return {
				dict: SvAppData
			}
		}
	};

	return {
		mixins: [SvHlp.mixins.common, SvHlp.mixins.form],
        components: {
            'sv-page-sales-orders-form-main': TabMain
		},
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
                        {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'},
                        {link:'/sales/products', label:'Products'}
                    ]
                }});
            },
			fetchData: function () {
                var orderId = this.$router.currentRoute.query.id, vm = this;
                SvHlp.sendRequest('GET', 'products/form_data', {id: orderId}, function (response) {
					vm.form = response.form;
                    if (!vm.form.updates) {
                        Vue.set(vm.form, 'updates', {});
                    }
                    vm.updateBreadcrumbs(vm.form.product.product_name);
                });
			},
			doDelete: function () {
				if (!confirm(SvHlp._('Are you sure you want to delete this product?'))) {
					return;
				}
				SvHlp.sendRequest('POST', 'products/form_delete', {id: this.form.product.id}, function (response) {
					if (!response.ok) {

					}
				});
			},
			save: function (stayOnPage) {
				var vm = this;
				SvHlp.sendRequest('POST', 'products/form_data', this.form.updates, function (response) {
					if (!response._ok) {

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
			'form.product': function (product) {

			}
		}
    };
});