define(['vue', 'text!sv-page-sales-orders-form-comments-tpl', 'text!sv-page-sales-orders-form-comments-comment-tpl',
    'text!sv-page-sales-orders-form-comments-comment-add-tpl'
    ], function (Vue, tabCommentsTpl, commentTpl, commentAddTpl) {

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

    var Comment = {
        props: ['form', 'comment'],
        template: commentTpl
    };

    var CommentAdd = {
        props: ['form'],
        template: commentAddTpl
    };

    return {
        props: {
            form: {}
        },
        data: function () {
            return {
                show_add_comment: false,
                sort_by: 'date',
                sort_by_dir: 'desc',
                sort_by_options: [{name: 'date', label: 'Date'}, {name: 'status', label: 'Status'}, {name: 'name', label: 'Name'}]
            }
        },
        template: tabCommentsTpl,
        components: {
            comment: Comment,
            commentAdd: CommentAdd
        },
        methods: {
            toggleSort: function (sortBy) {
                if (sortBy === this.sort_by) {
                    this.sort_by_dir = this.sort_by_dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sort_by = sortBy;
                    this.sort_by_dir = 'asc';
                }
            },
            showAddComment: function () {
                this.show_add_comment = true;
            }
        },
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