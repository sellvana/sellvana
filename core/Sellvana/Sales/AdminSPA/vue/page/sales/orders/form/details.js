define(['lodash', 'vue', 'text!sv-page-sales-orders-form-details-tpl',
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
    ], function (_, Vue, tabDetailsTpl,
         // PaymentAdd, PaymentEdit, ShipmentAdd, ShipmentEdit, RefundAdd, RefundEdit, ReturnAdd, ReturnEdit, CancellationAdd, CancellationEdit,
        paymentsTpl, paymentsAddTpl, paymentsEditTpl,
        shipmentsTpl, shipmentsAddTpl, shipmentsEditTpl,
        refundsTpl, refundsAddTpl, refundsEditTpl,
        returnsTpl, returnsAddTpl, returnsEditTpl,
        cancellationsTpl, cancellationsAddTpl, cancellationsEditTpl
) {

    var SectionComponents = {
        payments: {
            props: ['form'],
            template: paymentsTpl
        },
        paymentAdd: {
            props: ['form', 'entity'],
            template: paymentsAddTpl,
            data: function () {
                return {
                    items_selected: {}
                }
            },
            computed: {
                isItemSelected: function () {
                    return function (item) {
                        return this.items_selected[item.id];
                    }
                },
                totalAmountToPay: function () {
                    var total = 0, i;
                    for (i = 0, l = this.form.items_payable.length; i < l; i++) {
                        total += this.form.items_payable[i].amount_to_pay;
                    }
                    return total;
                }
            },
            methods: {
                toggleItem: function (item) {
                    Vue.set(this.items_selected, item.id, !this.items_selected[item.id]);
                    if (this.items_selected[item.id]) {
                        Vue.set(item, 'amount_to_pay', item.amount_due);
                    } else {
                        Vue.set(item, 'amount_to_pay', '');
                    }
                }
            }
        },
        paymentEdit: {
            props: ['form', 'entity'],
            template: paymentsEditTpl
        },
        
        shipments: {
            props: ['form'],
            template: shipmentsTpl
        },
        shipmentAdd: {
            props: ['form', 'entity'],
            template: shipmentsAddTpl
        },
        shipmentEdit: {
            props: ['form', 'entity'],
            template: shipmentsEditTpl
        },
        
        refunds: {
            props: ['form'],
            template: refundsTpl
        },
        refundAdd: {
            props: ['form', 'entity'],
            template: refundsAddTpl
        },
        refundEdit: {
            props: ['form', 'entity'],
            template: refundsEditTpl
        },
        
        returns: {
            props: ['form'],
            template: returnsTpl
        },
        returnAdd: {
            props: ['form', 'entity'],
            template: returnsAddTpl
        },
        returnEdit: {
            props: ['form', 'entity'],
            template: returnsEditTpl
        },

        Cancellations: {
            props: ['form'],
            template: cancellationsTpl
        },
        cancellationAdd: {
            props: ['form', 'entity'],
            template: cancellationsAddTpl
        },
        cancellationEdit: {
            props: ['form', 'entity'],
            template: cancellationsEditTpl
        }
    };

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
                    case 'close': this.closeHlpSection();
                }
            }
        }
    };
});