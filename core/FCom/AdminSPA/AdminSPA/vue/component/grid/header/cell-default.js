define(['lodash'], function (_) {

    return {
        props: ['grid', 'col'],
        template: '<th>'
		    + '<a v-if="col.sortable" href="#" :class="anchorClass" @click.prevent="toggleSort()" class="f-main-grid__header-link">{{col.label|_}}'
            + '<i class="fa fa-caret-up f-sorted f-sorted-up" aria-hidden="true" v-if="sorted(\'up\', 1)"></i>'
            + '<i class="fa fa-caret-down f-sorted f-sorted-down" aria-hidden="true" v-if="sorted(\'down\', 1)"></i></a>'
            + '<span v-else>{{col.label|_}}</span>'
            + '</th>',
        computed: {
            sorted: function() {
                return function (dir, def) {
                    if (!this.col.sortable) {
                        return false;
                    }
                    if (!this.grid || !this.grid.config || !this.grid.config.state || this.grid.config.state.s !== this.col.field) {
                        return def;
                    }
                    var sd = this.grid.config.state.sd;
                    return (dir === 'up' && sd === 'asc') || (dir === 'down' && sd === 'desc');
                }
            },
            anchorClass: function () {
                return {'sorted-up':this.sorted('up', 0), 'sorted-down':this.sorted('down', 0)};
            }
        },
        methods: {
            toggleSort: function () {
                if (!this.col.sortable) {
                    return;
                }
                if (!this.grid.config.state) {
                    this.$set(this.grid.config, 'state', {});
                }
                var s = this.col.field, sd = 'asc';
                if (this.grid.config.state.s === s) {
                    if (this.grid.config.state.sd === 'asc') {
                        sd = 'desc';
                    } else {
                        s = false;
                        sd = false;
                    }
                }
                this.$set(this.grid.config.state, 's', s);
                this.$set(this.grid.config.state, 'sd', sd);
                if (this.grid.config.data_url) {
                    this.emitEvent('fetch-data');
                } else if (s) {
                    this.$set(this.grid, 'rows', _.orderBy(this.grid.rows, [s], [sd]));
                }
            }
        }
    };
});