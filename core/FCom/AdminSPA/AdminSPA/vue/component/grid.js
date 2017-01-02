define(['vue', 'sv-app', 'jquery', 'lodash',
        'text!sv-comp-grid-tpl', 'text!sv-comp-grid-header-row-tpl', 'text!sv-comp-grid-data-row-tpl',
        'text!sv-comp-grid-pager-list-tpl', 'text!sv-comp-grid-pager-select-tpl', 'text!sv-comp-grid-panel-tpl',
        'text!sv-comp-grid-panel-columns-tpl', 'text!sv-comp-grid-panel-filters-tpl',
        'text!sv-comp-grid-panel-export-tpl', 'text!sv-comp-grid-bulk-actions-tpl'
    ],
    function(Vue, SvApp, $, _, gridTpl, gridHeaderRowTpl, gridDataRowTpl, gridPagerListTpl, gridPagerSelectTpl,
             gridPanelTpl, gridPanelColumnsTpl, gridPanelFiltersTpl, gridPanelExportTpl, gridBulkActionsTpl
    ) {
        function prepareFiltersRequest(filters) {
            var result = [], i, f, r;
            for (i in filters) {
                f = filters[i];
                r = {field: f.config.field, op: f.op};
                if (!_.isEmpty(f.values)) {
                    r.val = f.values;
                }
                if (f.value !== '') {
                    r.val = f.value;
                }
                if (f.from) {
                    r.from = f.from;
                }
                if (f.to) {
                    r.to = f.to;
                }
                result[i] = r;
            }
            return JSON.stringify(result);
        }

        function prepareDataRequest(grid) {
            var params = {};
            if (grid && grid.state) {
                if (grid.state.s) {
                    params.s = grid.state.s;
                    params.sd = grid.state.sd;
                }
                if (grid.state.ps) {
                    params.ps = grid.state.ps;
                }
                if (grid.state.p) {
                    params.p = grid.state.p;
                }
                if (grid.filters) {
                    params.filters = prepareFiltersRequest(grid.filters);
                }
            }
            return params;
        }

        function initGridState(grid) {
            var state = grid.state || {};
            if (!state.c) {
                state.c = grid.rows && _.isArrayLike(grid.rows) ? grid.rows.length : 0;
            }
            if (!state.p) {
                state.p = 1;
            }
            if (!state.ps) {
                state.ps = grid.config.default_pagesize || 10;
            }
            if (!state.mp) {
                state.mp = Math.ceil(state.c / state.ps);
            }
console.log(state, grid);
            Vue.set(grid, 'state', state);
        }

        var GridHeaderRow = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            template: gridHeaderRowTpl,
            computed: {
                columns: function () {
                    return this.grid && this.grid.config.columns ? this.grid.config.columns : [];
                },
                sorted: function() {
                    return function (col, dir, def) {
                        if (!col.sortable) {
                            return false;
                        }
                        if (!this.grid || !this.grid.state || this.grid.state.s !== col.field) {
                            return def;
                        }
                        var sd = this.grid.state.sd;
                        return (dir === 'up' && sd === 'asc') || (dir === 'down' && sd === 'desc');
                    }
                },
                visible: function () {
                    return function (col) {

                    }
                }
            },
            methods: {
                toggleSort: function (col) {
                    if (!col.sortable) {
                        return;
                    }
                    if (!this.grid.state) {
                        Vue.set(this.grid, 'state', {});
                    }
                    var s = col.field, sd = 'asc';
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

        var GridDataRow = {
            mixins: [SvApp.mixins.common],
            props: ['grid', 'row'],
            template: gridDataRowTpl,
            computed: {
                columns: function () {
                    return this.grid && this.grid.config.columns ? this.grid.config.columns : [];
                },
                cellClass: function () {
                    return function (row, col) {
                        return '';
                    }
                },
                cellData: function () {
                    return function (row, col) {
                        return row[col.field];
                    }
                },
                isRowSelected: function () {
                    return function (col) {
                        return this.grid.rows_selected && this.grid.rows_selected[this.row[col.id_field]];
                    }
                },
                rowActionLink: function () {
                    return function (row, col, act) {
                        return act.link.replace(/\{([a-z0-9_]+)\}/, function (a, b) {
                            return row[b];
                        });
                    }
                }
            },
            methods: {
                deleteRow: function (row, col, act) {
                    if (!confirm(SvApp._('Are you sure you want to delete the row?'))) {
                        return;
                    }
                    this.$emit('delete-row', row)
                    var vm = this;
                    if (act.delete_url) {
                        SvApp.methods.sendRequest('POST', act.delete_url, postData, function (response) {
                            vm.$emit('fetch-data');
                        });
                    }
                },
                selectRow: function (col) {
                    if (!this.grid.rows_selected) {
                        Vue.set(this.grid, 'rows_selected', {});
                    }
                    var rowId = this.row[col.id_field];
                    Vue.set(this.grid.rows_selected, rowId, !this.grid.rows_selected[rowId]);
                }
            }
        };

        var GridPagerList = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            store: SvApp.store,
            template: gridPagerListTpl,
            computed: {
                pagesizeOptions: function () {
                    return this.grid.config.pagesize_options;
                },
                curPagesize: function () {
                    return this.grid.state ? this.grid.state.ps : 10;
                },
                numPages: function () {
                    return this.grid.state ? this.grid.state.mp : 0;
                },
                curPage: function () {
                    return this.grid.state ? this.grid.state.p : 1;
                }
            },
            methods: {
                setPagesize: function (ps) {
                    initGridState(this.grid);
                    Vue.set(this.grid.state, 'ps', ps);
                    this.$emit('fetch-data');
                },
                goPage: function (p) {
                    initGridState(this.grid);
                    Vue.set(this.grid.state, 'p',  p);
                    this.$emit('fetch-data');
                },
                goPrevPage: function () {
                    initGridState(this.grid);
                    Vue.set(this.grid.state, 'p', Math.max(this.grid.state.p * 1 - 1, 1));
                    this.$emit('fetch-data');
                },
                goNextPage: function () {
                    initGridState(this.grid);
                    Vue.set(this.grid.state, 'p', Math.min(this.grid.state.p * 1 + 1, this.grid.state.mp));
                    this.$emit('fetch-data');
                }
            }
        };

        var GridPagerSelect = $.extend({}, GridPagerList, {template: gridPagerSelectTpl});

        var GridPanelColumns = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            template: gridPanelColumnsTpl,
            store: SvApp.store,
            computed: {
                visible: function () {
                    return function (col) {
                        /*
                        if (!this.$store.state.personalize || !this.$store.state.personalize.grid || !this.$store.state.personalize.grid[this.grid.config.id]) {
                            return {};
                        }
                        return this.$store.state.personalize.grid[this.grid.config.id].columns[col.field];
                        */
                    }
                }
            },
            methods: {
                toggleColumn: function (col) {
                    Vue.set(col, 'hidden', !col.hidden);
                    this.$store.commit('personalizeGridColumn', {grid:this.grid, col:col});
                }
            }
        };

        var GridPanelFilters = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            template: gridPanelFiltersTpl,
            data: function () {
                return {
                    filterToAdd: '',
                    addedFilters: [],
                    ddOpCurrent: ''
                }
            },
            computed: {
                ddName: function () {
                    return function (flt) {
                        return this.grid.config.id + '/panel-filters/' + flt.config.field;
                    }
                },
                ddOpOpen: function () {
                    return function (flt) {
                        return this.ddOpCurrent === flt.config.field;
                    }
                },
                availableFilters: function () {
                    if (!this.grid || !this.grid.config || !this.grid.config.filters) {
                        return [];
                    }
                    var availFilters = [], addedFilters = this.addedFilters, af = {}, i, l;
                    for (i = 0, l = addedFilters.length; i < l; i++) {
                        af[addedFilters[i].config.field] = addedFilters[i];
                    }
                    for (i = 0, l = this.grid.config.filters.length; i < l; i++) {
                        var f = this.grid.config.filters[i];
                        if (af[f.field]) {
                            continue;
                        }
                        availFilters.push(this.grid.config.filters[i]);
                    }
                    return availFilters;
                }
            },
            methods: {
                addFilter: function () {
                    if (!this.filterToAdd) {
                        return;
                    }
                    var i, filters = this.grid.config.filters, f = null;
                    for (i = 0, l = filters.length; i < l; i++) {
                        if (filters[i].field === this.filterToAdd) {
                            f = filters[i];
                        }
                    }
                    if (!f) {
                        return;
                    }
                    var newFilter = {
                        config: f,
                        op: f.default_op,
                        value: '',
                        from: '',
                        to: '',
                        values: []
                    };
                    this.addedFilters.push(newFilter);
                    this.filterToAdd = '';
                },
                removeFilter: function (i) {
                    this.addedFilters.splice(i, 1);
                },
                ddOpToggle: function (flt) {
                    if (this.ddOpCurrent !== flt.config.field) {
                        this.ddOpCurrent = flt.config.field;
                    } else {
                        this.ddOpClear();
                    }
                },
                ddOpClear: function () {
                    this.ddOpCurrent = '';
                },
                switchOp: function (flt, op) {
                    Vue.set(flt, 'op', op);
                    this.ddOpClear();
                },
                applyFilters: function () {
                    var filters = this.addedFilters;
                    this.$emit('apply-filters', filters);
                    this.resetFilters();
                    this.closeDropdown();
                },
                resetFilters: function () {
                    this.addedFilters = [];
                },
                closeDropdown: function () {
                    this.ddToggle(this.grid.config.id + '/panel-filters');
                }
            },
            watch: {
                pageClickCounter: function () {
                    this.ddOpClear();
                }
            }
        };

        var GridPanelExport = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            template: gridPanelExportTpl,
            data: function () {
                return {
                    type: 'csv',
                    export_urls: []
                }
            },
            computed: {
                formatOptions: function () {
                    return this.grid && this.grid.config.export ? this.grid.config.export.format_options : {};
                }
            },
            methods: {
                submit: function () {
                    var url = this.grid.config.export.url;
                    if (!url) {
                        console.error('No export URL privided');
                        return;
                    }
                    url = this.$store.state.env.root_href + url;
                    var params = prepareDataRequest(this.grid);
                    params.type = this.type;
                    this.export_urls.push(url + (url.match(/\?/) ? '&' : '?') + $.param(params));
                    this.ddToggle(this.grid.config.id + '/panel-export');
                }
            }
        };

        var GridBulkActions = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            template: gridBulkActionsTpl,
            computed: {
                actions: function () {
                    return [
                        {label: 'Edit'},
                        {label: 'Delete'}
                    ];
                }
            },
            methods: {
                doAction: function (o) {

                }
            }
        };

        var GridPanel = {
            mixins: [SvApp.mixins.common],
            props: ['grid', 'cnt-visible'],
            components: {
                'sv-comp-grid-pager-list': GridPagerList,
                'sv-comp-grid-panel-columns': GridPanelColumns,
                'sv-comp-grid-panel-filters': GridPanelFilters,
                'sv-comp-grid-panel-export': GridPanelExport,
                'sv-comp-grid-bulk-actions': GridBulkActions
            },
            data: function() {
                return {
                    quickSearch: ''
                }
            },
            computed: {
                cntTotal: function () {
                    return this.grid && this.grid.state ? this.grid.state.c : 0;
                },
                currentFilters: function () {
                    var gridState = this.grid.state;
                    if (!gridState || !gridState.filters) {
                        return [];
                    }
                    return gridState.filters;
                }
            },
            methods: {
                fetchData: function () {
                    this.$emit('fetch-data');
                },
                applyFilters: function (filters) {
                    this.$emit('apply-filters', filters);
                },
                removeFilter: function (flt) {
                    this.$emit('remove-filter', flt);
                },
                updateQuickSearch: function () {
                    initGridState(this.grid);
                    Vue.set(this.grid.state, 'quickSearch', this.quickSearch);
                }
            },
            template: gridPanelTpl
        };

        return {
            props: ['grid'],
            mixins: [SvApp.mixins.common],
            data: function() {
                return {
                    cntVisible: 0
                }
            },
            computed: {
                columns: function () {
                    return this.grid && this.grid.config.columns ? this.grid.config.columns : [];
                },
                columnOptions: function () {
                    var colOptions = {};
                    for (var j = 0; j < this.grid.config.columns; j++) {
                        var col1 = this.grid.config.columns[j]
                        colOptions[col1.field] = col1.options;
                    }
                    return colOptions;
                },
                visibleRows: function () {
                    var q = this.grid && this.grid.state ? this.grid.state.quickSearch : '';
                    if (q === '') {
                        this.cntVisible = this.grid.rows ? this.grid.rows.length : 0;
                        return this.grid.rows;
                    }
                    var rows = [];
                    for (var i in this.grid.rows) {
                        var row = this.grid.rows[i], show = false, colOptions = this.columnOptions;
                        //TODO: optimize, run only once
                        for (var j in row) {
                            if (!row[j]) {
                                continue;
                            }
                            if (colOptions[j]) {
                                var v = colOptions[j][row[j]];
                                if (v && v.match(q)) {
                                    show = true;
                                    break;
                                }
                            } else {
                                if (row[j].match(q)) {
                                    show = true;
                                    break;
                                }
                            }
                        }
                        if (show) {
                            rows.push(row);
                        }
                    }
                    this.cntVisible = rows.length;

                    return rows;
                }
            },
            methods: {
                fetchData: function () {
                    var grid = this.grid, url = grid.config.data_url, params = prepareDataRequest(grid);
                    if (!url) { // local data
                        console.log(grid.state);
                        return;
                    }
                    SvApp.methods.sendRequest('GET', url, params, function (response) {
                        if (response.config) {
                            Vue.set(grid, 'config', response.config);
                        }
                        if (response.state) {
                            Vue.set(grid, 'state', response.state);
                        } else {
                            initGridState(grid);
                        }
                        if (response.rows) {
                            Vue.set(grid, 'rows', response.rows);
                        }
                    });
                },
                applyFilters: function (filters) {
                    var oldFilters = this.grid.filters || [];
                    Vue.set(this.grid, 'filters', oldFilters.concat(filters));
                    this.fetchData();
                },
                removeFilter: function (i) {
                    if (!this.grid || !this.grid.filters) {
                        return;
                    }
                    this.grid.filters.splice(i, 1);
                    //Vue.set(this.grid, 'filters', filters);
                    this.fetchData();
                }
            },
            watch: {
                grid: function (grid) {
                    initGridState(grid);
                }
            },
            mounted: function () {
                this.fetchData();
            },
            components: {
                'sv-comp-grid-header-row': GridHeaderRow,
                'sv-comp-grid-data-row': GridDataRow,
                'sv-comp-grid-panel': GridPanel,
                'sv-comp-grid-pager-select': GridPagerSelect
            },
            template: gridTpl
        };
});