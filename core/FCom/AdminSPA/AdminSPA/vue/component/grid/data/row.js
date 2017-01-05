define(['vue', 'sv-comp-grid-data-cell-default', 'sv-comp-grid-data-cell-row-select', 'sv-comp-grid-data-cell-actions'],
    function (Vue, SvCompGridDataCellDefault, SvCompGridDataCellRowSelect, SvCompGridDataCellActions) {

    // Vue.component('sv-comp-grid-data-cell-default', SvCompGridDataCellDefault);
    // Vue.component('sv-comp-grid-data-cell-row-select', SvCompGridDataCellRowSelect);
    // Vue.component('sv-comp-grid-data-cell-actions', SvCompGridDataCellActions);

    return {
        //mixins: [SvHlp.mixins.common],
        props: ['grid', 'row'],
        template: '<tr><component v-for="col in columns" v-if="!col.hidden" :is="cellComponent(col)" :grid="grid" :row="row" :col="col" @fetch-data="$emit(\'fetch-data\')"></component></tr>',
        computed: {
            columns: function () {
                return this.grid && this.grid.config.columns ? this.grid.config.columns : [];
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
        components: {
            empty: {template:'<td></td>'}
        }
    };
});