define(['vue', 'sv-hlp', 'jquery', 'lodash',
        'sv-comp-grid-header-row', 'sv-comp-grid-header-cell-default', 'sv-comp-grid-header-cell-row-select',
        'sv-comp-grid-data-row', 'sv-comp-grid-data-cell-default', 'sv-comp-grid-data-cell-row-select', 'sv-comp-grid-data-cell-actions',
        'text!sv-comp-grid-tpl', 'text!sv-comp-grid-header-cell-row-select-tpl',
        'text!sv-comp-grid-pager-list-tpl', 'text!sv-comp-grid-pager-select-tpl',
        'text!sv-comp-grid-panel-tpl',
        'text!sv-comp-grid-panel-columns-tpl', 'text!sv-comp-grid-panel-filters-tpl',
        'text!sv-comp-grid-panel-export-tpl', 'text!sv-comp-grid-bulk-actions-tpl'
    ],
    function(Vue, SvHlp, $, _,
             SvCompGridHeaderRow, SvCompGridHeaderCellDefault, SvCompGridHeaderCellRowSelect,
             SvCompGridDataRow, SvCompGridDataCellDefault, SvCompGridDataCellRowSelect, SvCompGridDataCellActions,
             gridTpl, gridHeaderCellRowSelectTpl,
             gridPagerListTpl, gridPagerSelectTpl,
             gridPanelTpl,
             gridPanelColumnsTpl, gridPanelFiltersTpl,
             gridPanelExportTpl, gridBulkActionsTpl
    ) {

        var dataRowCellComponents = {};

        function prepareFiltersRequest(filters) {
            var result = [], i, f, r;
            for (i in filters) {
                f = filters[i];
                r = {field: f.config.name, op: f.op};
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
            var state = _.extend({}, _.get(grid, 'config.state', {}), grid.state || {});
            if (!state.c) {
                state.c = grid.rows && _.isArrayLike(grid.rows) ? grid.rows.length : 0;
            }
            if (!state.p) {
                state.p = 1;
            }
            if (!state.ps) {
                state.ps = _.get(grid, 'config.default_pagesize', 10);
            }
            if (!state.mp) {
                state.mp = Math.ceil(state.c / state.ps);
            }
            if (state.sc) {
                var s = state.sc.split(' ');
                state.s = s[0];
                state.sd = s[1];
            }
            Vue.set(grid, 'state', state);
        }

        function initGridComponents(grid) {
            if (!grid.config || !grid.config.columns) {
                return;
            }
            if (!grid.components) {
                Vue.set(grid, 'components', {
                    header_columns: {},
                    datacell_columns: {}
                });
            }
            var columns = grid.config.columns, header_deps = [], header_names = [], datacell_deps = [], datacell_names = [];
            for (var i = 0, l = columns.length; i < l; i++) {
                var col = columns[i];
                if (col.header_component) {
                    header_deps.push(col.header_component);
                    header_names.push(col.name);
                } else if (col.header_template) {
                    Vue.set(grid.components.header_columns, col.name, {
                        props: ['grid', 'col'],
                        template: col.datacell_template
                    });
                } else {
                    Vue.set(grid.components.header_columns, col.name, SvCompGridHeaderCellDefault);
                }

                if (col.datacell_component) {
                    datacell_deps.push(col.datacell_component);
                    datacell_names.push(col.name);
                } else if (col.datacell_template) {
                    Vue.set(grid.components.datacell_columns, col.name, {
                        props: ['grid', 'row', 'col'],
                        template: col.datacell_template
                    });
                } else {
                    Vue.set(grid.components.datacell_columns, col.name, SvCompGridDataCellDefault);
                }

            }
            if (header_deps.length) {
                require(header_deps, function () {
                    for (var i = 0, l = arguments.length; i < l; i++) {
                        Vue.set(grid.components.header_columns, header_names[i], arguments[i]);
                    }
                });
            }
            if (datacell_deps.length) {
                require(datacell_deps, function () {
                    for (var i = 0, l = arguments.length; i < l; i++) {
                        Vue.set(grid.components.datacell_columns, datacell_names[i], arguments[i]);
                    }
                });
            }
        }

        function processDataResponse(response, grid) {
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
        }

        function fetchData(grid) {
            grid = grid || this.grid;
            if (!grid.config) {
                return;
            }
            var url = grid.config.data_url;
            if (!url) { // local data
                return;
            }
            var params = prepareDataRequest(grid);
            SvHlp.sendRequest('GET', url, params, function (response) {
                processDataResponse(response, grid);
            });
        }

        var SvCompGridPagerList = {
            mixins: [SvHlp.mixins.common],
            props: ['grid'],
            store: SvHlp.store,
            template: gridPagerListTpl,
            computed: {
                ddName: function () {
                    return _.get(this.grid, 'config.id', Math.random()) + '/pagesize-select';
                },
                pagesizeOptions: function () {
                    return _.get(this.grid, 'config.pager.pagesize_options', []);
                    //return this.grid.config && this.grid.config.pager ? this.grid.config.pager.pagesize_options : [];
                },
                curPagesize: function () {
                    return _.get(this.grid, 'state.ps', 10);
                },
                numPages: function () {
                    return _.get(this.grid, 'state.mp', 0);
                },
                curPage: function () {
                    return _.get(this.grid, 'state.p', 1);
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

        var SvCompGridPagerSelect = $.extend({}, SvCompGridPagerList, {template: gridPagerSelectTpl});

        var SvCompGridPanelColumns = {
            mixins: [SvHlp.mixins.common],
            props: ['grid'],
            template: gridPanelColumnsTpl,
            store: SvHlp.store,
            computed: {
                visible: function () {
                    return function (col) {
                        /*
                        if (!this.$store.state.personalize || !this.$store.state.personalize.grid || !this.$store.state.personalize.grid[this.grid.config.id]) {
                            return {};
                        }
                        return this.$store.state.personalize.grid[this.grid.config.id].columns[col.name];
                        */
                    }
                }
            },
            methods: {
                toggleColumn: function (col) {
                    Vue.set(col, 'hidden', !col.hidden);
                    this.$store.commit('personalizeGridColumn', {grid:this.grid, col:col});
                },
                sortingUpdate: function (ev) {
                    console.log(ev);
                }
            }
        };

        var SvCompGridPanelFilters = {
            mixins: [SvHlp.mixins.common],
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
                        return this.grid.config.id + '/panel-filters/' + flt.config.name;
                    }
                },
                ddOpOpen: function () {
                    return function (flt) {
                        return this.ddOpCurrent === flt.config.name;
                    }
                },
                availableFilters: function () {
                    var availFilters = [{id:'', text:''}];
                    if (!_.get(this.grid, 'config.filters')) {
                        return availFilters;
                    }
                    var addedFilters = this.addedFilters, af = {}, i, l;
                    for (i = 0, l = addedFilters.length; i < l; i++) {
                        af[addedFilters[i].config.name] = addedFilters[i];
                    }
                    for (i = 0, l = this.grid.config.filters.length; i < l; i++) {
                        var f = this.grid.config.filters[i];
                        if (af[f.name]) {
                            continue;
                        }
                        availFilters.push({id: f.name, text: f.label});
                    }
                    return availFilters;
                },
                addFilterSelect2Params: function () {
                    return {
                        data: this.availableFilters,
                        allowClear: true,
                        placeholder: SvHlp._(this.availableFilters.length > 1 ? 'Add filter...' : 'No filters available to add')
                    };
                }
            },
            methods: {
                addFilter: function () {
                    if (!this.filterToAdd) {
                        return;
                    }
                    var i, filters = this.grid.config.filters, f = null;
                    for (i = 0, l = filters.length; i < l; i++) {
                        if (filters[i].name === this.filterToAdd) {
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
                    if (this.ddOpCurrent !== flt.config.name) {
                        this.ddOpCurrent = flt.config.name;
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
                },
                filterToAdd: function (value) {
                    if (!value) {
                        return;
                    }
                    this.addFilter();
                    this.filterToAdd = '';
                }
            }
        };

        var SvCompGridPanelExport = {
            mixins: [SvHlp.mixins.common],
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
                    return _.get(this.grid, 'config.export.format_options', {});
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

        var SvCompGridBulkActions = {
            mixins: [SvHlp.mixins.common],
            props: ['grid'],
            template: gridBulkActionsTpl,
            computed: {
                actions: function () {
                    return _.get(this.grid, 'config.bulk_actions', []);
                }
            },
            methods: {
                bulkAction: function (o) {
                    this.$emit('bulk-action', o);
                }
            }
        };

        var SvCompGridPanel = {
            mixins: [SvHlp.mixins.common],
            props: ['grid', 'cnt-visible'],
            components: {
                'sv-comp-grid-pager-list': SvCompGridPagerList,
                'sv-comp-grid-panel-columns': SvCompGridPanelColumns,
                'sv-comp-grid-panel-filters': SvCompGridPanelFilters,
                'sv-comp-grid-panel-export': SvCompGridPanelExport,
                'sv-comp-grid-bulk-actions': SvCompGridBulkActions
            },
            data: function() {
                return {
                    quickSearch: ''
                }
            },
            computed: {
                cntTotal: function () {
                    return _.get(this.grid, 'state.c', 0);
                },
                currentFilters: function () {
                    return _.get(this.grid, 'state.filters', []);
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
                bulkAction: function (act) {
                    this.$emit('bulk-action', act);
                }
            },
            template: gridPanelTpl,
            watch: {
                quickSearch: function (value) {
                    Vue.set(this.grid.state, 'quickSearch', value);
                }
            }
        };

        return {
            props: ['grid'],
            mixins: [SvHlp.mixins.common],
            data: function() {
                return {
                    cntVisible: 0
                }
            },
            computed: {
                columns: function () {
                    return _.get(this.grid, 'config.columns', []);
                },
                columnOptions: function () {
                    var columns = _.get(this.grid, 'config.columns', []), colOptions = {};
                    for (var j = 0; j < columns; j++) {
                        var col1 = columns[j];
                        colOptions[col1.name] = col1.options;
                    }
                    return colOptions;
                },
                visibleRows: function () {
                    var q = _.get(this.grid, 'state.quickSearch', '');
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
                                if ((_.isString(row[j]) || _.isNumber(row[j])) && row[j].match(q)) {
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
                fetchData: fetchData,
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
                },
                bulkAction: function (act) {
                    console.log(act);
                }
            },
            created: function () {
                initGridState(this.grid);
                initGridComponents(this.grid);
                this.fetchData();
            },
            components: {
                'sv-comp-grid-header-row': SvCompGridHeaderRow,
                'sv-comp-grid-data-row': SvCompGridDataRow,
                'sv-comp-grid-panel': SvCompGridPanel,
                'sv-comp-grid-pager-select': SvCompGridPagerSelect
            },
            template: gridTpl
        };
});