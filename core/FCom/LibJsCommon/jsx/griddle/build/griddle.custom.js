/** @jsx React.DOM */

/*
   Griddle - Simple Grid Component for React
   https://github.com/DynamicTyped/Griddle
   Copyright (c) 2014 Ryan Lanciaux | DynamicTyped

   See License / Disclaimer https://raw.githubusercontent.com/DynamicTyped/Griddle/master/LICENSE
*/
define(['underscore', 'react', 'griddle.gridNoData'], function(_, React, GridNoData) {
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
var Griddle = React.createClass({displayName: "Griddle",
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
            "initPage": 0 //begin with 0 page
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

        // Obtain the results
        this.props.getExternalResults(filter, sortColumn, sortAscending, page, this.props.resultsPerPage, callback);
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
                isInit: false
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
            this.setState({ maxPage: maxPage, filteredColumns: this.props.columns, initColumns: this.props.initColumns });
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
                that.setState(state);
            }
        }
    },
    getColumns: function(){
        var that = this;

        //if we don't have any data don't mess with this
        if (this.state.results === undefined || this.state.results.length == 0){ return [];}

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
                sortAscending: 'asc'
            };

        // If this is the same column, reverse the sort.
        if (this.state.sortColumn == sort) {
            state.sortAscending = (this.state.sortAscending == 'asc') ? 'desc' : ((this.state.sortAscending == 'desc') ? '' : 'asc');
        } else {
            state.sortAscending = "asc";
        }

        if (this.hasExternalResults()) {
            this.updateStateWithExternalResults(state, function(updatedState) {
                that.setState(updatedState);
            });
        } else {
            this.setState(state);
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
            filteredColumns: [],
            filter: "",
            sortColumn: this.props.sortColumn,
            sortAscending: this.props.sortAscending,
            showColumnChooser: false,
            isLoading: false,
            //fcom custom
            initColumns: [], //init columns include all hide columns
            isInit: true,
            selectedRows: [],
            headerSelect: 'show_all' //select value in header dropdown
        };

        // If we need to get external results, grab the results.
        if (!this.hasExternalResults()) {
            state.results = this.props.results;
            state.totalResults = this.props.results.length;
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
    },

    getDataForRender: function(data, cols, pageList){
        var that = this;
        if (!this.hasExternalResults()) {
            //get the correct page size
            if(this.state.sortColumn != "" || this.props.initialSort != ""){
                data = _.sortBy(data, function(item){
                    return item[that.state.sortColumn||that.props.initialSort];
                });

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

        var headerTableClassName = this.props.tableClassName + " table-header";

        //figure out if we want to show the filter section
        var filter = this.props.showFilter ?
            (
                this.props.useCustomFilter
                ? React.createElement(this.props.customFilter, {changeFilter: this.setFilter, customFilter: this.props.customFilter, getConfig: this.getConfig})
                : React.createElement(GridFilter, {changeFilter: this.setFilter, placeholderText: this.props.filterPlaceholderText})
            ) : "";
        var settings = this.props.showSettings ?
        (
            this.props.useCustomSettings
            ? React.createElement(this.props.customSettings, {columnMetadata: this.props.columnMetadata, selectedColumns: this.getColumns, setColumns: this.setColumns, 
                getConfig: this.getConfig, searchWithinResults: this.searchWithinResults, getSelectedRows: this.getSelectedRows, refresh: this.refresh, 
                setHeaderSelection: this.setHeaderSelection, getHeaderSelection: this.getHeaderSelection, getGriddleState: this.getGriddleState, 
                updateInitColumns: this.updateInitColumns, getInitColumns: this.getInitColumns, getCurrentGrid: this.getCurrentGrid})
            : React.createElement("span", {className: "settings", onClick: this.toggleColumnChooser}, this.props.settingsText, " ", React.createElement("i", {className: "glyphicon glyphicon-cog"}))
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
                ? (React.createElement(CustomFormatContainer, {data: data, columns: cols, metadataColumns: meta, className: this.props.customFormatClassName, customFormat: this.props.customFormat}))
                : (
                    this.props.useCustomGrid
                    ? (React.createElement(this.props.customGrid, {columnMetadata: this.props.columnMetadata, data: data, originalData: results, columns: cols, metadataColumns: meta, 
                        className: this.props.tableClassName, changeSort: this.changeSort, sortColumn: this.state.sortColumn, sortAscending: this.state.sortAscending, 
                        getConfig: this.getConfig, refresh: this.refresh, setHeaderSelection: this.setHeaderSelection, getHeaderSelection: this.getHeaderSelection, 
                        getSelectedRows: this.getSelectedRows, addSelectedRows: this.addSelectedRows, clearSelectedRows: this.clearSelectedRows, removeSelectedRows: this.removeSelectedRows, 
                        hasExternalResults: this.hasExternalResults}
                    ))
                    : (React.createElement(GridBody, {columnMetadata: this.props.columnMetadata, data: data, columns: cols, metadataColumns: meta, className: this.props.tableClassName}))
                );

            pagingContent = this.props.useCustomPager && this.props.customPager
                ? (React.createElement(this.props.customPager, {next: this.nextPage, previous: this.previousPage, currentPage: this.state.page, maxPage: this.state.maxPage ? this.state.maxPage : 0, 
                    setPage: this.setPage, nextText: this.props.nextText, previousText: this.props.previousText, totalResults: this.state.totalResults, 
                    getConfig: this.getConfig, setPageSize: this.setPageSize, resultsPerPage: this.props.resultsPerPage, getHeaderSelection: this.getHeaderSelection}))
                : (React.createElement(GridPagination, {next: this.nextPage, previous: this.previousPage, currentPage: this.state.page ? this.state.page : 0, maxPage: this.state.maxPage, setPage: this.setPage, nextText: this.props.nextText, previousText: this.props.previousText}));
        } else {
            // Otherwise, display the loading content.
            resultContent = (React.createElement("div", {className: "loading img-responsive center-block"}));
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
                React.createElement("div", null, 
                    React.createElement("div", {className: rowTopClassName}, 
                        filter
                    ), 
                    React.createElement("div", {className: rowBottomClassName}, 
                        settings, 
                        that.props.showPager ? pagingContent : ""
                    )
                )
            );
        }

        var columnSelector = this.state.showColumnChooser && !this.props.useCustomSettings ? (
            React.createElement("div", {className: "row"}, 
                React.createElement("div", {className: "col-md-12"}, 
                    React.createElement(GridSettings, {columns: keys, selectedColumns: cols, setColumns: this.setColumns, settingsText: this.props.settingsText, maxRowsText: this.props.maxRowsText, setPageSize: this.setPageSize, resultsPerPage: this.props.resultsPerPage, allowToggleCustom: this.props.allowToggleCustom, toggleCustomFormat: this.toggleCustomFormat, useCustomFormat: this.props.useCustomFormat, enableCustomFormatText: this.props.enableCustomFormatText, columnMetadata: this.props.columnMetadata})
                )
            )
        ) : "";

        var gridClassName = this.props.gridClassName.length > 0 ? "griddle " + this.props.gridClassName : "griddle";
        //add custom to the class name so we can style it differently
        gridClassName += this.props.useCustomFormat ? " griddle-custom" : "";


        var gridBody = this.props.useCustomFormat || this.props.customGrid
            ?       React.createElement("div", {className: "scrollable-area"}, resultContent)
            :       (React.createElement("div", {className: "grid-body"}, 
                        this.props.showTableHeading ? React.createElement("table", {className: headerTableClassName}, 
                            React.createElement(GridTitle, {columns: cols, changeSort: this.changeSort, sortColumn: this.state.sortColumn, sortAscending: this.state.sortAscending, columnMetadata: this.props.columnMetadata})
                        ) : "", 
                        resultContent
                        ));

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
            React.createElement("div", {className: gridClassName}, 
                topSection, 
                columnSelector, 
                gridBody
            )
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
        }
    },
    /**
     * quick search in available data collection
     * @param value
     */
    searchWithinResults: function (value) {
        //todo: confirm with Boris about search within available columns or all columns
        if (value) {
            var that = this,
                state = { page: 0 },
                updateAfterResultsObtained = function (updatedState) {
                    // Update the max page.
                    updatedState.maxPage = that.getMaxPage(updatedState.filteredResults);

                    // Set the state.
                    that.setState(updatedState);
                };

            state.filteredResults = _.filter(this.state.results,
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
        } else {
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
        this.setState({selectedRows: []});
    },
    addRows: function(rows) {
        var results = this.state.filteredResults || this.state.results;
        results.push.apply(results, rows);
        this.setState({ results: results, filteredResults: results });
    },
    getRows: function() {
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

        this.setState({
            initColumns: columns,
            filteredColumns: newSelectedColumns
        });
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
    }
});

//module.exports = Griddle;
return Griddle;
});
