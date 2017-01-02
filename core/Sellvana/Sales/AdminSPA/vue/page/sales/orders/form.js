define(['vue', 'sv-app', 'sv-comp-grid', 'sv-comp-form',
		'text!sv-page-sales-orders-form-main-tpl', 'json!sv-page-sales-orders-form-items-config',
		'text!sv-page-sales-orders-form-details-tpl',
		'text!sv-page-sales-orders-form-comments-tpl',
        'text!sv-page-sales-orders-form-history-tpl'],
	   function (Vue, SvApp, SvCompGrid, SvCompForm, tabMainTpl, itemsGridConfig, tabDetailsTpl, tabCommentsTpl, tabHistoryTpl) {

	var defForm = {
        order: {},
        items: {},
        shipments: {},
        payments: {},
        returns: {},
        refunds: {},
        cancellations: {},
        options: {},
        updates: {}
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
				editing: {customer: false, shipping: false, billing: false, order: false},
				dict: SvApp.data
			}
		},
		computed: {
			regionOptions: function () {
				return function (type) {
					if (!this.form.order.id) {
						return [];
					}
                    return this.dict.regionsSeq['@' + this.form.order[type + '_country']];
                }
			},
			itemsGrid: function () {
				return {
					config: itemsGridConfig,
					rows: this.form.items
				}
			},
			paidByStoreCredit: function () {
				return 0;
			},
            length: function () {
                return function (value) {
                    if (typeof value === 'object') {
                        return Object.keys(value).length;
                    } else if (value.isArray()) {
                        return value.length;
                    } else {
                        return 1;
                    }
                }
            }
		},
		methods: {
			toggleEditing: function(type) {
				this.editing[type] = !this.editing[type];
			}
		},
		watch: {
			order: function () {
				console.log(this.form.order.shipping_country);
			}
		},
		components: {
			'sv-comp-grid': SvCompGrid
		}
	};

	var TabDetails = {
        props: {
            form: {
                default: defForm
            }
        },
		template: tabDetailsTpl,
		data: function () {
        	return {
        		curPayment: {},
				curShipment: {},
				curReturn: {},
				curRefund: {},
				curCancellation: {},

				curHlpSection: false
			};
		},
		methods: {
        	addPayment: function () {
        		this.curHlpSection = 'add-payment';
				this.curPayment = {};
			},
        	addShipment: function () {
                this.curHlpSection = 'add-shipment';
				this.curShipment = {};
			},
			addReturn: function () {
                this.curHlpSection = 'add-return';
				this.curReturn = {};
			},
			addRefund: function () {
                this.curHlpSection = 'add-refund';
				this.curRefund = {};
			},
			addCancellation: function () {
                this.curHlpSection = 'add-cancellation';
				this.curCancellation = {};
			},
			viewPayment: function (p) {
                this.curHlpSection = 'view-payment';
				this.curPayment = p;
			},
			viewShipment: function (s) {
                this.curHlpSection = 'view-shipment';
                this.curShipment = s;
			},
			viewReturn: function (r) {
                this.curHlpSection = 'view-return';
				this.curReturn = r;
			},
			viewRefund: function (r) {
                this.curHlpSection = 'view-refund';
				this.curRefund = r;
			},
			viewCancellation: function (c) {
                this.curHlpSection = 'view-cancellation';
				this.curCancellation = c;
			},
			closeHlpSection: function () {
        		this.curHlpSection = false;
			}
		}
	};

	var TabComments = {
        props: {
            form: {
                default: defForm
            }
        },
		template: tabCommentsTpl,
		created: function () {
            Vue.set(this.form, 'comments', [
				{
					author_name: 'Boris Gurvich',
					create_at: '2016-12-28 18:42:00',
					type: 'sent',
					files: [
						{name: 'test.jpg'}
					]
				},
				{
					author_name: 'Boris Gurvich',
					create_at: '2016-12-28 18:42:00',
					type: 'received'
				},
				{
					author_name: 'Boris Gurvich',
					create_at: '2016-12-28 18:42:00',
					type: 'private'
				}
			]);
		}
	};

	var TabHistory = {
        props: {
            form: {
                default: defForm
            }
        },
		computed: {
			grid: function () {
				return {
					config: {
						id: 'sales_order_histoy',
						data_url: 'orders/form_history_grid_data?id=' + this.form.order.id,
						columns: [
							{field:'id', label:'ID'},
							{field:'create_at', label:'Timestamp'}
						]
					}
				}
			}
        },
		template: tabHistoryTpl,
		components: {
			'sv-comp-grid': SvCompGrid
		}
	};

	return {
		store: SvApp.store,
        components: {
            'sv-comp-form': SvCompForm,
            'sv-page-sales-orders-form-main': TabMain,
			'sv-page-sales-orders-form-comments': TabComments,
            'sv-page-sales-orders-form-details': TabDetails,
            'sv-page-sales-orders-form-history': TabHistory
		},
		data: function () {
			return {
				tab: 'main',
				form: defForm
			}
		},
		computed: {
			getOption: function () {
                return function (type, value) {
                    if (!this.form.options[type]) {
                        return {};
                    }
                    if (!value) {
                        return this.form.options[type];
                    }
                    return this.form.options[type][value];
                }
			}
		},
		methods: {
			switchTab: function (tab) {
				this.tab = tab;
			},
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
                SvApp.methods.sendRequest('GET', 'orders/form_data', {id: orderId}, function (response) {
					vm.form = response.form;
                    vm.updateBreadcrumbs(SvApp._('Order #' + vm.form.order.unique_id));
                });
			},
			goBack: function () {
				this.$router.go(-1);
			},
			doDelete: function () {
				if (!confirm(SvApp._('Are you sure you want to delete this order?'))) {
					return;
				}
			},
			shipAllItems: function () {

			},
			markAsPaid: function () {

			},
			save: function (stayOnPage) {
				var vm = this;
				SvApp.methods.sendRequest('POST', 'orders/form_data', this.updates, function (response) {
                    for (var i in response) {
                        Vue.set(vm.form, i, response.form[i]);
                    }
                    if (!stayOnPage) {
                        this.$router.go(-1);
                    }
				})
		    }
		},
		watch: {
			'$route': 'fetchData'
		},
		created: function () {
			this.updateBreadcrumbs(SvApp._('Loading order data...'));
			this.fetchData();
		}
    };
});