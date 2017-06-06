define([], function () {
    return {
        props: ['grid', 'row', 'col'],
        template: '<td>{{outputValue}}</td>',
        computed: {
            outputValue: function () {
                var v = this.row[this.col.field], f;

                if (this.grid.config.columns_by_name) {
                    f = this.grid.config.columns_by_name[this.col.field];
                    if (f && f.options && f.options[v]) {
                        return f.options[v];
                    }
                }

                return v;
            }
        }
    };
});