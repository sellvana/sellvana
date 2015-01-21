/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'jsx!griddle.fcomGridBody', 'jsx!griddle.fcomGridFilter', 'jsx!griddle', 'backbone', 'bootstrap', 'jsx!fcom.components'],
function (_, React, $, FComGridBody, FComFilter, Griddle, Backbone, Components) {

    var dataUrl,
        gridId,
        pageSize,
        pageSizeOptions,
        initColumns;

    var FComGriddleComponent = React.createClass({
        getDefaultProps: function () {
            return {
                "config": {
                    page_size_options: 10
                },
                "tableClassName": 'fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable'
            }
        },
        componentWillMount: function () {
            this.initColumn();
            //todo: need change way to get right info
            dataUrl = this.props.config.data_url;
            gridId = this.props.config.id;
            pageSize = this.props.config.state.ps;
            pageSizeOptions = this.props.config.page_size_options;
            initColumns = this.getColumn();
        },
        initColumn: function () {
            var columnsConfig = this.props.config.columns;

            var all = _.pluck(columnsConfig, 'name');
            var hide = _.pluck(_.where(columnsConfig, {hidden: true}), 'name');
            var show = _.difference(all, hide);

            this.props.columns = {all: all, show: show, hide: hide};
            //console.log('this.props.columns', this.props.columns);
            this.props.columnMetadata = columnsConfig;
        },
        getColumn: function (type) {
            switch (type) {
                case 'hide':
                    return this.props.columns.hide;
                    break;
                case 'show':
                    return this.props.columns.show;
                    break;
                default:
                    return this.props.columns.all;
                    break;
            }
        },
        render: function () {
            //console.log('config', this.props.config);

            return (
                <Griddle showTableHeading={false} tableClassName={this.props.tableClassName}
                    config={this.props.config}
                    columns={this.getColumn('show')} columnMetadata={this.props.columnMetadata}
                    useCustomGrid={true} customGrid={FComGridBody}
                    getExternalResults={FComDataMethod} resultsPerPage={pageSize}
                    useCustomPager="true" customPager={FComPager}
                    showSettings={true} useCustomSettings={true} customSettings={FComSettings}
                    showFilter={true} useCustomFilter="true" customFilter={FComFilter} filterPlaceholderText={"Quick Search"}
                />
            );
        }
    });

    /**
     * callback to get data from external results
     * @param filterString
     * @param sortColumn
     * @param sortAscending
     * @param page
     * @param pageSize
     * @param callback
     * @constructor
     */
    var FComDataMethod = function (filterString, sortColumn, sortAscending, page, pageSize, callback) {
        $.ajax({
            url: dataUrl + '?gridId=' + gridId + '&p=' + (page + 1) + '&ps=' + pageSize + '&s=' + sortColumn + '&sd=' + sortAscending + '&filters=' + (filterString ? filterString : '{}'),
            dataType: 'json',
            type: 'GET',
            data: {},
            success: function (response) {
                var data = {
                    results: response[1],
                    totalResults: response[0].c
                };

                callback(data);
            },
            error: function (xhr, status, err) {
                //console.error(this.props.url, status, err.toString());
            }
        });
    };

    /**
     * FCom Pager component
     */
    var FComPager = React.createClass({
        getDefaultProps: function () {
            return {
                "maxPage": 0,
                "nextText": "",
                "previousText": "",
                "currentPage": 0
            }
        },
        pageChange: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(event.target.getAttribute("data-value")));
        },
        pageFirst: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(0));
        },
        pageNext: function (event) {
            event.preventDefault();
            this.props.next();
        },
        pagePrevious: function (event) {
            event.preventDefault();
            this.props.previous();
        },
        pageLast: function (event) {
            event.preventDefault();
            this.props.setPage(parseInt(this.props.maxPage) - 1);
        },
        setPageSize: function (event) {
            event.preventDefault();
            var value = event.target.dataset.value;
            pageSize = parseInt(value);

            this.props.setPageSize(parseInt(value));
            this.props.setPage(0);
        },
        render: function () {
            var first = "";
            var previous = "";
            var next = "";
            var last = "";

            first = <li className="first">
                <a href="#" className="js-change-url" onClick={this.pageFirst}>«</a>
            </li>;
            previous = <li className="prev">
                <a href="#" className="js-change-url" onClick={this.pagePrevious}>‹</a>
            </li>;
            next = <li className="next">
                <a className="js-change-url" href="#" onClick={this.pageNext}>›</a>
            </li>;
            last = <li className="last">
                <a className="js-change-url" href="#" onClick={this.pageLast}>{this.props.maxPage} »</a>
            </li>;

            var options = [];

            var startIndex = Math.max(this.props.currentPage - 5, 0);
            var endIndex = Math.min(startIndex + 11, this.props.maxPage);
            if (this.props.maxPage >= 11 && (endIndex - startIndex) <= 10) {
                startIndex = endIndex - 11;
            }

            for (var i = startIndex; i < endIndex; i++) {
                var selected = this.props.currentPage == i ? "page active" : "page";
                options.push(<li className={selected}>
                    <a href="#" data-value={i} onClick={this.pageChange} className="js-change-url">{i + 1}</a>
                </li>);
            }

            var pageSizeHtml = [];
            for (var j = 0; j < pageSizeOptions.length; j++) {
                var selected = pageSizeOptions[j] == pageSize ? "active" : "";
                pageSizeHtml.push(<li className={selected}>
                    <a href="#" data-value={pageSizeOptions[j]} onClick={this.setPageSize} className="js-change-url page-size">{pageSizeOptions[j]}</a>
                </li>);
            }

            return (
                <div className="col-sm-6 text-right pagination" style={{ margin: "0" }}>
                    <span className="f-grid-pagination">{this.props.totalResults} record(s)</span>
                    <ul className="pagination pagination-sm pagination-griddle pagesize">
                        {pageSizeHtml}
                    </ul>
                    <ul className="pagination pagination-sm pagination-griddle page">
                        {first}
                        {previous}
                        {options}
                        {next}
                        {last}
                    </ul>
                </div>
            )
        }
    });

    var FComSettings = React.createClass({
        getDefaultProps: function() {
            return {
                "className": "",
                "getConfig": null,
                "selectedColumns": []

            }
        },
        toggleColumn: function(event) {
            var selectedColumns = this.props.selectedColumns;
            if(event.target.checked == true && _.contains(selectedColumns, event.target.dataset.name) == false){
                selectedColumns.push(event.target.dataset.name);
                var diff = _.difference(initColumns, selectedColumns);
                if (diff.length > 0) {
                    selectedColumns = initColumns;
                    for(var i=0; i < diff.length; i++) {
                        selectedColumns = _.without(selectedColumns, diff[i]);
                    }
                    this.props.setColumns(selectedColumns);
                } else {
                    this.props.setColumns(initColumns);
                }
            } else {
                /* redraw with the selected initColumns minus the one just unchecked */
                this.props.setColumns(_.without(selectedColumns, event.target.dataset.name));
            }

            //don't close dropdown after toggle column
            $(event.target).parents('div.dropdown').addClass('open');
        },
        quickSearch: function(event) {
            this.props.searchWithinResults(event.target.value);
        },
        render: function () {
            var that = this;
            var id = this.props.getConfig('id');

            var options = _.map(initColumns, function(column) {
                if (column == '0') {
                    return false;
                }

                var checked = _.contains(that.props.selectedColumns, column);
                var colInfo = _.findWhere(that.props.columnMetadata, {name: column});
                return (
                    <li data-id={column} className="dd-item dd3-item">
                        <div className="icon-ellipsis-vertical dd-handle dd3-handle"></div>
                        <div className="dd3-content">
                            <label>
                                <input type="checkbox" checked={checked} data-id={column} data-name={column} className="showhide_column" onChange={that.toggleColumn}/> {colInfo ?  colInfo.label : column}
                            </label>
                        </div>
                    </li>
                )
            });

            //quick search
            var quickSearch = <input type="text" className="f-grid-quick-search form-control" placeholder="Search within results" id={id + '-quick-search'} onChange={this.quickSearch} />;

            var disabledClass = !this.props.getSelectedRows().length ? ' disabled' : '';

            return (
                <div className="col-sm-6">
                    {quickSearch}
                    <div className="dropdown dd dd-nestable columns-span" style={{ display: 'inline' }}>
                        <a href="#" className="btn dropdown-toggle showhide_columns" data-toggle="dropdown">
                            Columns <b className="caret"></b>
                        </a>
                        <ol className="dd-list dropdown-menu columns ui-sortable">
                            {options}
                        </ol>
                    </div>
                    <a className={"btn grid-mass-edit btn-success" + disabledClass} role="button" href="#" >Edit</a>
                    <button className={"btn grid-mass-delete btn-danger" + disabledClass} type="button">Delete</button>
                </div>
            )
        }
    });

    return FComGriddleComponent;
});