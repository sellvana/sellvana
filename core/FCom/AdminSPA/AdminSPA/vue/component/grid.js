define(['vue', 'jquery', 'lodash', 'vue-draggable', 'sv-comp-popup',
        'sv-comp-grid-header-row', 'sv-comp-grid-header-cell-default', 'sv-comp-grid-header-cell-row-select',
        'sv-comp-grid-data-row', 'sv-comp-grid-data-cell-default', 'sv-comp-grid-data-cell-row-select', 'sv-comp-grid-data-cell-actions',
        'text!sv-comp-grid-tpl', 'text!sv-comp-grid-header-cell-row-select-tpl',
		'text!sv-comp-grid-pager-list-tpl', 'text!sv-comp-grid-pager-select-tpl', 'text!sv-comp-grid-pager-dropdown-tpl',
        'text!sv-comp-grid-panel-tpl',
        'text!sv-comp-grid-panel-columns-tpl', 'text!sv-comp-grid-panel-filters-tpl',
        'text!sv-comp-grid-panel-export-tpl', 'text!sv-comp-grid-bulk-actions-tpl'
    ],
    function(Vue, $, _, VueDraggable, SvCompPopup,
             SvCompGridHeaderRow, SvCompGridHeaderCellDefault, SvCompGridHeaderCellRowSelect,
             SvCompGridDataRow, SvCompGridDataCellDefault, SvCompGridDataCellRowSelect, SvCompGridDataCellActions,
             gridTpl, gridHeaderCellRowSelectTpl,
			 gridPagerListTpl, gridPagerSelectTpl, gridPagerDropdownTpl,
             gridPanelTpl,
             gridPanelColumnsTpl, gridPanelFiltersTpl,
             gridPanelExportTpl, gridBulkActionsTpl
    ) {

        var dataRowCellComponents = {};

        function prepareFiltersRequest(filters) {
            var result = [], i, f, r;
            for (i in filters) {
                f = filters[i];
                r = {field: f.config.name, op: f.op, val: f.val};
                if (!_.isEmpty(f.values)) {
                    r.val = f.values;
                }
                if (f.value !== '' && typeof f.value !== 'undefined') {
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
console.log(filters, result);
            return result;
        }

        function prepareDataRequest(grid) {
            var params = {}, state = _.get(grid, 'config.state', {});
            if (state.s) {
                params.s = state.s;
                params.sd = state.sd;
            }
            if (state.ps) {
                params.ps = state.ps;
            }
            if (state.p) {
                params.p = state.p;
            }
            if (state.filters) {
                params.filters = prepareFiltersRequest(state.filters);
            }
            return params;
        }

        function initGridState(grid) {
            var state = _.get(grid, 'config.state', {});
            if (!state.c) {
                state.c = grid.rows && _.isArrayLike(grid.rows) ? grid.rows.length : 0;
            }
            if (!state.p) {
                state.p = 1;
            }
            if (!state.ps) {
                state.ps = 10;
            }
            if (!state.mp) {
                state.mp = Math.ceil(state.c / state.ps);
            }
            if (state.sc) {
                var s = state.sc.split(' ');
                state.s = s[0];
                state.sd = s[1];
            }
            if (!grid.config) {
                Vue.set(grid, 'config', {});
            }
            Vue.set(grid.config, 'state', state);
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

        function initGridConfig(grid, storeState)
        {
            var cols = {}, i, l, c;

            if (storeState.grid && storeState.grid[grid.config.id]) {
                Vue.set(grid.config, 'columns', storeState.grid[grid.config.id].config.columns);
            }

            if (grid.config && grid.config.columns) {
                for (i = 0, l = grid.config.columns.length; i < l; i++) {
                    c = grid.config.columns[i];
                    cols[c.name] = c;
                }
                Vue.set(grid.config, 'columns_by_name', cols);
            }
        }

        function processDataResponse(response, grid) {
            if (response.config) {
                Vue.set(grid, 'config', response.config);
            }
            if (response.state) {
                Vue.set(grid.config, 'state', response.state);
            } else {
                initGridState(grid);
            }
            if (response.rows) {
                Vue.set(grid, 'rows', response.rows);
            }
        }

        var SvCompGridPagerList = {
            props: ['grid'],
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
                    return _.get(this.grid, 'config.state.ps', 10);
                },
                numPages: function () {
                    return _.get(this.grid, 'config.state.mp', 0);
                },
                curPage: function () {
                    return _.get(this.grid, 'config.state.p', 1);
                }
            },
            methods: {
                setPagesize: function (ps) {
                    initGridState(this.grid);
                    this.$set(this.grid.config.state, 'ps', ps);
                    this.emitEvent('fetch-data');
                },
                goPage: function (p) {
                    initGridState(this.grid);
                    this.$set(this.grid.config.state, 'p',  p);
                    this.emitEvent('fetch-data');
                },
                goPrevPage: function () {
                    initGridState(this.grid);
                    this.$set(this.grid.config.state, 'p', Math.max(this.grid.config.state.p * 1 - 1, 1));
                    this.emitEvent('fetch-data');
                },
                goNextPage: function () {
                    initGridState(this.grid);
                    this.$set(this.grid.config.state, 'p', Math.min(this.grid.config.state.p * 1 + 1, this.grid.config.state.mp));
                    this.emitEvent('fetch-data');
                }
            }
        };

        var SvCompGridPagerSelect = $.extend({}, SvCompGridPagerList, {template: gridPagerSelectTpl});
	
        var SvCompGridPagerDropdown = $.extend({}, SvCompGridPagerList, {template: gridPagerDropdownTpl});

        var SvCompGridPanelColumns = {
            props: ['grid'],
            template: gridPanelColumnsTpl,
            components: {
                draggable: VueDraggable
            },
            methods: {
                toggleColumn: function (col) {
                    this.$set(col, 'hidden', !col.hidden);
                    //this.$store.commit('personalizeGridColumn', {grid:this.grid, col:col});

                    if (!this.grid.config.personalize_url) {
                        console.error('No personalize_url set');
                        return;
                    }
                    var vm = this, postData = {
                        do: 'grid.col.hidden',
                        grid: this.grid.config.id,
                        col: col.name,
                        hidden: col.hidden
                    };
                    this.sendRequest('POST', this.grid.config.personalize_url, postData, function (response) {
                        //vm.$emit('event', 'update-config', response.grid.config);
                    });
                },
                onDraggableEnd: function () {
                    var vm = this, i, l, cols = [], col;
                    for (i = 0, l = this.grid.config.columns.length; i < l; i++) {
                        col = this.grid.config.columns[i];
                        this.$set(col, 'position', i + 1);
                        cols.push({name: col.name, position: i + 1});
                    }
                    var postData = {
                        do: 'grid.cols.order',
                        grid: this.grid.config.id,
                        cols: cols
                    };
                    this.sendRequest('POST', this.grid.config.personalize_url, postData, function (response) {

                    });
                }
            }
        };

        var SvCompGridPanelFilters = {
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
                        placeholder: this.availableFilters.length > 1 ? this._(('Add filter...')) : this._(('No filters available to add'))
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
                    this.$set(flt, 'op', op);
                    this.ddOpClear();
                },
                applyFilters: function () {
                    var filters = this.addedFilters;
                    this.emitEvent('apply-filters', filters);
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
                page_click_counter: function () {
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
            props: ['grid'],
            template: gridBulkActionsTpl,
            computed: {
                actions: function () {
                    return _.get(this.grid, 'config.bulk_actions', []);
                }
            },
            methods: {
                doBulkAction: function (act) {
                    this.emitEvent('bulk-action', act);
                }
            }
        };

        var SvCompGridPanel = {
            props: ['grid', 'cnt-visible'],
            components: {
                'sv-comp-grid-pager-list': SvCompGridPagerList,
				'sv-comp-grid-pager-dropdown': SvCompGridPagerDropdown,
                'sv-comp-grid-panel-columns': SvCompGridPanelColumns,
                'sv-comp-grid-panel-filters': SvCompGridPanelFilters,
                'sv-comp-grid-panel-export': SvCompGridPanelExport,
                'sv-comp-grid-bulk-actions': SvCompGridBulkActions
            },
            data: function() {
                return {
                    quickSearch: '',
                    settingsTab: 'columns'
                }
            },
            computed: {
                cntTotal: function () {
                    var cnt = _.get(this.grid, 'config.state.c', 0);
                    if (!cnt && this.grid.rows) {
                        cnt = this.grid.rows.length;
                    }
                    return cnt;
                },
                currentFilters: function () {
                    return _.get(this.grid, 'config.state.filters', []);
                },
                hasFilters: function () {
                    return this.currentFilters && !_.isEmpty(this.currentFilters);
                }
            },
            methods: {
                onEvent: function (event, args) {
                    this.emitEvent(event, args);
                },
                setSettingsTab: function (tab) {
                    this.settingsTab = tab;
                }
            },
            template: gridPanelTpl,
            watch: {
                quickSearch: function (value) {
                    this.$set(this.grid.config.state, 'quickSearch', value);
                }
            }
        };

        var SvCompGrid = {
            props: ['grid'],
            data: function() {
                return {
                    cntVisible: 0
                }
            },
            computed: {
                gridTitle: function () {
                    return _.get(this.grid, 'config.title', 'Grid Title Not Set');
                },
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
                    var q = _.get(this.grid, 'config.state.quickSearch', '');
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
                                if ((_.isString(row[j]) && row[j].match(new RegExp(q, 'i'))) || (_.isNumber(row[j]) && row[j] == q)) {
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
                onEvent: function (event, arg) {
                    switch (event) {
                        case 'fetch-data': this.fetchData(arg); break;
                        case 'apply-filters': this.applyFilters(arg); break;
                        case 'remove-filter': this.removeFilter(arg); break;
                        case 'remove-all-filters': this.removeAllFilters(arg); break;
                        case 'update-config': this.updateConfig(arg); break;
                        case 'bulk-action': this.doBulkAction(arg); break;
                        case 'panel-action': this.doPanelAction(arg); break;
                        case 'row-action': this.doRowAction(arg); break;
                        default: this.emitEvent(event, arg);
                    }
                },
                fetchData: function (grid) {
                    grid = grid || this.grid;
                    if (!grid.config) {
                        return;
                    }
                    var url = grid.config.data_url;
                    if (!url) { // local data
                        this.fetchLocalData(grid);
                        return;
                    }
                    var params = prepareDataRequest(grid);
                    this.sendRequest('GET', url, params, function (response) {
                        processDataResponse(response, grid);
                    });
                },
                fetchLocalData: function (grid)
                {
                    var data = grid.config.data, state = {};

                    //Vue.set(grid, 'state', state);
                    this.$set(grid, 'rows', data);
                    initGridState(grid);
                },
                applyFilters: function (filters) {
                    var oldFilters = _.get(this.grid, 'config.state.filters', []) || [];
                    this.$set(this.grid.config.state, 'filters', oldFilters.concat(filters));
                    this.fetchData();
                },
                removeFilter: function (i) {
                    if (!this.grid || !this.grid.config.state || !this.grid.config.state.filters) {
                        return;
                    }
                    this.grid.config.state.filters.splice(i, 1);
                    //Vue.set(this.grid, 'filters', filters);
                    this.fetchData();
                },
                removeAllFilters: function () {
                    if (!this.grid || !this.grid.config || !this.grid.config.state || !this.grid.config.state.filters) {
                        return;
                    }
                    this.$set(this.grid.config.state, 'filters', []);
                    this.fetchData();
                },
                doBulkAction: function (act) {
                    if (!Object.keys(this.grid.rows_selected).length) {
                        alert(this._(('Please select some rows first')));
                        return;
                    }
                    this.emitEvent('bulk-action', act);
                    console.log(act);
                    if (act.popup) {
                        this.$set(this.grid, act.action_type || 'popup', act.popup);
                        // this.grid.popup.grid = this.grid;
                        // this.grid.popup.open = true;
                    }
                },
                doPanelAction: function (act) {
                    this.emitEvent('panel-action', act);
                },
                doRowAction: function (act) {
                    this.emitEvent('row-action', act);
                },
                updateConfig: function (config) {
                    this.$set(this.grid, 'config', config);
                }
            },
            created: function () {
                initGridState(this.grid);
                initGridConfig(this.grid, this.$store.state);
                initGridComponents(this.grid);
                this.fetchData();
            },
            components: {
                'sv-comp-grid-header-row': SvCompGridHeaderRow,
                'sv-comp-grid-data-row': SvCompGridDataRow,
                'sv-comp-grid-panel': SvCompGridPanel,
                'sv-comp-grid-pager-select': SvCompGridPagerSelect,
				'sv-comp-grid-pager-dropdown': SvCompGridPagerDropdown
            },
            template: gridTpl,
            watch: {
                'grid.fetch_data_flag': function (flag) {
                    if (flag) {
                        this.fetchData();
                        this.grid.fetch_data_flag = false;
                    }
                }
            }
        };

        Vue.component('sv-comp-grid', SvCompGrid);

        return SvCompGrid;
});