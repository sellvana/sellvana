define(['text!sv-comp-grid-data-cell-actions-tpl'], function (actionsTpl) {
    return {
        props: ['grid', 'row', 'col'],
        template: actionsTpl,
        computed: {
            rowActionLink: function () {
                return function (row, act) {
                    return act.link.replace(/\{([a-z0-9_]+)\}/, function (a, b) {
                        return row[b];
                    });
                }
            }
        },
        methods: {
            deleteRow: function (row, act) {
                if (!confirm(this._(('Are you sure you want to delete the row?')))) {
                    return;
                }
                this.$emit('delete-row', row);
                var vm = this;
                if (act.delete_url) {
                    var url = vm.rowActionLink(row, {link: act.delete_url});
                    this.sendRequest('POST', url, {}, function (response) {
                        vm.$emit('event', 'fetch-data');
                    });
                }
            }
        }
    };
});