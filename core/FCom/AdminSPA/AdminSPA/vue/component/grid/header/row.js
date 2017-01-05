define(['vue', 'sv-hlp', 'sv-comp-grid-header-cell-default', 'sv-comp-grid-header-cell-row-select'],
    function (Vue, SvHlp, SvCompGridHeaderCellDefault, SvCompGridHeaderCellRowSelect) {

    // Vue.component('sv-comp-grid-header-cell-default', SvCompGridHeaderCellDefault);
    // Vue.component('sv-comp-grid-header-cell-row-select', SvCompGridHeaderCellRowSelect);

    return {
        mixins: [SvHlp.mixins.common],
        props: ['grid'],
        template: '<tr><component v-for="col in columns" v-if="!col.hidden" :is="cellComponent(col)" :grid="grid" :col="col" @fetch-data="$emit(\'fetch-data\')"></component></tr>',
        computed: {
            columns: function () {
                return this.grid && this.grid.config.columns ? this.grid.config.columns : [];
            },
            cellComponent: function () {
                var vm = this;
                return function (col) {
                    if (!(this.grid && this.grid.components)) {
                        return 'empty';//SvCompGridHeaderCellDefault;
                    }
                    return this.grid.components.header_columns[col.name];
                }
            }
        },
        components: {
            empty: {template:'<th></th>'}
        }
    }
});