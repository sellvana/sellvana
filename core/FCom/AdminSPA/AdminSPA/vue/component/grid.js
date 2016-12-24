define(['vue', 'sv-app', 'jquery',
        'text!sv-comp-grid-tpl', 'text!sv-comp-grid-header-row-tpl', 'text!sv-comp-grid-data-row-tpl',
        'text!sv-comp-grid-pager-list-tpl', 'text!sv-comp-grid-pager-select-tpl', 'text!sv-comp-grid-panel-tpl',
        'text!sv-comp-grid-panel-columns-tpl', 'text!sv-comp-grid-panel-filters-tpl', 'text!sv-comp-grid-panel-filters-current-tpl',
        'text!sv-comp-grid-panel-export-tpl', 'text!sv-comp-grid-bulk-actions-tpl'
    ],
    function(Vue, SvApp, $, gridTpl, gridHeaderRowTpl, gridDataRowTpl, gridPagerListTpl, gridPagerSelectTpl, gridPanelTpl,
             gridPanelColumnsTpl, gridPanelFiltersTpl, gridPanelFiltersCurrentTpl, gridPanelExportTpl, gridBulkActionsTpl
    ) {
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
                }
            },
            methods: {
                deleteRow: function (row, col, act) {
                    if (!confirm(SvApp._('Are you sure you want to delete the row?'))) {
                        return;
                    }
                    SvApp.methods.sendRequest('POST', act.delete_url, postData, function (response) {
                        this.$emit('fetch-data');
                    });
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
                    this.grid.state.ps = ps;
                    this.$emit('fetch-data');
                },
                goPage: function (p) {
                    this.grid.state.p = p;
                    this.$emit('fetch-data');
                },
                goPrevPage: function () {
                    this.grid.state.p = Math.max(this.grid.state.p * 1 - 1, 1);
                    this.$emit('fetch-data');
                },
                goNextPage: function () {
                    this.grid.state.p = Math.min(this.grid.state.p * 1 + 1, this.grid.state.mp);
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
                    filterToAdd: ''
                }
            },
            computed: {
                availableFilters: function () {
                    return [];
                },
                addedFilters: function () {
                    return [{}];
                }
            },
            methods: {
                addFilter: function () {

                },
                applyFilters: function () {

                }
            }
        };

        var GridPanelFiltersCurrent = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            template: gridPanelFiltersCurrentTpl,
            computed: {
                currentFilters: function () {
                    var gridState = this.grid.state;
                    if (!gridState || !gridState.filters) {
                        return [];
                    }
                    return gridState.filters;
                }
            },
            methods: {
                removeFilter: function (flt) {
                    var gridState = this.grid.state;
                    if (!gridState || !gridState.filters || !gridState.filters[flt.field]) {
                        return;
                    }
                    delete gridState.filters[flt.field];
                    if (!Object.keys(gridState.filters).length) {
                        delete gridState.filters;
                    }
                }
            }
        };

        var GridPanelExport = {
            mixins: [SvApp.mixins.common],
            props: ['grid'],
            template: gridPanelExportTpl
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
                'sv-comp-grid-panel-filters-current': GridPanelFiltersCurrent,
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
                }
            },
            methods: {
                fetchData: function () {
                    this.$emit('fetch-data');
                },
                updateQuickSearch: function () {
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
                visibleRows: function () {
                    var q = this.grid && this.grid.state ? this.grid.state.quickSearch : '';
                    if (q === '') {
                        this.cntVisible = this.grid.rows ? this.grid.rows.length : 0;
                        return this.grid.rows;
                    }
                    var rows = [];
                    for (var i in this.grid.rows) {
                        var row = this.grid.rows[i], show = false, colOptions = {};
                        //TODO: optimize, run only once
                        for (var j = 0; j < this.grid.config.columns; j++) {
                            var col1 = this.grid.config.columns[j]
                            colOptions[col1.field] = col1.options;
                        }
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
                    var grid = this.grid, url = grid.config.data_url, params = {types: 'rows'};
                    if (grid.state) {
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
                    }
                    SvApp.methods.sendRequest('GET', url, params, function (response) {
                        if (response.config) {
                            Vue.set(grid, 'config', response.config);
                        }
                        if (response.state) {
                            Vue.set(grid, 'state', response.state);
                        }
                        if (response.rows) {
                            Vue.set(grid, 'rows', response.rows);
                        }
                    });
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