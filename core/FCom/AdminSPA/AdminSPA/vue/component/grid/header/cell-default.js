define(['vue', 'sv-hlp'], function (Vue, SvHlp) {

    return {
        mixins: [SvHlp.mixins.common],
        props: ['grid', 'col'],
        template: '<th>'
            + '<a v-if="col.sortable" href="#" :class="anchorClass" @click.prevent="toggleSort()">'
            + '<i class="fa fa-caret-up" aria-hidden="true" v-if="sorted(\'up\', 1)"></i>'
            + '<i class="fa fa-caret-down" aria-hidden="true" v-if="sorted(\'down\', 1)"></i>{{col.label|_}}</a>'
            + '<span v-else>{{col.label|_}}</span>'
            + '</th>',
        computed: {
            sorted: function() {
                return function (dir, def) {
                    if (!this.col.sortable) {
                        return false;
                    }
                    if (!this.grid || !this.grid.state || this.grid.state.s !== this.col.field) {
                        return def;
                    }
                    var sd = this.grid.state.sd;
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
                if (!this.grid.state) {
                    Vue.set(this.grid, 'state', {});
                }
                var s = this.col.field, sd = 'asc';
                if (this.grid.state.s === s) {
                    if (this.grid.state.sd === 'asc') {
                        sd = 'desc';
                    } else {
                        s = false;
                        sd = false;
                    }
                }
                Vue.set(this.grid.state, 's', s);
                Vue.set(this.grid.state, 'sd', sd);
                this.$emit('fetch-data');
            }
        }
    };
});