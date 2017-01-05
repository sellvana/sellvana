define(['vue', 'text!sv-page-sales-orders-form-comments-tpl'], function (Vue, tabCommentsTpl) {

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
});