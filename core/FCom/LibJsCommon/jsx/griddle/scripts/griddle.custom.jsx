/** @jsx React.DOM */

/*
   Griddle - Simple Grid Component for React
   https://github.com/DynamicTyped/Griddle
   Copyright (c) 2014 Ryan Lanciaux | DynamicTyped

   See License / Disclaimer https://raw.githubusercontent.com/DynamicTyped/Griddle/master/LICENSE
*/
define(['jquery', 'underscore', 'react', 'griddle.gridNoData', 'fcom.components',], function($, _, React, GridNoData, Components) {
/*
var React = require('react');
var GridBody = require('./gridBody.jsx');
var GridFilter = require('./gridFilter.jsx');
var GridPagination = require('./gridPagination.jsx');
var GridSettings = require('./gridSettings.jsx');
var GridTitle = require('./gridTitle.jsx');
var GridNoData = require('./gridNoData.jsx');
var CustomFormatContainer = require('./customFormatContainer.jsx');
var CustomPaginationContainer = require('./customPaginationContainer.jsx');
var _ = require('underscore');
*/
var Griddle = React.createClass({
    mixins: [FCom.Mixin],
    getDefaultProps: function() {
        return{
            "columns": [],
            "columnMetadata": [],
            "resultsPerPage":5,
            "results": [], // Used if all results are already loaded.
            "getExternalResults": null, // Used if obtaining results from an API, etc.
            "initialSort": "",
            "gridClassName":"",
            "tableClassName":"",
            "customFormatClassName":"",
            "settingsText": "Settings",
            "filterPlaceholderText": "Filter Results",
            "nextText": "Next",
            "previousText": "Previous",
            "maxRowsText": "Rows per page",
            "enableCustomFormatText": "Enable Custom Formatting",
            //this column will determine which column holds subgrid data
            //it will be passed through with the data object but will not be rendered
            "childrenColumnName": "children",
            //Any column in this list will be treated as metadata and will be passed through with the data but won't be rendered
            "metadataColumns": [],
            "showFilter": false,
            "showSettings": false,
            "useCustomFormat": false,
            "useCustomPager": false,
            "customFormat": {},
            "customPager": {},
            "allowToggleCustom":false,
            "noDataMessage":"There is no data to display.",
            "customNoData": null,
            "showTableHeading":true,
            "showPager":true,
            //custom for fcom component
            "useCustomFilter": false,
            "useCustomSettings": false,
            "useCustomGrid": false,
            "customFilter": {},
            "customSettings": {},
            "customGrid": {},
            "initPage": 0, //begin with 0 page
            "addRowsExternal": null,
            "updateRowsExternal": null,
            "removeRowsExternal": null
        };
    },
    /* if we have a filter display the max page and results accordingly */
    setFilter: function(filter) {
        if(filter){
            var that = this,
                state = {
                    page: 0,
                    filter: filter
                },
                updateAfterResultsObtained = function(updatedState) {
                    // Update the max page.
                    updatedState.maxPage = that.getMaxPage(updatedState.filteredResults);

                    // Set the state.
                    that.setState(updatedState);
                };

            // Obtain the state results.
            if (this.hasExternalResults()) {
                // Update the state with external results.
                this.updateStateWithExternalResults(state, updateAfterResultsObtained);
            } else {
               state.filteredResults = _.filter(this.state.results,
                function(item) {
                    var arr = _.values(item);
                    for(var i = 0; i < arr.length; i++){
                       if ((arr[i]||"").toString().toLowerCase().indexOf(filter.toLowerCase()) >= 0){
                        return true;
                       }
                    }

                    return false;
                });

                // Update the state after obtaining the results.
                updateAfterResultsObtained(state);
            }
        } else {
            this.setState({
                filteredResults: null,
                filter: filter,
                maxPage: this.getMaxPage(null)
            });
        }
    },
    getExternalResults: function(state, callback) {
        //console.log('getExternalResults');
        // use init data in grid config
        if (this.state.isInit) {
            var fcomData = this.getConfig('data');
            callback({ results: fcomData.data, totalResults: fcomData.state.c });
            return false;
        }

        var filter,
            sortColumn,
            sortAscending,
            page;

        // Fill the search properties.
        if (state !== undefined && state.filter !== undefined) {
            filter = state.filter;
        } else {
            filter = this.state.filter;
        }

        if (state !== undefined && state.sortColumn !== undefined) {
            sortColumn = state.sortColumn;
        } else {
            sortColumn = this.state.sortColumn;
        }

        if (state !== undefined && state.sortAscending !== undefined) {
            sortAscending = state.sortAscending;
        } else {
            sortAscending = this.state.sortAscending;
        }

        if (state !== undefined && state.page !== undefined) {
            page = state.page;
        } else {
            page = this.state.page;
        }

        var options = {
            dataUrl: this.getConfig('data_url'),
            gridId: this.getConfig('id')
        };

        // Obtain the results
        this.props.getExternalResults(filter, sortColumn, sortAscending, page, this.props.resultsPerPage, callback, options);
    },
    updateStateWithExternalResults: function(state, callback) {
        // Update the table to indicate that it's loading.
        //this.setState({ isLoading: true });
        var that = this;

        // Grab the results.
        this.getExternalResults(state, function(externalResults) {
            // Fill the state result properties
            state.results = externalResults.results;
            state.totalResults = externalResults.totalResults;
            state.isLoading = false;
            state.isInit = false;

            //todo: re-check this code again to reduce set state and avoid re-render
            //fix pagination when get data from external results
            that.setState({
                results: externalResults.results,
                totalResults: externalResults.totalResults,
                isLoading: false,
                isInit: false,
                filteredResults: null,
                maxPage: that.getMaxPage(externalResults.results)
            });

            callback(state);
        });
    },
    hasExternalResults: function() {
        return typeof(this.props.getExternalResults) === 'function';
    },
    setPageSize: function(size){
        //make this better.
        this.props.resultsPerPage = size;
        this.setMaxPage();
    },
    toggleColumnChooser: function(){
        this.setState({
            showColumnChooser: this.state.showColumnChooser == false
        });
    },
    toggleCustomFormat: function(){
        this.setProps({
            useCustomFormat: this.props.useCustomFormat == false
        });
    },
    getMaxPage: function(results){
        var totalResults;
        if (this.hasExternalResults()) {
            totalResults = this.state.totalResults;
        } else {
            totalResults = (results||this.state.results).length;
        }
        var maxPage = Math.ceil(totalResults / this.props.resultsPerPage);
        return maxPage;
    },
    setMaxPage: function(results){
        var maxPage = this.getMaxPage();
        //re-render if we have new max page value
        if (this.state.maxPage != maxPage){
            this.setState({ maxPage: maxPage, filteredColumns: this.props.columns, initColumns: this.props.initColumns }, function() {
                this.saveLocalState();
            });
        }
    },
    setPage: function(number) {
       //check page size and move the filteredResults to pageSize * pageNumber
        if (number * this.props.resultsPerPage <= this.props.resultsPerPage * this.state.maxPage) {
            var that = this,
                state = {
                    page: number
                };

            if (this.hasExternalResults()) {
                this.updateStateWithExternalResults(state, function(updatedState) {
                    that.setState(updatedState);
                });
            } else {
                that.setState(state, function() {
                    this.saveLocalState();
                });
            }
        }
    },
    getColumns: function(){
        var that = this;

        //if we don't have any data don't mess with this
        //if (this.state.results === undefined || this.state.results.length == 0){ return [];}

        var result = this.state.filteredColumns;

        //if we didn't set default or filter
        if (this.state.filteredColumns.length == 0){
            var meta = [].concat(this.props.metadataColumns);
            meta.push(this.props.childrenColumnName);
            result =  _.keys(_.omit(this.state.results[0], meta));
        }


        result = _.sortBy(result, function(item){
            var metaItem = _.findWhere(that.props.columnMetadata, {columnName: item});

            if (typeof metaItem === 'undefined' || metaItem === null || isNaN(metaItem.order)){
                return 100;
            }

            return metaItem.order;
        });

        return result;
    },
    setColumns: function(columns){
        columns = _.isArray(columns) ? columns : [columns];
        this.setState({
            filteredColumns: columns
        });
    },
    nextPage: function() {
        if (this.state.page < this.state.maxPage - 1) { this.setPage(this.state.page + 1); }
    },
    previousPage: function() {
        if (this.state.page > 0) { this.setPage(this.state.page - 1); }
    },
    changeSort: function(sort){
        var that = this,
            state = {
                page:0,
                sortColumn: sort,
                sortAscending: true
            };

        // If this is the same column, reverse the sort.
        if (this.state.sortColumn == sort) {
            state.sortAscending = this.state.sortAscending == false;
            //sortAscending: true -> false -> clear -> true ...
            if (this.state.sortAscending === false) {
                state.sortColumn = '';
            }
        }

        if (this.hasExternalResults()) {
            this.updateStateWithExternalResults(state, function(updatedState) {
                that.setState(updatedState);
            });
        } else {
            this.setState(state, function() {
                this.saveLocalState();
            });
        }
    },
    componentWillReceiveProps: function(nextProps) {
        if (this.hasExternalResults()) {
            // TODO: Confirm
            var state = this.state,
                that = this;

            // Update the state with external results.
            state = this.updateStateWithExternalResults(state, function(updatedState) {
                that.setState(updatedState);
            });
        } else {
            this.setMaxPage(nextProps.results);
        }
    },
    getInitialState: function() {
        var state =  {
            maxPage: 0,
            page: this.props.initPage,
            filteredResults: null,
            filteredColumns: this.props.columns,
            filter: "",
            sortColumn: this.props.sortColumn,
            sortAscending: this.props.sortAscending,
            showColumnChooser: false,
            isLoading: false,
            //fcom custom
            initColumns: this.props.initColumns, //init columns include all hide columns
            isInit: true,
            selectedRows: [],
            headerSelect: 'show_all' //select value in header dropdown
        };

        //set current filter state
        var filters = {};
        var configFilters = this.getConfig('filters');
        if (configFilters && configFilters.length) {
            _.forEach(configFilters, function(f) {
                if (f.val != '') {
                    f.submit = true;
                    filters[f.field] = f;
                }
            });
            state.filter = JSON.stringify(filters);
        }

        // If we need to get external results, grab the results.
        if (!this.hasExternalResults()) {
            state.results = this.props.results;
            state.totalResults = this.props.results.length;

            //filter local data if we have local_personalise config
            if (this.getConfig('data_mode') == 'local' && this.getConfig('local_personalize') == true && this.getConfig('filters').length) {
                var results = this.filterLocalData(this.props.results, filters);
                state.filteredResults = results;
                state.totalResults = results.length;
                state.maxPage = this.getMaxPage(results);
            }
        } else {
            state.isLoading = true; // Initialize to 'loading'
        }
        return state;
    },
    componentWillMount: function() {
        if (!this.hasExternalResults()) {
            this.setMaxPage();
        }
    },
    componentDidMount: function() {
        var state = this.state;
        var that = this;
        if (this.hasExternalResults()) {
            // Update the state with external results when mounting
            state = this.updateStateWithExternalResults(state, function(updatedState) {
                that.setState(updatedState);
                that.setMaxPage();
            });
        }
        this.triggerCallback('componentDidMount');
    },
    componentDidUpdate: function() {
        //console.log('state', this.state);
        this.triggerCallback('componentDidUpdate');
    },
    getDataForRender: function(data, cols, pageList){
        var that = this;
        if (!this.hasExternalResults()) {
            //get the correct page size
            if(this.state.sortColumn != "" || this.props.initialSort != ""){
                data = _.sortBy(data, this.state.sortColumn);
                if(this.state.sortAscending == false){
                    data.reverse();
                }
            }

            if (pageList && (this.props.resultsPerPage * (this.state.page+1) <= this.props.resultsPerPage * this.state.maxPage) && (this.state.page >= 0)) {
                //the 'rest' is grabbing the whole array from index on and the 'initial' is getting the first n results
                var rest = _.rest(data, this.state.page * this.props.resultsPerPage);
                data = _.initial(rest, rest.length-this.props.resultsPerPage);
            }
        } else {
            // Don't sort or page data if loaded externally.
        }

        //console.log('getDataForRender.data', data);

        var meta = [].concat(this.props.metadataColumns);
        meta.push(this.props.childrenColumnName);

        //custom for fcom
        if (_.indexOf(meta, 'id') == -1) {
            meta.push('id');
        }

        var transformedData = [];

        for(var i = 0; i<data.length; i++){
            var mappedData = _.pick(data[i], cols.concat(meta));

            if(typeof mappedData[that.props.childrenColumnName] !== "undefined" && mappedData[that.props.childrenColumnName].length > 0){
                //internally we're going to use children instead of whatever it is so we don't have to pass the custom name around
                mappedData["children"] = that.getDataForRender(mappedData[that.props.childrenColumnName], cols, false);

                if(that.props.childrenColumnName !== "children") { delete mappedData[that.props.childrenColumnName]; }
            }

            transformedData.push(mappedData);
        }

        return transformedData;
    },
    render: function() {
        //console.log('this.state.filteredResults', this.state.filteredResults);
        //console.log('this.state.filteredColumns', this.state.filteredColumns);
        //console.log('this.state.initColumns', this.state.initColumns);
        var that = this,
            results = this.state.filteredResults || this.state.results; // Attempt to assign to the filtered results, if we have any.
            results = _.uniq(results);
        var headerTableClassName = this.props.tableClassName + " table-header";

        //figure out if we want to show the filter section
        var filter = this.props.showFilter ?
            (
                this.props.useCustomFilter
                ? <this.props.customFilter changeFilter={this.setFilter} changeFilterLocalData={this.setFilterLocalData} getConfig={this.getConfig} />
                : <GridFilter changeFilter={this.setFilter} placeholderText={this.props.filterPlaceholderText} />
            ) : "";
        var settings = this.props.showSettings ?
        (
            this.props.useCustomSettings
            ? <this.props.customSettings columnMetadata={this.props.columnMetadata} selectedColumns={this.getColumns} setColumns={this.setColumns}
                getConfig={this.getConfig} searchWithinResults={this.searchWithinResults} getSelectedRows={this.getSelectedRows} refresh={this.refresh}
                setHeaderSelection={this.setHeaderSelection} getHeaderSelection={this.getHeaderSelection} getGriddleState={this.getGriddleState}
                updateInitColumns={this.updateInitColumns} getInitColumns={this.getInitColumns} removeRows={this.removeRows} getCurrentGrid={this.getCurrentGrid}
                ref={'gridSettings'} hasExternalResults={this.hasExternalResults} updateRows={this.updateRows} saveModalForm={this.saveModalForm}
                clearSelectedRows={this.clearSelectedRows} removeSelectedRows={this.removeSelectedRows}
            />
            : <span className="settings" onClick={this.toggleColumnChooser}>{this.props.settingsText} <i className="glyphicon glyphicon-cog"></i></span>
        ) : "";

        var resultContent = "";
        var pagingContent = "";
        var keys = [];
        var cols = this.getColumns();

        // If we're not loading results, fill the table with legitimate data.
        if (!this.state.isLoading) {
            //figure out which columns are displayed and show only those
            var data = this.getDataForRender(results, cols, true);

            var meta = this.props.metadataColumns;
            meta.push(this.props.childrenColumnName);

            // Grab the column keys from the first results
            keys = _.keys(_.omit(results[0], meta));

            //clean this stuff up so it's not if else all over the place.
            //console.log('this.props.columnMetadata', this.props.columnMetadata);
            resultContent = this.props.useCustomFormat
                ? (<CustomFormatContainer data= {data} columns={cols} metadataColumns={meta} className={this.props.customFormatClassName} customFormat={this.props.customFormat}/>)
                : (
                    this.props.useCustomGrid
                    ? (<this.props.customGrid columnMetadata={this.props.columnMetadata} data={data} originalData={results} columns={cols} metadataColumns={meta}
                        className={this.props.tableClassName} changeSort={this.changeSort} sortColumn={this.state.sortColumn} sortAscending={this.state.sortAscending}
                        getConfig={this.getConfig} refresh={this.refresh} setHeaderSelection={this.setHeaderSelection} getHeaderSelection={this.getHeaderSelection}
                        getSelectedRows={this.getSelectedRows} addSelectedRows={this.addSelectedRows} clearSelectedRows={this.clearSelectedRows} removeSelectedRows={this.removeSelectedRows}
                        hasExternalResults={this.hasExternalResults} removeRows={this.removeRows} updateRows={this.updateRows} saveModalForm={this.saveModalForm} saveLocalState={this.saveLocalState} ref={'gridBody'}
                    />)
                    : (<GridBody columnMetadata={this.props.columnMetadata} data={data} columns={cols} metadataColumns={meta} className={this.props.tableClassName}/>)
                );

            pagingContent = this.props.useCustomPager && this.props.customPager
                ? (<this.props.customPager next={this.nextPage} previous={this.previousPage} currentPage={this.state.page} maxPage={this.state.maxPage ? this.state.maxPage : 0}
                    setPage={this.setPage} nextText={this.props.nextText} previousText={this.props.previousText} totalResults={this.state.totalResults}
                    getConfig={this.getConfig} setPageSize={this.setPageSize} resultsPerPage={this.props.resultsPerPage} getHeaderSelection={this.getHeaderSelection} />)
                : (<GridPagination next={this.nextPage} previous={this.previousPage} currentPage={this.state.page ? this.state.page : 0} maxPage={this.state.maxPage} setPage={this.setPage} nextText={this.props.nextText} previousText={this.props.previousText}/>);
        } else {
            // Otherwise, display the loading content.
            resultContent = (<div className="loading img-responsive center-block"></div>);
        }


        //if we have neither filter or settings don't need to render this stuff
        var topSection = "";
        if (this.props.showFilter || this.props.showSettings){
            /*topSection = (
                <div className="row top-section">
                    <div className="col-xs-12">
                        {filter}
                    </div>
                    <div className="col-xs-12">
                        <div className="f-grid-top f-grid-toolbar f-grid-settings-pager clearfix">
                            <div className="col-xs-6 grid-settings">
                                {settings}
                            </div>
                            <div className="col-xs-6 grid-pager pull-right">
                                {that.props.showPager ? pagingContent : ""}
                            </div>
                        </div>
                    </div>
                </div>
            );*/
            //todo: use variable custom template
            var rowTopClassName = "f-grid-top f-grid-toolbar clearfix " + this.getConfig('id');
            var rowBottomClassName = "row f-grid-bottom f-grid-toolbar clearfix " + this.getConfig('id');
            topSection = (
                <div>
                    <div className={rowTopClassName}>
                        {filter}
                    </div>
                    <div className={rowBottomClassName}>
                        {settings}
                        {that.props.showPager ? pagingContent : ""}
                    </div>
                </div>
            );
        }

        var columnSelector = this.state.showColumnChooser && !this.props.useCustomSettings ? (
            <div className="row">
                <div className="col-md-12">
                    <GridSettings columns={keys} selectedColumns={cols} setColumns={this.setColumns} settingsText={this.props.settingsText} maxRowsText={this.props.maxRowsText}  setPageSize={this.setPageSize} resultsPerPage={this.props.resultsPerPage} allowToggleCustom={this.props.allowToggleCustom} toggleCustomFormat={this.toggleCustomFormat} useCustomFormat={this.props.useCustomFormat} enableCustomFormatText={this.props.enableCustomFormatText} columnMetadata={this.props.columnMetadata} />
                </div>
            </div>
        ) : "";

        var gridClassName = this.props.gridClassName.length > 0 ? "griddle " + this.props.gridClassName : "griddle";
        //add custom to the class name so we can style it differently
        gridClassName += this.props.useCustomFormat ? " griddle-custom" : "";


        var gridBody = this.props.useCustomFormat || this.props.customGrid
            ?       <div className="scrollable-area">{resultContent}</div>
            :       (<div className="grid-body">
                        {this.props.showTableHeading ? <table className={headerTableClassName}>
                            <GridTitle columns={cols} changeSort={this.changeSort} sortColumn={this.state.sortColumn} sortAscending={this.state.sortAscending} columnMetadata={this.props.columnMetadata}/>
                        </table> : ""}
                        {resultContent}
                        </div>);

        if (typeof this.state.results === 'undefined' || this.state.results.length == 0) {
            /*if (this.props.customNoData != null) {
                var myReturn = (<div className={gridClassName}><this.props.customNoData /></div>);

                return myReturn
            }

            var myReturn = (<div className={gridClassName}>
                    <GridNoData noDataMessage={this.props.noDataMessage} />
                </div>);
            return myReturn;*/

        }


        /*return (
            <div className={gridClassName}>
                {topSection}
                {columnSelector}
                <div className="grid-container panel">
                    {gridBody}
                </div>
            </div>
        );*/
        return (
            <div className={gridClassName}>
                {topSection}
                {columnSelector}
                {gridBody}
            </div>
        );

    },
    /**
     * get value from FCom config
     * @param name
     * @returns {*}
     */
    getConfig: function(name) {
        if (typeof this.props.config[name] !== 'undefined') {
            return this.props.config[name];
        }
        return null;
    },
    /**
     * re-render grid with same state
     */
    refresh: function() {
        var state = this.state;
        var that = this;
        if (this.hasExternalResults()) {
            // Update the state with external results
            state = this.updateStateWithExternalResults(state, function(updatedState) {
                that.setState(updatedState);
                that.setMaxPage();
            });
        } else {
            //todo: refresh for local data_mode
        }
    },
    /**
     * filter local data
     * @param data //todo: remove this params
     * @param filters
     * @returns {Griddle.props.results|*}
     */
    filterLocalData: function(data, filters) {
        var originalResults = this.props.results;
        var filteredResults = originalResults;

        _.each(filters, function(filter, key) { //key is field name
            if (filter.submit == true && typeof filter.val != 'undefined') {
                var filterVal = filter.val.toLowerCase();

                switch (filter.type) {
                    case 'text':
                        var check = {};
                        switch (filter.op) {
                            case 'contains':
                                check.contain = true;
                                break;
                            case 'start':
                                check.contain = true;
                                check.start = true;
                                break;
                            case 'end':
                                check.contain = true;
                                check.end = true;
                                break;
                            case 'equal':
                                check.contain = true;
                                check.end = true;
                                check.start = true;
                                break;
                            case 'not':
                                check.contain = false;
                                break;
                        }

                        filteredResults = _.filter(originalResults, function(row) {
                            //console.log('row', row);
                            var flag = true;
                            var rowVal = (row[key] + '').toLowerCase();
                            var firstIndex = rowVal.indexOf(filterVal);
                            var lastIndex = rowVal.lastIndexOf(filterVal);
                            _.forEach(check, function(checkValue, checkKey) {
                                switch (checkKey) {
                                    case 'contain':
                                        flag = flag && ((firstIndex !== -1) === check.contain);
                                        break;
                                    case 'start':
                                        flag = flag && firstIndex === 0;
                                        break;
                                    case 'end':
                                        flag = flag && (lastIndex + filterVal.length) === rowVal.length;
                                        break;
                                }

                                if (!flag)
                                    return flag;
                            });

                            return flag;
                        });

                        break;
                    case 'multiselect':
                        filterVal = filterVal.split(',');
                        filteredResults = _.filter(originalResults, function(row) {
                            var flag = false;
                            _.forEach(filterVal, function(value) {
                                flag = flag || (value === row[key].toLowerCase());
                            });

                            return flag;
                        });
                        break;
                    case 'number-range':
                        filteredResults = _.filter(originalResults, function(row) {
                            var flag = false;
                            var rowVal = row[key].toLowerCase();
                            var rangeVal = [];
                            switch (filter.op) {
                                case 'between':
                                    rangeVal = filterVal.split('~');
                                    if (Number(rangeVal[0]) <= rowVal && rowVal <= Number(rangeVal[1])) {
                                        flag = true;
                                    }
                                    break;
                                case 'from':
                                    if ( filterVal <= Number(rowVal)) {
                                        flag = true;
                                    }
                                    break;
                                case 'to':
                                    if (rowVal <= Number(filterVal)) {
                                        flag = true;
                                    }
                                    break;
                                case 'equal':
                                    if (rowVal == Number(filterVal) ) {
                                        flag = true;
                                    }
                                    break;
                                case 'not_in':
                                    rangeVal = filterVal.split('~');
                                    if (Number(rangeVal[0]) > rowVal || Number(rangeVal[1]) < rowVal) {
                                        flag = true;
                                    }
                                    break;
                            }

                            return flag;
                        });
                        break;
                    default:
                        break;
                }
                originalResults = filteredResults;
            }
        });

        return filteredResults;
    },
    /**
     * set filter for local data
     * @param submitFilters
     */
    setFilterLocalData: function (submitFilters) {
        var filter = JSON.stringify(submitFilters);
        var filteredResults = this.filterLocalData(this.props.results, submitFilters);

        //personalize
        var personalizeUrl = this.getLocalPersonalizeUrl();
        if (personalizeUrl) {
            $.post(personalizeUrl, {
                'do': 'grid.local.filters',
                'grid': this.getConfig('id'),
                'filters': JSON.stringify(submitFilters)
            });
        }

        this.setState({ filter: filter, filteredResults: filteredResults, totalResults: filteredResults.length, maxPage: this.getMaxPage(filteredResults) });
    },
    /**
     * get url to save personalize url
     */
    getLocalPersonalizeUrl: function () {
        var personalizeUrl = this.getConfig('personalize_url');
        var localPersonalize = this.getConfig('local_personalize');

        if (personalizeUrl && localPersonalize == true) {
            return personalizeUrl;
        }
        return '';
    },
    /**
     * post ajax to save local state
     */
    saveLocalState: function() {
        //personalize
        var personalizeUrl = this.getLocalPersonalizeUrl();
        if (personalizeUrl) {
            $.post(personalizeUrl, {
                'do': 'grid.state',
                'grid': this.getConfig('id'),
                's': this.state.sortColumn,
                'sd': this.state.sortAscending ? 'asc' : 'desc',
                'p': this.state.page + 1,
                'ps': this.props.resultsPerPage
            });
        }
    },
    /**
     * quick search in available data collection
     * @param value
     */
    searchWithinResults: function (value) {
        //console.log('searchWithinResults.value', value);
        //todo: confirm with Boris about search within available columns or all columns
        if (value) {
            var that = this,
                state = { page: 0 },
                updateAfterResultsObtained = function (updatedState) {
                    // Update the max page.
                    updatedState.maxPage = that.getMaxPage(updatedState.filteredResults);
                    updatedState.totalResults = updatedState.filteredResults.length;

                    // Set the state.
                    that.setState(updatedState);
                };

            var results = this.state.results;
            //console.log('state.filter', this.state.filter);
            if (this.state.filter != '') { //if have filter, need to filter data then search in results
                results = this.filterLocalData(null, JSON.parse(this.state.filter));
                //console.log('results before search', results);
            }

            state.filteredResults = _.filter(results,
                function (item) {
                    var arr = _.values(item);
                    for (var i = 0; i < arr.length; i++) {
                        if ((arr[i] || "").toString().toLowerCase().indexOf(value.toLowerCase()) >= 0) {
                            return true;
                        }
                    }

                    return false;
                });

            updateAfterResultsObtained(state);
        } else if (!this.hasExternalResults() && this.state.filter != '') { //empty value + already have filtered data, return to filtered data
            //console.log('state.filter', this.state.filter);
            var filters = JSON.parse(this.state.filter);
            var filteredResults = this.filterLocalData(null, filters);
            this.setState({
                filteredResults: filteredResults,
                maxPage: this.getMaxPage(filteredResults),
                totalResults: filteredResults.length
            });
        } else { //empty value + empty filtered data
            this.setState({
                filteredResults: null,
                maxPage: this.getMaxPage(null)
            });
        }
    },
    /**
     * get array selectedRows
     * @returns {*}
     */
    getSelectedRows: function() {
        return this.state.selectedRows;
    },
    /**
     * add rows to array selectedRows
     * @param rows
     */
    addSelectedRows: function(rows) {
        var selectedRows = this.getSelectedRows();
        _.forEach(rows, function(row) {
            if (typeof row.id == 'undefined') {
                console.log('griddle.addSelectedRow: row.id is undefined', row);
            }

            if (!_.findWhere(selectedRows, { id: row.id })) {
                selectedRows.push(row);
            }
        });

        this.setState({selectedRows: selectedRows});
    },
    /**
     * remove multi rows from selectedRows
     * @param rows
     */
    removeSelectedRows: function(rows) {
        var newSelectedRows = _.reject(this.state.selectedRows, function(ele) {
            return _.findWhere(rows, {id: ele.id});
        });
        this.setState({selectedRows: newSelectedRows});
    },
    /**
     * empty selectedRows
     */
    clearSelectedRows: function() {
        //console.log('clear selected rows');
        this.setState({ selectedRows: [] });
    },
    addRows: function(rows, options) {
        options = _.extend({
            silent: false
            //other options
        }, options);

        var that = this;

        /*if (this.hasExternalResults()) {
            this.props.addRowsExternal(rows, triggerAddedRowsEvent);
        } else {*/
        var results = this.state.filteredResults || this.state.results;
        _.forEach(rows, function(row) {
            if (!_.findWhere(results, {id: row.id})) {
                results.push(row);
            }
        });

        var state = { results: results };

        if (!this.state.filteredResults)  {
            state.totalResults = results.length;
            state.maxPage = this.getMaxPage(results);
        }

        this.setState(state, triggerAddedRowsEvent);
        //}

        function triggerAddedRowsEvent() {
            if (!options.silent) {
                $(that.getDOMNode()).trigger('addedRows.griddle', [rows, that]);
            }
        }
    },
    removeRows: function(rows, options) {
        options = _.extend({
            silent: false
            //other options
        }, options);

        var that = this;

        /*if (this.hasExternalResults()) {
            this.props.removeRowsExternal(rows, triggerRemovedRowsEvent);
        } else {*/
            var results = this.state.results;
            var filteredResults = this.state.filteredResults;
            var selectedRows = this.getSelectedRows();
            var deleteIds = _.pluck(rows, 'id');
            if (deleteIds) {

                function filterRow(row) {
                    return !_.contains(deleteIds, row.id);
                }

                results = _.filter(results, filterRow);

                if (selectedRows) {
                    selectedRows = _.filter(selectedRows, filterRow);
                }
                if (filteredResults) {
                    filteredResults = _.filter(filteredResults, filterRow);
                }
            }

            var state = {
                results: results,
                filteredResults: filteredResults,
                totalResults: filteredResults ? filteredResults.length : results.length,
                maxPage: this.getMaxPage(filteredResults ? filteredResults : results),
                selectedRows: selectedRows
            };

            this.setState(state, triggerRemovedRowsEvent);
        //}

        function triggerRemovedRowsEvent() {
            if (!options.silent) {
                $(that.getDOMNode()).trigger('removedRows.griddle', [rows, that]);
            }
        }
    },

    /**
     * todo: need to find solution to attach this function FComModalForm
     * Save modal form
     * @param modal
     */
    saveModalForm: function(modal) {
        var that = this,
            form = $(modal.getDOMNode()).find('form'),
            id = form.find('#id').val(),
            url = that.getConfig('edit_url'),
            hash = { oper: id ? 'edit' : 'add' };
        form.find('textarea, input, select').each(function() {
            var key = $(this).attr('id');
            var val = $(this).val();
            hash[key] = that.html2text(val);
        });
        form.validate();
        if (form.valid()) {
            if (!this.hasExternalResults()) {
                //console.log('localModeSave');
                this.updateRows([hash]);
                modal.close();
            } else if (url) {
                $.post(url, hash, function(data) {
                    if (data) {
                        that.refresh();
                        modal.close();
                    } else {
                        alert('error when save');
                        return false;
                    }
                });
            }
        } else {
            //error
            console.log('form validated fail');
            return false;
        }
    },

    /**
     * update multi rows data, almost use in data mode local
     * @param {array} data
     * @param {object} options
     * @returns {boolean}
     */
    updateRows: function(data, options) {
        //console.log('updateRows.data', data);

        options = _.extend({
            silent: false
            //other options
        }, options);

        var that = this;

        /*if (this.hasExternalResults()) {
            this.props.updateRowsExternal(rows);
        } else {*/
            var rows = this.getRows();
            var mapIds = rows.map(function (e) {
                return e.id.toString();
            });
            var updatedRows = [];

            _.each(data, function (item) {
                var index = mapIds.indexOf(item.id);
                if (index != -1) {
                    _.each(item, function (value, key) {
                        if (rows[index].hasOwnProperty(key)) {
                            rows[index][key] = value;
                        }
                    });
                    updatedRows.push(rows[index]);
                }
            });

            this.setState({ results: rows }, triggerUpdatedRowsEvent);
        //}

        function triggerUpdatedRowsEvent() {
            if (!options.silent) {
                $(that.getDOMNode()).trigger('updatedRows.griddle', [updatedRows, data, that]); //todo: event updatedRow.server.griddle???
            }
        }
    },
    getRows: function() {
        //console.log('state', this.state);
        return this.state.filteredResults || this.state.results;
    },
    /**
     * set value header selection
     * @param value
     */
    setHeaderSelection: function(value) {
        this.setState({headerSelect: value});
    },
    /**
     * get value header selection
     * @returns {string}
     */
    getHeaderSelection: function() {
        return this.state.headerSelect;
    },
    /**
     * get all state in this griddle
     * @returns {ReactCompositeComponent.state|*}
     */
    getGriddleState: function() {
        return this.state;
    },
    /**
     * update array init columns, almost use for re-order columns
     * @param columns
     */
    updateInitColumns: function(columns) {
        var selectedColumns = this.getColumns();
        var newSelectedColumns = [];

        //update selected columns
        _.forEach(columns, function(item) {
            if (_.contains(selectedColumns, item)) {
                newSelectedColumns.push(item);
            }
        });

        //this.setState({ initColumns: [], filteredColumns: [] });
        this.setState({ initColumns: columns, filteredColumns: newSelectedColumns });
    },
    /**
     * return array init columns
     * @returns {*|Griddle.props.initColumns}
     */
    getInitColumns: function() {
        return this.state.initColumns;
    },
    getCurrentGrid: function() {
        return this;
    },
    /**
     * trigger global callback function base on name
     * @param name
     */
    triggerCallback: function(name) {
        var that = this;
        var callbacks = this.getConfig('callbacks');
        if (callbacks && typeof callbacks[name] !== 'undefined') {
            if (callbacks[name] instanceof Array) {
                _.forEach(callbacks[name], function(funcName) {
                    callGlobalFunction(funcName);
                });
            } else {
                var callbackFuncName = callbacks[name];
                callGlobalFunction(callbackFuncName);
            }
        }

        function callGlobalFunction(funcName) {
            if (typeof window[funcName] === 'function') {
                console.log('triggerCallback:' + name);
                return window[funcName](that, name);
            } else {
                console.log('DEBUG: cannot find call back ' + funcName + ' for name ' + name);
            }
        }
    },
    /**
     * get child component by React refs
     * @param {string} childName (body | settings | rows)
     * @returns {*}
     */
    getChildComponent: function(childName) {
        var child = null;
        var refs = this.refs;
        if (refs) {
            switch (childName) {
                case 'gridBody':
                case 'body':
                    child = refs.gridBody;
                    break;
                case 'gridSettings':
                case 'settings':
                    child = refs.gridSettings;
                    break;
                case 'rows':
                    child = refs.gridBody.refs;
                    break;
            }
        }

        return child;
    },
    /**
     * get row component
     * @param id
     * @returns {*}
     */
    getRowComponent: function(id) { //todo: do we need to merge this with this.getRows()
        var rows = this.getChildComponent('rows');
        if (rows && typeof rows['row' + id] !== 'undefined') {
            return rows['row' + id];
        }
        return null;
    }
});

//module.exports = Griddle;
return Griddle;
});
