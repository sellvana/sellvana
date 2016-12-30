define(['sv-app', 'sv-comp-grid', 'sv-comp-form',
		'text!sv-page-sales-orders-form-main-tpl',
		'text!sv-page-sales-orders-form-details-tpl',
		'text!sv-page-sales-orders-form-comments-tpl',
        'text!sv-page-sales-orders-form-history-tpl'],
	   function (SvApp, SvCompGrid, SvCompForm, tabMainTpl, tabDetailsTpl, tabCommentsTpl, tabHistoryTpl) {

	var TabMain = {
		template: tabMainTpl,
		props: ['order', 'items', 'shipments', 'payments', 'returns', 'refunds', 'cancellations', 'options'],
		data: function () {
			return {
				editing: {customer: false, shipping: false, billing: false, order: false},
				dict: SvApp.data
			}
		},
		computed: {
			regionOptions: function () {
				return function (type) {
					if (!this.order.id) {
						return [];
					}
                    return this.dict.regionsSeq['@' + this.order[type + '_country']];
                }
			},
			itemsGrid: function () {
				console.log(this.items);
				return {
					config: {
						columns: [
							{type:'row-select'},
							{type:'actions'},
							{field:'id', label:'ID'},
							{field:'product_name', label:'Product Name'},
							{field:'product_sku', label:'Product SKU'},
							{field:'price', label:'Price'},
							{field:'qty_ordered', label:'Qty'},
							{field:'row_total', label:'Total'},
                            {field:'state_overall', label:'Overall', options:this.options.item_state_overall},
                            {field:'state_delivery', label:'Delivery', options:this.options.item_state_delivery},
                            {field:'state_custom', label:'Custom', options:this.options.item_state_custom}
						]
					},
					rows: this.items
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
				console.log(this.order.shipping_country);
			}
		},
		components: {
			'sv-comp-grid': SvCompGrid
		}
	};

	var TabDetails = {
        props: ['order', 'shipments', 'payments', 'returns', 'refunds', 'cancellations', 'options'],
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
		props: ['order'],
		template: tabCommentsTpl,
		computed: {
			comments: function () {
				return [
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
				]
			}
		}
	};

	var TabHistory = {
		props: ['order'],
		computed: {
			grid: function () {
				return {
					config: {
						id: 'sales_order_histoy',
						data_url: 'orders/form_history_grid_data?id=' + this.order.id,
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
				order: {},
				items: {},
				shipments: {},
				payments: {},
				returns: {},
				refunds: {},
				cancellations: {},
				options: {},
				updates: {}
			}
		},
		computed: {
			getOption: function () {
                return function (type, value) {
                    if (!this.options[type]) {
                        return {};
                    }
                    if (!value) {
                        return this.options[type];
                    }
                    return this.options[type][value];
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
                	for (var i in response) {
                		vm[i] = response[i];
					}
                    vm.updateBreadcrumbs(SvApp._('Order #' + vm.order.unique_id));
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
                        vm[i] = response[i];
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