define(['sv-hlp'], function (SvHlp) {
    return {
        props: ['grid', 'row', 'col'],
        template: '<th class="column-actions">'
            + '<template v-for="act in col.actions">'
            + '<router-link v-if="act.link" :to="rowActionLink(row, act)" class="edit"><i :class="act.icon_class" aria-hidden="true"></i></router-link>'
            + '<a href="#" v-if="act.delete_url" @click.prevent="deleteRow(row, act)" class="delete"><i :class="act.icon_class" aria-hidden="true"></i></a>'
            + '</template>'
            + '</th>',

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
                if (!confirm(SvHlp._('Are you sure you want to delete the row?'))) {
                    return;
                }
                this.$emit('delete-row', row);
                var vm = this;
                if (act.delete_url) {
                    var url = vm.rowActionLink(row, {link: act.delete_url});
                    SvHlp.sendRequest('POST', url, {}, function (response) {
                        vm.$emit('fetch-data');
                    });
                }
            }
        }
    };
});