define(['lodash', 'vue', 'sv-comp-grid-data-cell-default', 'sv-comp-grid-data-cell-row-select', 'sv-comp-grid-data-cell-actions'],
    function (_, Vue, SvCompGridDataCellDefault, SvCompGridDataCellRowSelect, SvCompGridDataCellActions) {

    // Vue.component('sv-comp-grid-data-cell-default', SvCompGridDataCellDefault);
    // Vue.component('sv-comp-grid-data-cell-row-select', SvCompGridDataCellRowSelect);
    // Vue.component('sv-comp-grid-data-cell-actions', SvCompGridDataCellActions);

    return {
        props: ['grid', 'row'],
        template: '<tr><component v-for="col in columns" :key="col.name" v-if="!col.hidden" :is="cellComponent(col)" '
            + ':name="col.name" :grid="grid" :row="row" :col="col" @event="onEvent"></component></tr>',
        computed: {
            columns: function () {
                return _.get(this.grid, 'config.columns', []);
            },
            cellComponent: function () {
                var vm = this;
                return function (col) {
                    if (!(this.grid && this.grid.components)) {
                        return 'empty';//SvCompGridDataCellDefault;
                    }
                    return this.grid.components.datacell_columns[col.name];
                }
            }
        },
        methods: {
            onEvent: function (event, arg) {
                this.emitEvent(event, arg);
            }
        },
        components: {
            empty: {template: '<td></td>'}
        },
        watch: {
            row: function (row, oldRow) {
                console.log('row-update', row, oldRow);
                this.emitEvent('row-update', {row: row, old: oldRow});
            }
        }
    };
});