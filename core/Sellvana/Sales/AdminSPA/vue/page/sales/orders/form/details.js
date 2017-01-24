define(['lodash', 'vue', 'sv-hlp', 'text!sv-page-sales-orders-form-details-tpl',
        // 'sv-page-sales-orders-form-details-payment-add',
        // 'sv-page-sales-orders-form-details-payment-edit',
        // 'sv-page-sales-orders-form-details-shipment-add',
        // 'sv-page-sales-orders-form-details-shipment-edit',
        // 'sv-page-sales-orders-form-details-refund-add',
        // 'sv-page-sales-orders-form-details-refund-edit',
        // 'sv-page-sales-orders-form-details-return-add',
        // 'sv-page-sales-orders-form-details-return-edit',
        // 'sv-page-sales-orders-form-details-cancellation-add',
        // 'sv-page-sales-orders-form-details-cancellation-edit',

        'text!sv-page-sales-orders-form-details-payments-tpl',
        'text!sv-page-sales-orders-form-details-payments-add-tpl',
        'text!sv-page-sales-orders-form-details-payments-edit-tpl',
        'text!sv-page-sales-orders-form-details-shipments-tpl',
        'text!sv-page-sales-orders-form-details-shipments-add-tpl',
        'text!sv-page-sales-orders-form-details-shipments-edit-tpl',
        'text!sv-page-sales-orders-form-details-refunds-tpl',
        'text!sv-page-sales-orders-form-details-refunds-add-tpl',
        'text!sv-page-sales-orders-form-details-refunds-edit-tpl',
        'text!sv-page-sales-orders-form-details-returns-tpl',
        'text!sv-page-sales-orders-form-details-returns-add-tpl',
        'text!sv-page-sales-orders-form-details-returns-edit-tpl',
        'text!sv-page-sales-orders-form-details-cancellations-tpl',
        'text!sv-page-sales-orders-form-details-cancellations-add-tpl',
        'text!sv-page-sales-orders-form-details-cancellations-edit-tpl'
    ], function (_, Vue, SvHlp, tabDetailsTpl,
         // PaymentAdd, PaymentEdit, ShipmentAdd, ShipmentEdit, RefundAdd, RefundEdit, ReturnAdd, ReturnEdit, CancellationAdd, CancellationEdit,
        paymentsTpl, paymentsAddTpl, paymentsEditTpl,
        shipmentsTpl, shipmentsAddTpl, shipmentsEditTpl,
        refundsTpl, refundsAddTpl, refundsEditTpl,
        returnsTpl, returnsAddTpl, returnsEditTpl,
        cancellationsTpl, cancellationsAddTpl, cancellationsEditTpl
) {

    var orderItemsById = {};

    function getOrderItem(form, entityItem) {
        if (_.isEmpty(orderItemsById) || true === entityItem) {
            for (var i = 0, l = form.items.length; i < l; i++) {
                orderItemsById[form.items[i].id] = form.items[i];
            }
        }
        return orderItemsById[entityItem.order_item_id];
    }

    var EntityListMixin = {
        mixins: [SvHlp.mixins.common],
        props: ['form', 'entity']
    };

    var EntityAddMixin = {
        mixins: [SvHlp.mixins.common],
        props: ['form', 'entity'],
        data: function () {
            return {
                items_selected: {}
            }
        },
        computed: {
            isItemSelected: function () {
                return function (item) {
                    var id = item.id || item.name;
                    return this.items_selected[id];
                }
            }
        }
    };

    var EntityEditMixin = {
        mixins: [SvHlp.mixins.common],
        props: ['form', 'entity'],
        computed: {
            orderItem: function () {
                var vm = this;
                return function (entityItem) {
                    return getOrderItem(vm.form, entityItem);
                }
            }
        }
    };

    var SectionComponents = {
        payments: {
            mixins: [EntityListMixin],
            template: paymentsTpl
        },
        paymentAdd: {
            mixins: [EntityAddMixin],
            template: paymentsAddTpl,
            data: function () {
                return {
                    payment_method: ''
                }
            },
            computed: {
                totalAmountToPay: function () {
                    var total = 0, i;
                    for (i = 0, l = this.form.items_payable.length; i < l; i++) {
                        total += 1 * this.form.items_payable[i].amount_to_pay;
                    }
                    for (i = 0, l = this.form.totals.length; i < l; i++) {
                        if (_.isNumber(this.form.totals[i].amount_to_pay)) {
                            total += 1 * this.form.totals[i].amount_to_pay;
                        }
                    }
                    return total;
                }
            },
            methods: {
                toggleItem: function (item) {
                    var id = item.id || item.name;
                    Vue.set(this.items_selected, id, !this.items_selected[id]);
                    Vue.set(item, 'amount_to_pay', this.items_selected[id] ? (item.amount_due || item.value) : '');
                },
                submit: function () {
                    var vm = this, i, l, item, postData = {
                        order_id: this.form.order.id,
                        payment: {
                            payment_method: this.payment_method
                        },
                        amounts: {},
                        totals: {}
                    };
                    for (i = 0, l = this.form.items_payable.length; i < l; i++) {
                        item = this.form.items_payable[i];
                        postData.amounts[item.id] = item.amount_to_pay;
                    }
                    for (i = 0, l = this.form.totals.length; i < l; i++) {
                        var total = this.form.totals[i];
                        postData.totals[total.name] = total.amount_to_pay;
                    }
                    this.sendRequest('POST', 'orders/payment_add', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                        if (response.ok) {
                            vm.$emit('action', {type: 'switch-entity', entity_type: 'payment', entity_id: response.new_entity_id});
                        }
                    });
                }
            }
        },
        paymentEdit: {
            mixins: [EntityEditMixin],
            data: function () {
                return {
                    show_failed_transactions: false
                };
            },
            template: paymentsEditTpl,
            computed: {
                paymentMethod: function () {
                    return this.form.payment_methods[this.entity.payment_method] || {};
                },
                totalAmount: function () {
                    return 1 * this.entity.amount_captured + 1 * this.entity.amount_due - this.entity.amount_refunded;
                },
                isRootTransactionNeeded: function () {
                    var meta = this.paymentMethod.meta;
                    return meta && meta.is_root_transaction_needed && meta.capabilities.pay_by_url;
                },
                transactionStatus: function () {
                    var vm = this;
                    return function (t) {
                        return t.transaction_status == 'completed' ? 'Success' : (t.transaction_status == 'void') ? 'Void' : 'Failure';
                    }
                }
            },
            methods: {
                sendRootTransactionUrl: function () {
                    var vm = this, postData = {
                        order_id: this.form.order.id,
                        payment_id: this.entity.id
                    };
                    this.sendRequest('POST', 'orders/send_root_transaction_url', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                    });
                },
                changePaymentState: function (type, value) {
                    var vm = this, postData = {
                        order_id: this.form.order.id,
                        payment_id: this.entity.id,
                        type: type,
                        value: value
                    };
                    this.sendRequest('POST', 'orders/payment_state', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                        if (response.ok) {
                            vm.$emit('action', {type: 'switch-entity', entity_type: 'payment', entity_id: vm.entity.id});
                        }
                    });
                },
                doTransactionAction: function (transaction, action) {
                    this.action_in_progress = action + '-' + transaction.id;
                    console.log(transaction, action);
                    var vm = this, postData = {
                        order_id: this.form.order.id,
                        payment_id: this.entity.id,
                        transaction_id: transaction.id,
                        action_type: action,
                        amount: transaction.available_actions[action].amount
                    };
                    this.sendRequest('POST', 'orders/transaction_action', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                        if (response.ok) {
                            vm.$emit('action', {type: 'switch-entity', entity_type: 'payment', entity_id: vm.entity.id});
                        }
                        vm.action_in_progress = '';
                    });
                }
            }
        },
        
        shipments: {
            mixins: [EntityListMixin],
            template: shipmentsTpl
        },
        shipmentAdd: {
            mixins: [EntityAddMixin],
            template: shipmentsAddTpl,
            data: function () {
                return {
                    shipment: {
                        carrier_code: '',
                        service_code: '',
                        shipping_weight: '',
                        shipping_size: '',
                        carrier_price: 0
                    }
                }
            },
            computed: {
                totalQtyToShip: function () {
                    var total = 0, i, l, item;
                    for (i = 0, l = this.form.items_shippable.length; i < l; i++) {
                        item = this.form.items_shippable[i];
                        if (item.qty_to_ship) {
                            total += 1 * item.qty_to_ship;
                        }
                    }
                    return total;
                },
                shippingServices: function () {
                    if (!this.shipment.carrier_code) {
                        return [];
                    }
                    var i, j, l, m, services = [];
                    for (i = 0, l = this.form.shipping_methods.length; i < l; i++) {
                        m = this.form.shipping_methods[i];
                        if (m.id === this.shipment.carrier_code) {
                            for (j in m.services) {
                                services.push({id: j, text: m.services[j]});
                            }
                            break;
                        }
                    }
                    return services;
                }
            },
            methods: {
                toggleItem: function (item) {
                    var id = item.id;
                    Vue.set(this.items_selected, id, !this.items_selected[id]);
                    Vue.set(item, 'qty_to_ship', this.items_selected[id] ? item.qty_can_ship : '');
                },
                submit: function () {
                    var vm = this, i, l, item, postData = {
                        order_id: this.form.order.id,
                        shipment: this.shipment,
                        qtys: {}
                    };
                    for (i = 0, l = this.form.items_shippable.length; i < l; i++) {
                        item = this.form.items_shippable[i];
                        postData.qtys[item.id] = item.qty_to_ship;
                    }
                    this.sendRequest('POST', 'orders/shipment_add', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                        if (response.ok) {
                            vm.$emit('action', {type: 'switch-entity', entity_type: 'shipment', entity_id: response.new_entity_id});
                        }
                    });
                }
            }
        },
        shipmentEdit: {
            mixins: [EntityEditMixin],
            template: shipmentsEditTpl,
            methods: {
                updateTracking: function () {
                    var vm = this, i, l, pkg, postData = {
                        order_id: this.form.order.id,
                        packages: {}
                    };
                    for (i = 0, l = this.entity.packages.length; i < l; i++) {
                        pkg = this.entity.packages[i];
                        postData.packages[pkg.id] = {tracking_number: pkg.tracking_number};
                    }
                    this.sendRequest('POST', 'orders/shipment_edit', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                    });
                }
            }
        },
        
        refunds: {
            mixins: [EntityListMixin],
            template: refundsTpl
        },
        refundAdd: {
            mixins: [EntityAddMixin],
            template: refundsAddTpl
        },
        refundEdit: {
            mixins: [EntityEditMixin],
            template: refundsEditTpl,
            computed: {
                totalItemLabel: function () {
                    var vm = this;
                    return function (rItem) {
                        var pItem = vm.orderItem(rItem);
                        return pItem ? pItem.data_serialized.custom_label : 'Refunded Item';
                    }
                },
                totalItemAmountPaid: function () {
                    var vm = this;
                    return function (rItem) {
                        var pItem = vm.orderItem(rItem);
                        return pItem ? pItem.amount : 0;
                    }
                }
            }
        },
        
        returns: {
            mixins: [EntityListMixin],
            template: returnsTpl
        },
        returnAdd: {
            mixins: [EntityAddMixin],
            template: returnsAddTpl,
            data: function () {
                return {
                    rma: {
                        carrier_code: '',
                        service_code: '',
                        shipping_weight: '',
                        shipping_size: '',
                        carrier_price: 0
                    }
                }
            },
            computed: {
                totalQtyToReturn: function () {
                    var total = 0, i, l, item;
                    for (i = 0, l = this.form.items_returnable.length; i < l; i++) {
                        item = this.form.items_returnable[i];
                        if (item.qty_to_return) {
                            total += 1 * item.qty_to_return;
                        }
                    }
                    return total;
                },
                shippingServices: function () {
                    if (!this.rma.carrier_code) {
                        return [];
                    }
                    var i, j, l, m, services = [];
                    for (i = 0, l = this.form.shipping_methods.length; i < l; i++) {
                        m = this.form.shipping_methods[i];
                        if (m.id === this.rma.carrier_code) {
                            for (j in m.services) {
                                services.push({id: j, text: m.services[j]});
                            }
                            break;
                        }
                    }
                    return services;
                }
            },
            methods: {
                toggleItem: function (item) {
                    var id = item.id;
                    Vue.set(this.items_selected, id, !this.items_selected[id]);
                    Vue.set(item, 'qty_to_return', this.items_selected[id] ? item.qty_can_return : '');
                },
                submit: function () {
                    var vm = this, i, l, item, postData = {
                        order_id: this.form.order.id,
                        'return': this.rma,
                        qtys: {}
                    };
                    for (i = 0, l = this.form.items_returnable.length; i < l; i++) {
                        item = this.form.items_returnable[i];
                        postData.qtys[item.id] = item.qty_to_return;
                    }
                    this.sendRequest('POST', 'orders/return_add', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                        if (response.ok) {
                            vm.$emit('action', {type: 'switch-entity', entity_type: 'return', entity_id: response.new_entity_id});
                        }
                    });
                }
            }
        },
        returnEdit: {
            mixins: [EntityEditMixin],
            template: returnsEditTpl
        },

        cancellations: {
            mixins: [EntityListMixin],
            template: cancellationsTpl
        },
        cancellationAdd: {
            mixins: [EntityAddMixin],
            template: cancellationsAddTpl,
            data: function () {
                return {
                    cancel: {}
                }
            },
            computed: {
                totalQtyToCancel: function () {
                    var total = 0, i, l, item;
                    for (i = 0, l = this.form.items_cancellable.length; i < l; i++) {
                        item = this.form.items_cancellable[i];
                        if (item.qty_to_cancel) {
                            total += 1 * item.qty_to_cancel;
                        }
                    }
                    return total;
                }
            },
            methods: {
                toggleItem: function (item) {
                    var id = item.id;
                    Vue.set(this.items_selected, id, !this.items_selected[id]);
                    Vue.set(item, 'qty_to_cancel', this.items_selected[id] ? item.qty_can_cancel : '');
                },
                submit: function () {
                    var vm = this, i, l, item, postData = {
                        order_id: this.form.order.id,
                        cancel: this.cancel,
                        qtys: {}
                    };
                    for (i = 0, l = this.form.items_cancellable.length; i < l; i++) {
                        item = this.form.items_cancellable[i];
                        postData.qtys[item.id] = item.qty_to_cancel;
                    }
                    this.sendRequest('POST', 'orders/cancellation_add', postData, function (response) {
                        if (response.form) {
                            vm.$emit('action', {type: 'update-form', form: response.form});
                        }
                        if (response.ok) {
                            vm.$emit('action', {type: 'switch-entity', entity_type: 'cancellation', entity_id: response.new_entity_id});
                        }
                    });
                }
            }
        },
        cancellationEdit: {
            mixins: [EntityEditMixin],
            template: cancellationsEditTpl
        }
    };

    function populateOrderItems(form) {
        var type, i, j, l, m, itemsById = {}, item;
        for (i = 0, l = form.items.length; i < l; i++) {
            itemsById[form.items[i].id] = form.items[i];
        }
        for (type in ['payments', 'shipments', 'returns', 'refunds', 'cancellations']) {
            if (!form[type] || !form[type].length) {
                continue;
            }
            for (i = 0, l = form[type].length; i < l; i++) {
                if (!form[type][i].items || !form[type][i].items.length) {
                    continue;
                }
                for (j = 0, m = form[type][i].items.length; j < m; j++) {
                    item = form[type][i].item[j];
                    Vue.set(item, 'order_item', itemsById[item.order_item_id]);
                }
            }
        }
//console.log(form, form.payments[0].items[0].order_item);
    }

    return {
        mixins: [SvHlp.mixins.common],
        props: {
            form: {
                type: Object
            }
        },
        template: tabDetailsTpl,
        data: function () {
            return {
                curHlpComponent: null,
                curHlpEntity: null
            };
        },
        computed: {
            detailsSections: function () {
                var sections = [], i, section;
                for (i = 0, l = this.form.details_sections.length; i < l; i++) {
                    section = this.form.details_sections[i];
                    if (!section.component) {
                        section.component = SectionComponents[section.name];
                    }
                    sections.push(section);
                }
                return sections;
            }
        },
        methods: {
            addEntity: function (type) {
                this.curHlpComponent = SectionComponents[type + 'Add'];
                this.curHlpEntity = _.get(this.form, 'new_entities.' + type, {});
            },
            editEntity: function (type, entity) {
                this.curHlpComponent = SectionComponents[type + 'Edit'];
                this.curHlpEntity = entity;
            },
            closeHlpSection: function () {
                this.curHlpComponent = null;
                this.curHlpEntity = null;
            },
            doAction: function (action) {
                if (typeof action === 'string') {
                    action = {type: action};
                }
                switch (action.type) {
                    case 'close':
                        this.closeHlpSection();
                        break;

                    case 'switch-entity':
                        var vm = this;
                        this.$nextTick(function () {
                            var i, l, entities = vm.form[action.entity_type + 's'];
                            for (i = 0, l = entities.length; i < l; i++) {
                                console.log(entities[i], action);
                                if (entities[i].id == action.entity_id) {
                                    vm.editEntity(action.entity_type, entities[i]);
                                    break;
                                }
                            }
                        });
                        break;

                    case 'delete':
                        if (!confirm(SvHlp._('Are you sure you want to delete this ' + action.entity.entity_type + '?'))) {
                            return;
                        }
                        var vm = this, postData = {
                            order_id: this.form.order.id,
                            entity_type: action.entity.entity_type,
                            entity_id: action.entity.id
                        };
                        this.sendRequest('POST', 'orders/entity_delete', postData, function (response) {
                            if (response.ok) {
                                vm.$emit('action', {type: 'update-form', form: response.form});
                            }
                            vm.closeHlpSection();
                        });
                        break;

                    default:
                        this.$emit('action', action);
                }
            }
        },
        created: function () {
            populateOrderItems(this.form);
        },
        watch: {
            'form.order.id': function (orderId) {
                populateOrderItems(this.form);
            },
            'form.items': {
                handler: function () { getOrderItem(this.form, true); },
                deep: true
            },
            'entity': function (entity) {
                if (!entity) {
                    this.closeHlpSection();
                }
            }
        }
    };
});