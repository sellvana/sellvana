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
                var content = <Griddle getExternalResults={FComDataMethod} showFilter={true} showSettings={true} resultsPerPage={this.props.resultsPerPage}
                    tableClassName="fcom-htmlgrid__grid data-table-column-filter table table-bordered table-striped dataTable"
                    useCustomPager="true" customPager={FComGriddlePager}
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

                first = <li className="first"><a href="#" className="js-change-url" onClick={this.pageFirst}>«</a></li>
                previous = <li className="prev"><a href="#" className="js-change-url" onClick={this.pagePrevious}>‹</a></li>
                next = <li className="next"><a class="js-change-url" href="#" onClick={this.pageNext}>›</a></li>
                last = <li class="last"><a class="js-change-url" href="#" onClick={this.pageLast}>{this.props.maxPage} »</a></li>

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

        React.render(
            <FComGriddleComponent resultsPerPage={config.state.ps} />, document.getElementById(config.id)
        );

    };
});