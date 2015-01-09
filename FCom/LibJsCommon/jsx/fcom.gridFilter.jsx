/** @jsx React.DOM */

define(['underscore', 'react', 'select2', 'daterangepicker', 'datetimepicker'], function (_, React) {
    var FComFilter = React.createClass({
        getInitialState: function() {
            var that = this;
            var filters = {};
            _.forEach(this.props.getConfig('filters'), function(f) {
                _.extend(f, {
                    show: true,
                    label: that.getFieldName(f.field),
                    opLabel: '',
                    op: '',
                    val: '',
                    range: true,
                    submit: false
                });

                filters[f.field] = f;
            });

            return {
                filters: filters
            }
        },
        getDefaultProps: function() {
            return {
                "placeholderText": "Quick Search"
            }
        },
        componentDidMount: function() {
            var that = this;

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
        },
        capitaliseFirstLetter: function(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        },
        setStateFilter: function(field, key, value) {
            if (typeof field === 'undefined' ||  typeof key === 'undefined') {
                return;
            }

            var filters = this.state.filters;
            if (typeof filters[field] === 'undefined') {
                return;
            }
            filters[field][key] = value;

            console.log('setStateFilter', filters);
            //this.setState({stateFilters: stateFilters});
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
        /**
         * show/hide filter block
         * @param event
         */
        toggleFilter: function(event) {
            var filters = this.state.filters;
            filters[event.target.dataset.field].show = (event.target.checked == true);
            this.setState({filters: filters});
            this.keepShowDropDown(event.target);
        },
        prepareFilter: function () {
            //add submit filter
            var filters = this.state.filters;
            var submitFilters = {};
            _.forEach(filters, function(f) {
                if (f.submit == true) {
                    submitFilters[f.field] = f;
                }
            });
            return submitFilters;
        },
        /**
         * do filter
         * @param {} filter
         * @param bool isClear
         */
        doFilter: function (filter, isClear) {
            if (typeof isClear == 'undefined') {
                isClear = false;
            }
            //prepare data
            var field = filter.field;
            this.setStateFilter(field, 'submit', !isClear);

            var submitFilters = this.prepareFilter();
            //console.log('submitFilters', submitFilters);

            //call parent griddle function to handle filter
            this.props.changeFilter(JSON.stringify(submitFilters));
        },
        render: function() {
            //console.log('begin render filters');
            var that = this;
            var id = this.props.getConfig('id');
            var filters = this.state.filters;

            //console.log('filters', filters);

            //quick search
            var quickSearch = <input type="text" className="f-grid-quick-search form-control" placeholder={this.props.placeholderText} onChange={this.handleChange} id={id + '-quick-search'} />;

            var filterSettingNodes = _.map(filters, function(f) {
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

            var filterNodes = _.map(filters, function(f) {
                if (!f.show) {
                    return false;
                }
                return (<FComFilterNodeContainer filter={f} setFilter={that.doFilter} setStateFilter={that.setStateFilter} capitaliseFirstLetter={that.capitaliseFirstLetter} keepShowDropDown={that.keepShowDropDown} getConfig={that.props.getConfig} />);
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
                default:
                    console.log('Does not support filter type: ' + filter.type);
                    break;
            }

            return node;
        }
    });

    var FilterStateMixin = {
        setStateOperation: function(event) {
            var filter = this.state.filter;
            var operation = _.findWhere(this.getOperations(), {op: event.target.dataset.id});
            var opLabel = this.props.capitaliseFirstLetter(operation.name);
            var isRange = this.isRange(event);

            filter.op = event.target.dataset.id;
            filter.opLabel = opLabel;
            filter.range = isRange;

            this.props.setStateFilter(filter.field, 'op', filter.op);
            this.props.setStateFilter(filter.field, 'opLabel', opLabel);
            this.props.setStateFilter(filter.field, 'range', isRange);
            this.setState({filter: filter});
        },
        setStateValue: function(event) {
            var filter = this.state.filter;
            filter.val = event.target.value;
            this.props.setStateFilter(filter.field, 'val', event.target.value);
            this.setState({filter: filter});
        },
        submitFilter: function (event) {
            var filter = this.state.filter;
            var isClear = event.target.dataset.clear == "1";
            filter.submit = !isClear;
            this.setState({filter: filter});
            this.props.setFilter(filter, isClear);
        },
        isRange: function(event) {
            return $(event.target).hasClass('range');
        }
    };

    var FComFilterText = React.createClass({
        mixins: [FilterStateMixin],
        getInitialState: function () {
            var filter = this.props.filter;

            if (filter.op == '') { //set default value operation
                var operation = _.findWhere(this.getOperations(), {'default': true});
                _.extend(filter, {
                    op: operation.op,
                    opLabel: this.props.capitaliseFirstLetter(operation.name),
                    val: '',
                    submit: false
                });
                /*this.props.setStateFilter(filter.field, 'op', filter.op);
                this.props.setStateFilter(filter.field, 'opLabel', filter.opLabel);*/
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
        render: function() {
            var that = this;
            var filter = this.state.filter;

            /*console.log('filter.' + filter.field, filter);
            console.log('begin render filter: ' +  filter.field);*/

            var operations = this.getOperations().map(function(item) {
                return ( <li> <a className="filter_op" data-id={item.op} onClick={that.setStateOperation} href="#">{item.name}</a> </li> )
            });

            /*console.log('end render filter: ' +  filter.field);*/

            return (
                <div className={"btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : "")} id={"f-grid-filter-" + filter.field}>
                    <button className="btn dropdown-toggle filter-text-main" data-toggle="dropdown">
                        <span className="f-grid-filter-field">{filter.label}</span>:
                        <span className="f-grid-filter-value"> {filter.submit ? filter.opLabel + "\"" + filter.val + "\"" : 'All'} </span>
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
                                <input type="text" className="form-control" onChange={this.setStateValue} />
                                <div className="input-group-btn">
                                    <button type="button" className="btn btn-primary update" onClick={this.submitFilter}>
                                        <i className="icon-check-sign"></i> Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <abbr className="select2-search-choice-close" data-clear="1" style={filter.submit ? {display: 'block'} : {display: 'none'}} onClick={this.submitFilter}></abbr>
                </div>
            );
        }
    });

    var FComFilterDateRange = React.createClass({
        mixins: [FilterStateMixin],
        getInitialState: function() {
            var filter = this.props.filter;
            if (filter.op == '') { //set default value operation
                var operation = _.findWhere(this.getOperations(), {'default': true});
                _.extend(filter, {
                    op: operation.op,
                    opLabel: this.props.capitaliseFirstLetter(operation.name),
                    val: '',
                    range: operation.range,
                    submit: false
                });
            }

            return { filter: filter };
        },
        getOperations: function() {
            return [
                { op: 'between', name: 'between', range: true, 'default': true },
                { op: 'from', name: 'from', range: false },
                { op: 'to', name: 'to', range: false },
                { op: 'equal', name: 'is equal to', range: false },
                { op: 'not_in', name: 'not in', range: true }
            ];
        },
        componentDidMount: function() {
            var filter = this.state.filter;
            var that = this;
            var filterContainer = $('#f-grid-filter-' + filter.field);

            //init datepicker + daterangepicker
            filterContainer.find('#daterange2').daterangepicker({
                format: "YYYY-MM-DD",
                opens: 'left'
            }, function (start, end) {
                var value = start.format("YYYY-MM-DD") + "~" + end.format("YYYY-MM-DD");
                $('#date-range-text-' + filter.field).val(value);
                filter.val = value;
                that.setState({filter: filter});
            });

            filterContainer.find('#daterange2').on('click', function() {
                return false;
            });


            filterContainer.find(".datepicker").datetimepicker({ pickTime: false });

            $('.daterangepicker').on('click', function (ev) {
                    ev.stopPropagation();
                    ev.preventDefault();
                    return false;
                }
            );
        },
        render: function() {
            var filter = this.state.filter;
            var that = this;

            var operations = this.getOperations().map(function(item) {
                return ( <li> <a className={"filter_op " + (item.range ? 'range' : 'not_range')} data-id={item.op} onClick={that.setStateOperation} href="#">{item.name}</a> </li> )
            });

            return (
                <div className={"btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : "")} id={"f-grid-filter-" + filter.field}>
                    <button className="btn dropdown-toggle filter-text-main" data-toggle='dropdown'>
                        <span className='f-grid-filter-field'>{filter.label}</span>:
                        <span className='f-grid-filter-value'> {filter.submit ? filter.opLabel + "\"" + filter.val + "\"" : 'All'} </span>
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
                                <div className="input-group range" style={!filter.range ? {display: 'none'} : {display: 'table'}}>
                                    <input id={'date-range-text-' + filter.field} type="text" placeholder="Select date range" className="form-control daterange" onChange={this.setStateValue} />
                                    <span id="daterange2" className="input-group-addon filter-date-range" data-input={'date-range-text-' + filter.field}>
                                        <i className="icon-calendar"></i>
                                    </span>
                                </div>
                                <div className="datepicker input-group not_range" style={filter.range ? {display: 'none'} : {display: 'table'}}>
                                    <input type="text" placeholder="Select date" data-format="yyyy-MM-dd" className="form-control" onChange={this.setStateValue} />
                                    <span className="input-group-addon">
                                        <span data-time-icon="icon-time" data-date-icon="icon-calendar" className="icon-calendar"></span>
                                    </span>
                                </div>
                                <div className="input-group-btn">
                                    <button type="button" className="btn btn-primary update" onClick={this.submitFilter}>
                                        <i className="icon-check-sign"></i> Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <abbr className="select2-search-choice-close" data-clear="1" style={filter.submit ? {display: 'block'} : {display: 'none'}} onClick={this.submitFilter}></abbr>
                </div>
            );
        }
    });

    var FComFilterNumberRange = React.createClass({
        mixins: [FilterStateMixin],
        getInitialState: function() {
            var filter = this.props.filter;
            if (filter.op == '') { //set default value operation
                var operation = _.findWhere(this.getOperations(), {'default': true});
                _.extend(filter, {
                    op: operation.op,
                    opLabel: this.props.capitaliseFirstLetter(operation.name),
                    val: '',
                    from: '',
                    to: '',
                    range: operation.range,
                    submit: false
                });
            }

            return { filter: filter };
        },
        getOperations: function() {
            return [
                { op: 'between', name: 'between', range: true, 'default': true },
                { op: 'from', name: 'from', range: false },
                { op: 'to', name: 'to', range: false },
                { op: 'equal', name: 'is equal to', range: false },
                { op: 'not_in', name: 'not in', range: true }
            ];
        },
        setStateRangeValue: function(event) {
            var filter = this.state.filter;
            filter[event.target.dataset.type] = event.target.value;
            filter.val = filter.from + '~' + filter.to;
            this.props.setStateFilter(filter.field, 'val', filter.val);
            this.setState({filter: filter});
        },
        render: function() {
            var that = this;
            var filter = this.state.filter;

            var operations = this.getOperations().map(function(item) {
                return ( <li> <a className={"filter_op " + (item.range ? 'range' : 'not_range')} data-id={item.op} onClick={that.setStateOperation} href="#">{item.name}</a> </li> )
            });

            return (
                <div className={"btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : "")} id={"f-grid-filter-" + filter.field}>
                    <button className="btn dropdown-toggle filter-text-main" data-toggle='dropdown'>
                        <span className='f-grid-filter-field'>{filter.label}</span>:
                        <span className='f-grid-filter-value'> {filter.submit ? filter.opLabel + "\"" + filter.val + "\"" : 'All'} </span>
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
                                <div className="input-group-btn range" style={!filter.range ? {display: 'none'} : {display: 'table'}}>
                                    <input type="text" data-type="from" placeholder="From" className="form-control js-number1" style={{width: '45%'}} onChange={this.setStateRangeValue} />
                                    &nbsp;<i className="icon-resize-horizontal"></i>&nbsp;
                                    <input type="text" data-type="to" placeholder="To" className="form-control js-number2" style={{width: '45%'}} onChange={this.setStateRangeValue} />
                                </div>
                                <div className="input-group-btn not_range" style={filter.range ? {display: 'none'} : {display: 'table'}}>
                                    <input type="text" placeholder="Number" className="form-control js-number" onChange={this.setStateValue} />
                                </div>
                                <div className="input-group-btn">
                                    <button type="button" className="btn btn-primary update" onClick={this.submitFilter}>
                                        <i className="icon-check-sign"></i> Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <abbr className="select2-search-choice-close" data-clear="1" style={filter.submit ? {display: 'block'} : {display: 'none'}} onClick={this.submitFilter}></abbr>
                </div>
            );
        }
    });

    var FComFilterMultiSelect = React.createClass({
        mixins: [FilterStateMixin],
        getInitialState: function() {
            var filter = this.props.filter;
            filter.valName = '';
            //get data to build select2
            var column = _.findWhere(this.props.getConfig('columns'), {name: filter.field});
            var data = [];
            _.forEach(column.options, function(value, key) {
                data.push({ id: key, text: value });
            });

            return { filter: filter, filterData: data };
        },
        getValueName: function(value) {
            var data = _.findWhere(this.state.filterData, { id: value });
            return data ? data.text : value;
        },
        componentDidMount: function() {
            var that = this;
            var filter = this.state.filter;
            var filterContainer = $('#f-grid-filter-' + filter.field);

            filterContainer.find('#multi_hidden').select2({
                multiple: true,
                data: this.state.filterData,
                placeholder: 'All'
                //closeOnSelect: true
            });

            filterContainer.find('#multi_hidden').on('change', function(e) {
                filter.val = e.val.join(',');
                var valName = [];
                _.forEach(e.val, function(value) {
                    valName.push(that.getValueName(value));
                });
                filter.valName = valName.join(',');
            });

            filterContainer.find('.select2-container').on('click', function() {
                return false;
            });
        },
        render: function() {
            var filter = this.state.filter;

            return (
                <div className={"btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : "")} id={"f-grid-filter-" + filter.field}>
                    <button className='btn dropdown-toggle filter-text-main' data-toggle='dropdown'>
                        <span className='f-grid-filter-field'> {filter.label} </span>
                        <span className='f-grid-filter-value'> {filter.submit ? filter.opLabel + ": " + filter.valName : 'All'} </span>
                        <span className="caret"></span>
                    </button>
                    <ul className="dropdown-menu filter-box">
                        <li>
                            <div className="input-group">
                                <input type="hidden" id="multi_hidden" style={{width: '100%', minWidth: '120px'}} />
                                <div className="input-group-btn">
                                    <button type="button" className="btn btn-primary update" onClick={this.submitFilter}>
                                        <i className="icon-check-sign"></i> Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <abbr className="select2-search-choice-close" data-clear="1" style={filter.submit ? {display: 'block'} : {display: 'none'}} onClick={this.submitFilter}></abbr>
                </div>
            );
        }
    });

    return FComFilter;
});