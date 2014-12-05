/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!griddle', 'backbone', 'underscore', 'bootstrap'],
function(React, $, Griddle, Backbone) {

    /**
     *
     * @param config
     * @constructor
     */
    FCom.Griddle = function(config) {
        var page_size_options = config.page_size_options;
        var totalResults = config.data.state.c;

        var FComGriddleComponent = React.createClass({
            getDefaultProps: function(){
                return {
                    "resultsPerPage": config.state.ps
                }
            },
            render: function() {
                var content = <Griddle
                    tableClassName="fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable"
                    getExternalResults={FComDataMethod} resultsPerPage={this.props.resultsPerPage}
                    useCustomPager="true" customPager={FComGriddlePager}
                    showFilter={true} useCustomFilter="true" customFilter={FComGriddleFilter} filterPlaceholderText={"Quick Search"}
                    showSettings={true}
                />;

                return (
                    <div>{content}</div>
                );
            }
        });

        /**
         *
         * @param filterString
         * @param sortColumn
         * @param sortAscending
         * @param page
         * @param pageSize
         * @param callback
         * @constructor
         */
        var FComDataMethod = function(filterString, sortColumn, sortAscending, page, pageSize, callback) {
            $.ajax({
                url: config.data_url+'?gridId='+config.id+'&p='+(page+1)+'&ps='+pageSize+'&s='+sortColumn+'&sd='+sortAscending+'&filters='+filterString,
                dataType: 'json',
                type: 'GET',
                data: {},
                success: function(response) {
                    var data = {
                        results: response[1],
                        totalResults: totalResults
                    };

                    callback(data);
                },
                error: function(xhr, status, err) {
                    console.error(this.props.url, status, err.toString());
                }
            });
        };

        /**
         *
         */
        var FComGriddlePager = React.createClass({
            getDefaultProps: function(){
                return {
                    "maxPage": 0,
                    "nextText": "",
                    "previousText": "",
                    "currentPage": 0
                }
            },
            pageChange: function(event) {
                event.preventDefault();
                this.props.setPage(parseInt(event.target.getAttribute("data-value")));
            },
            pageFirst: function(event) {
                event.preventDefault();
                this.props.setPage(parseInt(0));
            },
            pageNext: function(event) {
                event.preventDefault();
                this.props.next();
            },
            pagePrevious: function(event) {
                event.preventDefault();
                this.props.previous();
            },
            pageLast: function(event) {
                event.preventDefault();
                this.props.setPage(parseInt(this.props.maxPage) - 1);
            },
            setPageSize: function(event){
                event.preventDefault();
                var value = parseInt(event.target.getAttribute("data-value"));

                document.getElementById(config.id).innerHTML = '';
                config.state.ps = value;

                React.render(
                    <FComGriddleComponent resultsPerPage={config.state.ps} />, document.getElementById(config.id)
                );
            },
            render: function() {
                var first = "";
                var previous = "";
                var next = "";
                var last = "";

                first = <li className="first"><a href="#" className="js-change-url" onClick={this.pageFirst}>«</a></li>;
                previous = <li className="prev"><a href="#" className="js-change-url" onClick={this.pagePrevious}>‹</a></li>;
                next = <li className="next"><a class="js-change-url" href="#" onClick={this.pageNext}>›</a></li>;
                last = <li class="last"><a class="js-change-url" href="#" onClick={this.pageLast}>{this.props.maxPage} »</a></li>;

                var options = [];

                var startIndex = Math.max(this.props.currentPage - 5, 0);
                var endIndex = Math.min(startIndex + 11, this.props.maxPage);
                if (this.props.maxPage >= 11 && (endIndex - startIndex) <= 10) {
                    startIndex = endIndex - 11;
                }

                for (var i = startIndex; i < endIndex ; i++){
                    var selected = this.props.currentPage == i ? "page active" : "page";
                    options.push(<li className={selected}><a href="#" data-value={i} onClick={this.pageChange} class="js-change-url">{i + 1}</a></li>);
                }

                var pageSizeHtml = [];
                for (var j = 0; j < page_size_options.length; j++) {
                    var selected = page_size_options[j] == config.state.ps ? "active" : "";
                    pageSizeHtml.push(<li className={selected}><a href="#" data-value={page_size_options[j]} onClick={this.setPageSize} className="js-change-url page-size">{page_size_options[j]}</a></li>);
                }

                return (

                    <div className="col-sm-6 text-right pagination">
                        <span className="f-grid-pagination">{totalResults} record(s)</span>

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

        var FComGriddleFilter = React.createClass({
            getDefaultProps: function(){
                return {
                    "placeholderText": "Quick Search",
                    "operations": [
                        {
                            "operation": "contains",
                            "display": "contains"
                        },
                        {
                            "operation": "not",
                            "display": "does not contain"
                        },
                        {
                            "operation": "equal",
                            "display": "is equal to"
                        },
                        {
                            "operation": "start",
                            "display": "start with"
                        },
                        {
                            "operation": "end",
                            "display": "end with"
                        }
                    ],
                    "filters": [
                        // Example filters, remove later
                        {
                            "column": "title",
                            "display": "Title"
                        },
                        {
                            "column": "name",
                            "display": "Name"
                        }
                    ]
                }
            },
            handleChange: function(event){
                this.props.changeFilter(event.target.value);
            },
            toggleDropdown: function(event) {
                event.preventDefault();

                var selected = event.target;
                var parent = $(selected).parent();

                if (!$(parent).hasClass('open')) {
                    $('<div class="dropdown-backdrop"/>').insertAfter($(selected)).on('click', this.clearMenus);
                }

                $(parent).toggleClass('open').trigger('shown.bs.dropdown');
            },
            clearMenus: function() {
                $('.dropdown-backdrop').remove()

                $('.dropdown-toggle').each(function (e) {
                    var parent = $(this).parent();
                    if (!$(parent).hasClass('open')) {
                        return;
                    }
                    $(parent).trigger(e = $.Event('hide.bs.dropdown'));

                    if (e.isDefaultPrevented()) {
                        return;
                    }
                    $(parent).removeClass('open').trigger('hidden.bs.dropdown');
                })
            },
            render: function() {
                var quickSearch = <input type="text" className="f-grid-quick-search form-control" placeholder={this.props.placeholderText} onChange={this.handleChange} />;

                var filterOperations = [];
                for (var i=0; i<this.props.operations.length; i++) {
                    var op = this.props.operations[i];
                    filterOperations.push(<li><a href="#" data-id={op.operation} className="filter_op">{op.display}</a></li>);
                }

                var filterOptions = [];
                var filters = [];

                for (var i=0; i<this.props.filters.length; i++) {
                    var filter = this.props.filters[i];
                    filterOptions.push(
                        <li data-id="title" class="dd-item dd3-item">
                            <div class="icon-ellipsis-vertical dd-handle dd3-handle"></div>
                            <div class="dd3-content">
                                <label><input type="checkbox" checked="" datid={filter.column} className="showhide_column" />{filter.display}</label>
                            </div>
                        </li>
                    );

                    filters.push(
                        <div className="btn-group f-grid-filter dropdown">
                            <button className="btn dropdown-toggle filter-text-main" onClick={this.toggleDropdown}>
                                <span className="f-grid-filter-field">{filter.display}</span>: <span className="f-grid-filter-value">All</span> <span className="caret"></span>
                            </button>

                            <ul className="dropdown-menu filter-box">
                                <li>
                                    <div className="input-group">
                                        <div className="input-group-btn dropdown">
                                            <button className="btn btn-default dropdown-toggle filter-text-sub" onClick={this.toggleDropdown}>
                                                {this.props.operations[0].display} <span className="caret"></span>
                                            </button>

                                            <ul className="dropdown-menu filter-sub">
                                                {filterOperations}
                                            </ul>
                                        </div>

                                        <input type="text" value="" className="form-control" />
                                        <div className="input-group-btn">
                                            <button className="btn btn-primary update" type="button">Update</button>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <abbr className="select2-search-choice-close"></abbr>
                        </div>
                    );
                }

                return (
                    <div className="f-grid-top f-grid-toolbar clearfix">
                        <div className="f-col-filters-selection pull-left">
                            {quickSearch}
                            <span className="dropdown">
                                <button className="btn dropdown-toggle showhide_columns" onClick={this.toggleDropdown}>
                                    Filters <span className="caret"></span>
                                </button>
                                <ul className="dd-list dropdown-menu filters ui-sortable">{filterOptions}</ul>
                            </span>
                        </div>

                        <span className="f-filter-btns">
                            {filters}
                        </span>
                    </div>
                );
            }
        });

        React.render(
            <FComGriddleComponent resultsPerPage={config.state.ps} />, document.getElementById(config.id)
        );

    };
});