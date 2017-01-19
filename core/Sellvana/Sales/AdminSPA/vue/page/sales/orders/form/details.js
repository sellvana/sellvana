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
                orderItemsById[form.items[i].id] = form.items;
            }
        }
        return orderItemsById[entityItem.order_item_id];
    }

    var EntityAddMixin = {
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
        },
        methods: {
            toggleItem: function (item) {
                var id = item.id || item.name;
                Vue.set(this.items_selected, id, !this.items_selected[id]);
                if (this.items_selected[id]) {
                    Vue.set(item, 'amount_to_pay', item.amount_due || item.value);
                } else {
                    Vue.set(item, 'amount_to_pay', '');
                }
            }
        }
    };

    var EntityEditMixin = {

    };

    var SectionComponents = {
        payments: {
            props: ['form', 'entity'],
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
                submit: function () {
                    var i, l, item, postData = {
                        order_id: this.form.order.id,
                        payment: {
                            method: this.payment_method
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
                    SvHlp.sendRequest('POST', 'orders/payment_add', postData, function (response) {
console.log(response);
                        this.$emit('action', {type: 'update-form', form: response.form});
                    });
                }
            }
        },
        paymentEdit: {
            mixins: [EntityEditMixin],
            props: ['form', 'entity'],
            template: paymentsEditTpl,
            computed: {
                orderItem: function () {
                    var vm = this;
                    return function (entityItem) {
                        return getOrderItem(vm.form, entityItem);
                    }
                }
            }
        },
        
        shipments: {
            props: ['form', 'entity'],
            template: shipmentsTpl
        },
        shipmentAdd: {
            mixins: [EntityAddMixin],
            props: ['form', 'entity'],
            template: shipmentsAddTpl
        },
        shipmentEdit: {
            mixins: [EntityEditMixin],
            props: ['form', 'entity'],
            template: shipmentsEditTpl
        },
        
        refunds: {
            props: ['form', 'entity'],
            template: refundsTpl
        },
        refundAdd: {
            mixins: [EntityAddMixin],
            props: ['form', 'entity'],
            template: refundsAddTpl
        },
        refundEdit: {
            mixins: [EntityEditMixin],
            props: ['form', 'entity'],
            template: refundsEditTpl
        },
        
        returns: {
            props: ['form', 'entity'],
            template: returnsTpl
        },
        returnAdd: {
            mixins: [EntityAddMixin],
            props: ['form', 'entity'],
            template: returnsAddTpl
        },
        returnEdit: {
            mixins: [EntityEditMixin],
            props: ['form', 'entity'],
            template: returnsEditTpl
        },

        cancellations: {
            props: ['form', 'entity'],
            template: cancellationsTpl
        },
        cancellationAdd: {
            mixins: [EntityAddMixin],
            props: ['form', 'entity'],
            template: cancellationsAddTpl
        },
        cancellationEdit: {
            mixins: [EntityEditMixin],
            props: ['form', 'entity'],
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
                    case 'update-form': this.$emit('action', action); break;
                    case 'close': this.closeHlpSection(); break;
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
            }
        }
    };
});