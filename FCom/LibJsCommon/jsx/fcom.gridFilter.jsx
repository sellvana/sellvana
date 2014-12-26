/** @jsx React.DOM */

define(['underscore', 'react'], function (_, React) {
    var FComFilter = React.createClass({
        getInitialState: function() {
            var that = this;
            var filters = this.props.getConfig('filters').map(function(f, index) {
                f.show = true;
                f.label = that.getFieldName(f.field);
                return f;
            });

            return {
                filters: filters, //filters as props
                stateFilters: {} //temporary filters state
            }
        },
        getDefaultProps: function() {
            return {
                "placeholderText": "Quick Search"
            }
        },
        componentDidMount: function() {
            var that = this;
            var filterContainer = $('.f-filter-btns');

            //fix for grid filter
            $(document).
                on('click', '.f-grid-filter input', function (e) {
                    e.stopPropagation();
                }).on('keyup', '.f-grid-filter input', function (e) {
                    var evt = e || window.event;
                    var charCode = evt.keyCode || evt.which;
                    if (charCode === 13) {
                        that.filter(e);
                    }
                }).on('click', '.f-grid-filter button.filter-text-sub', function (e) {
                    that.keepShowDropDown(this);
                }).on('click', 'ul.filter-sub a.filter_op', function (e) {
                    var operator = $(e.target);
                    var text = operator.html();
                    $(this).parents('div.dropdown').each(function() {
                        if ($(this).hasClass('input-group-btn')) {
                            $(this).removeClass('open');
                        }
                    });
                    e.preventDefault();
                    e.stopPropagation();
                }).on('show.bs.dropdown', 'div.dropdown.f-grid-filter', function() { //fix dropdown menu is hidden when reach right side windows
                    var ulEle = $(this).find('ul.dropdown-menu:first');
                    if ($(this).offset().left + ulEle.width() > $(window).width()) {
                        ulEle.css({'right' : 0, 'left' : 'auto'});
                    }
                })
            ;

            //init datepicker + daterangepicker
            filterContainer.find('.filter-date-range').daterangepicker({ format: "YYYY-MM-DD" });
            filterContainer.find(".datepicker").datetimepicker({ pickTime: false });

            $('.daterangepicker').on('click', function (ev) {
                    ev.stopPropagation();
                    ev.preventDefault();
                    return false;
                }
            );
        },
        capitaliseFirstLetter: function(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        initStateFilter: function(field) {
            var filter = _.findWhere(this.state.filters, {field: field});
            return {
                field: field,
                opLabel: '',
                op: '',
                val: '',
                range: true,
                type: filter.type,
                submit: false
            }
        },
        setStateFilter: function(field, key, value) {
            if (typeof field === 'undefined' ||  typeof key === 'undefined') {
                return;
            }

            var stateFilters = this.state.stateFilters;
            if (typeof stateFilters[field] === 'undefined') {
                stateFilters[field] = this.initStateFilter(field);
            }
            stateFilters[field][key] = value;

            console.log('setStateFilter', stateFilters);
            this.setState({stateFilters: stateFilters});
            //todo: minimize re-render function when update stateFilters
        },
        /**
         * keep parents dropdown still be shown
         * @param ele
         */
        keepShowDropDown: function(ele) {
            $(ele).parents('div.dropdown').addClass('open');
        },
        handleChange: function(event) {
            this.props.changeFilter(event.target.value);
        },
        /**
         * get display name from columns metadata
         * @param field
         * @returns {*}
         */
        getFieldName: function(field) {
            var row = _.findWhere(this.props.getConfig('columns'), {name: field});
            return row ? row.label : field;
        },
        toggleFilter: function(event) {// show/hide filter block
            var field = event.target.dataset.field;
            var checked = event.target.checked;
            var filters = this.state.filters.map(function(f) {
                if (f.field == field) {
                    f.show = (checked == true);
                }
                return f;
            });

            this.setState({filters: filters});
            this.keepShowDropDown(event.target);
        },
        prepareFilter: function (event) {
            //add submit filter
            var stateFilters = this.state.stateFilters;
            var submitFilters = {};
            for (var f in stateFilters) {
                if (typeof stateFilters[f] !== 'undefined' && stateFilters[f].submit == true) {
                    submitFilters[f] = stateFilters[f];
                }
            }
            return submitFilters;
        },
        /**
         * filter data
         * @param event
         */
        filter: function (event) {
            //prepare data
            var field = event.target.dataset.field;
            this.setStateFilter(field, 'submit', !(event.target.dataset.clear == '1'));

            var submitFilters = this.prepareFilter(event);
            //console.log('submitFilters', submitFilters);
            this.props.changeFilter(JSON.stringify(submitFilters));
        },
        render: function() {
            //console.log('begin render filters');
            var that = this;
            var id = this.props.getConfig('id');
            var filters = this.state.filters;

            //quick search
            var quickSearch = <input type="text" className="f-grid-quick-search form-control" placeholder={this.props.placeholderText} onChange={this.handleChange} id={id + '-quick-search'} />;

            var filterSettingNodes = filters.map(function(f, index) {
                return (
                    <li data-filter-id={f.field} className="dd-item dd3-item">
                        <div className="icon-ellipsis-vertical dd-handle dd3-handle"></div>
                        <div className="dd3-content">
                            <label>
                                <input className="showhide_column" data-field={f.field} onChange={that.toggleFilter} type="checkbox" defaultChecked={f.show ? 'checked' : ''} />
                                {f.label}
                            </label>
                        </div>
                    </li>
                );
            });

            var filterSettings = (
                <div className={id + ' dropdown'} style={{"display" : "inline-block"}}>
                    <a data-toggle="dropdown" className="btn dropdown-toggle showhide_columns">
                        Filters <b className="caret"></b>
                    </a>
                    <ul className={id + " dd-list dropdown-menu filters ui-sortable"}>
                        {filterSettingNodes}
                    </ul>
                </div>
            );

            var filterNodes = filters.map(function(f, index) {
                if (!f.show) {
                    return false;
                }
                return (<FComFilterNodeContainer filter={f} setFilter={that.filter} setStateFilter={that.setStateFilter} stateFilters={that.state.stateFilters} capitaliseFirstLetter={that.capitaliseFirstLetter} />);
            });

            //console.log('end render filters');

            return (
                <div>
                    <div className="f-col-filters-selection pull-left">
                        {quickSearch}
                        {filterSettings}
                    </div>
                    <div className={id + " f-filter-btns"}>
                        {filterNodes}
                    </div>
                </div>
            );
        }
    });

    var FComFilterNodeContainer = React.createClass({
        getDefaultProps: function() {
            return {
                'filter': {}
            };
        },
        render: function() {
            if (typeof this.props.filter === 'undefined') {
                return false;
            }

            var filter = this.props.filter;
            var node = null;

            switch (filter.type) {
                case 'text':
                    node = <FComFilterText {...this.props} />;
                    break;
                case 'date-range':
                    node = <FComFilterDateRange {...this.props} />;
                    break;
                case 'number-range':
                    node = <FComFilterNumberRange {...this.props} />;
                    break;
                case 'multiselect':
                    node = <FComFilterMultiSelect {...this.props} />;
                    break;
                case 'select':
                    node = <FComFilterSelect {...this.props} />;
                    break;
            }

            return node;
        }
    });

    var FComFilterText = React.createClass({
        getInitialState: function () {
            var filter = this.props.filter;
            if (typeof this.props.stateFilters[filter.field] === 'undefined') {
                var operation = _.findWhere(this.getOperations(), {'default': true});
                this.props.setStateFilter(filter.field, 'op', operation.op);
                filter.op = operation.op;
                filter.opLabel = this.props.capitaliseFirstLetter(operation.name);
                filter.val = '';
                filter.submit = false;
            } else {
                var data = this.props.stateFilters[filter.field];
                filter.op = data.op;
                filter.opLabel = data.opLabel;
                filter.val = data.val;
                filter.submit = data.submit;
            }

            return { filter: filter };
        },
        getOperations: function () {
            return [
                { op: 'contains', name: 'contains', 'default': true },
                { op: 'not', name: 'does not contain' },
                { op: 'equal', name: 'is equal to' },
                { op: 'start', name: 'start with' },
                { op: 'end', name: 'end with' }
            ];
        },
        setStateOperation: function(event) {
            var filter = this.state.filter;
            var operation = _.findWhere(this.getOperations(), {op: event.target.dataset.id});
            var opLabel = this.props.capitaliseFirstLetter(operation.name);

            filter.op = event.target.dataset.id;
            filter.opLabel = opLabel;

            this.props.setStateFilter(event.target.dataset.field, 'op', filter.op);
            this.props.setStateFilter(event.target.dataset.field, 'opLabel', opLabel);
            this.setState({filter: filter});
        },
        setStateValue: function(event) {
            var filter = this.state.filter;
            filter.val = event.target.value;
            this.props.setStateFilter(event.target.dataset.field, 'val', event.target.value);
            this.setState({filter: filter});
        },
        submitFilter: function (event) {
            var filter = this.state.filter;
            filter.submit = !(event.target.dataset.clear == "1");
            this.setState({filter: filter});
            this.props.setFilter(event);
        },
        render: function() {
            var that = this;
            var filter = this.state.filter;

            console.log('filter', filter);

            /*if (filter.field == 'firstname') {
                console.log('begin render text filter');
                console.log('state.filter', filter);
            }*/

            var operations = this.getOperations().map(function(item) {
                return ( <li> <a className="filter_op" data-id={item.op} data-field={filter.field} onClick={that.setStateOperation} href="#">{item.name}</a> </li> )
            });

            /*if (filter.field == 'firstname') {
                console.log('end render text filter');
            }*/

            return (
                <div className={"btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : "")} id={"f-grid-filter-" + filter.field}>
                    <button className="btn dropdown-toggle filter-text-main" data-toggle="dropdown">
                        <span className="f-grid-filter-field">{filter.label}</span>:
                        <span className="f-grid-filter-value"> {filter.submit ? filter.opLabel + "\"" + filter.val + "\"" : 'All'}  </span>
                        <span className="caret"></span>
                    </button>
                    <ul className="dropdown-menu filter-box">
                        <li>
                            <div className="input-group">
                                <div className="input-group-btn dropdown">
                                    <button className="btn btn-default dropdown-toggle filter-text-sub" data-toggle="dropdown">
                                        {filter.opLabel}
                                        <span className="caret"></span>
                                    </button>
                                    <ul className="dropdown-menu filter-sub">
                                        {operations}
                                    </ul>
                                </div>
                                <input type="text" className="form-control" data-field={filter.field} onChange={this.setStateValue} />
                                <div className="input-group-btn">
                                    <button type="button" className="btn btn-primary update" data-field={filter.field} onClick={this.submitFilter}>
                                        Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <abbr className="select2-search-choice-close" data-field={filter.field} data-clear="1" style={filter.submit ? {display: 'block'} : {display: 'none'}} onClick={this.submitFilter}></abbr>
                </div>
            );
        }
    });

    var FComFilterDateRange = React.createClass({
        getInitialState: function() {
            var filter = this.props.filter;
            filter.range = true;
            filter.val = "";
            filter.op = "between";
            return { filter: filter };
        },
        getOperations: function() {
            return [
                { name: 'between', label: 'Between', type: 'range' },
                { name: 'from', label: 'From', type: 'not_range' },
                { name: 'to', label: 'To', type: 'not_range' }
            ];
        },
        render: function() {
            var filter = this.state.filter;
            return (
                <div className="btn-group dropdown f-grid-filter" id={"f-grid-filter-" + filter.field}>
                    <button className="btn dropdown-toggle filter-text-main" data-toggle='dropdown'>
                        <span className='f-grid-filter-field'>{filter.label}</span>:
                        <span className='f-grid-filter-value'> All </span>
                        <span className="caret"></span>
                    </button>

                    <ul className="dropdown-menu filter-box">
                        <li>
                            <div className="input-group">
                                <div className="input-group-btn dropdown">
                                    <button className="btn btn-default dropdown-toggle filter-text-sub" data-toggle="dropdown">
                                        Between
                                        <span className="caret"></span>
                                    </button>
                                    <ul className="dropdown-menu filter-sub">
                                        <li>
                                            <a className="filter_op range" data-id="between" href="#">between</a>
                                        </li>
                                        <li>
                                            <a className="filter_op not_range" data-id="from" href="#">from</a>
                                        </li>
                                        <li>
                                            <a className="filter_op not_range" data-id="to" href="#">to</a>
                                        </li>
                                        <li>
                                            <a className="filter_op not_range" data-id="equal" href="#">is equal to</a>
                                        </li>
                                        <li>
                                            <a className="filter_op range" data-id="not_in" href="#">not in</a>
                                        </li>
                                    </ul>
                                </div>
                                <div className="input-group range" style={!filter.range ? {display: 'none'} : {display: 'table'}}>
                                    <input id={'date-range-text-' + filter.field} type="text" placeholder="Select date range" className="form-control daterange" value="" />
                                    <span id="daterange2" className="input-group-addon filter-date-range">
                                        <i className="icon-calendar"></i>
                                    </span>
                                </div>
                                <div className="datepicker input-group not_range" style={filter.range ? {display: 'none'} : {display: 'table'}}>
                                    <input type="text" placeholder="Select date" data-format="yyyy-MM-dd" className="form-control" value="" />
                                    <span className="input-group-addon">
                                        <span data-time-icon="icon-time" data-date-icon="icon-calendar" className="icon-calendar"></span>
                                    </span>
                                </div>
                                <div className="input-group-btn">
                                    <button type="button" className="btn btn-primary update" onClick={this.props.setFilter}>
                                        Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            );
        }
    });

    var FComFilterNumberRange = React.createClass({
        render: function() {
            return null;
        }
    });

    var FComFilterMultiSelect = React.createClass({
        render: function() {
            return null;
        }
    });

    var FComFilterSelect = React.createClass({
        render: function() {
            return null;
        }
    });

    return FComFilter;
});