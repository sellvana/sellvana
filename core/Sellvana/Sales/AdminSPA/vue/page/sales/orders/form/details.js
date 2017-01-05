define(['text!sv-page-sales-orders-form-details-tpl'], function (tabDetailsTpl) {

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
});