/** @jsx React.DOM */

define(['underscore', 'react', 'select2', 'daterangepicker', 'datetimepicker'], function (_, React) {
    var FComFilter = React.createClass({displayName: "FComFilter",
        getInitialState: function() {
            var that = this;
            var filters = {};
            _.forEach(this.props.getConfig('filters'), function(f) {
                if (!f.field) {
                    return false;
                }
                _.extend(f, {
                    hidden: f.hidden == true || f.hidden == 'true',
                    label: that.getFieldName(f.field),
                    opLabel: f.opLabel? f.opLabel : '',
                    op: f.op ? f.op : '',
                    val: f.val ? f.val : '',
                    range: f.range ? f.range : true,
                    submit: f.val ? true : false
                });

                filters[f.field] = f;
            });

            return { filters: filters }
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
        componentDidUpdate: function() {
            var that = this;
            //todo: find another way to avoid re-render filters component after main component didUpdate
            this.renderDropdownFilters();
            this.renderListFilters();
            $(this.getDOMNode()).find('.dd-list').sortable({
                handle: '.dd-handle',
                revert: true,
                axis: 'y',
                stop: function() {
                    that.sortFilters();
                }
            });
        },
        sortFilters: function() {
            var personalizeUrl = this.props.getConfig('personalize_url');
            var id = this.props.getConfig('id');

            var newPosFilters = $(this.getDOMNode()).find('.dd-list').sortable('toArray', { attribute: 'data-filter-id' });
            var filters = this.state.filters;
            var newFilters = {};
            var postFilters = []; //reduce amount post data

            _.forEach(newPosFilters, function (filterField, index) {
                if (typeof filters[filterField] !== 'undefined') {
                    newFilters[filterField] = filters[filterField];
                    newFilters[filterField].position = index;
                    postFilters.push({
                        field: filterField,
                        position: index,
                        hidden: filters[filterField].hidden
                    });
                }
            });

            if (personalizeUrl) {
                $.post(personalizeUrl, { 'do': 'grid.filter.orders', 'grid': id, 'cols': JSON.stringify(postFilters) });
            }

            //console.log('newFilters', newFilters);
            this.setState({ filters: newFilters });
        },
        renderDropdownFilters: function() {
            var that = this;
            var id = this.props.getConfig('id');
            var filters = this.state.filters;

            var filterSettingNodes = _.map(filters, function(f) {
                return (
                    React.createElement("li", {"data-filter-id": f.field, className: "dd-item dd3-item"}, 
                        React.createElement("div", {className: "icon-ellipsis-vertical dd-handle dd3-handle"}), 
                        React.createElement("div", {className: "dd3-content"}, 
                            React.createElement("label", null, 
                                React.createElement("input", {className: "showhide_column", "data-field": f.field, onChange: that.toggleFilter, type: "checkbox", defaultChecked: !f.hidden ? 'checked' : ''}), 
                                f.label
                            )
                        )
                    )
                );
            });

            var mountNode = document.getElementById('list-filters-setting');
            React.unmountComponentAtNode(mountNode);
            React.render(React.createElement("ul", {className: id + " dd-list dropdown-menu filters ui-sortable"}, filterSettingNodes), mountNode);
        },
        renderListFilters: function() {
            var that = this;
            var id = this.props.getConfig('id');
            var filters = this.state.filters;

            var filterNodes = _.map(filters, function(f) {
                if (f.hidden) {
                    return false;
                }
                return (React.createElement(FComFilterNodeContainer, {filter: f, setFilter: that.doFilter, setStateFilter: that.setStateFilter, capitaliseFirstLetter: that.capitaliseFirstLetter, keepShowDropDown: that.keepShowDropDown, getConfig: that.props.getConfig}));
            });

            var mountNode = document.getElementById('list-filters');
            React.unmountComponentAtNode(mountNode);
            React.render(React.createElement("div", {className: id + " f-filter-btns"}, filterNodes), mountNode);
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

            //console.log('setStateFilter', filters);
            //this.setState({stateFilters: stateFilters});
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
            var personalizeUrl = this.props.getConfig('personalize_url');
            var id = this.props.getConfig('id');

            var filters = this.state.filters;
            var hidden = !(event.target.checked == true);
            filters[event.target.dataset.field].hidden = hidden;

            if (personalizeUrl) {
                $.post(personalizeUrl, { 'do': 'grid.filter.hidden', 'grid': id, 'col': event.target.dataset.field, 'hidden': hidden });
            }

            this.setState({filters: filters});
            this.keepShowDropDown(event.target);
        },
        prepareFilter: function () {
            //add submit filter
            var filters = this.state.filters;
            var submitFilters = {};
            _.forEach(filters, function(f) {
                submitFilters[f.field] = f;
                if (!f.submit) {
                    submitFilters[f.field].val = '';
                    $('#f-grid-filter-' + f.field).find('input').val('');
                }
            });
            return submitFilters;
        },
        /**
         * do filter
         * @param {Object} filter
         * @param {Boolean} isClear
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

            console.log('filters', filters);

            var filterSettings = (
                React.createElement("div", {className: id + ' dropdown', style: {"display" : "inline-block"}}, 
                    React.createElement("a", {"data-toggle": "dropdown", className: "btn dropdown-toggle showhide_columns"}, 
                        "Filters ", React.createElement("b", {className: "caret"})
                    ), 
                    React.createElement("div", {id: "list-filters-setting"})
                )
            );

            //console.log('end render filters');

            return (
                React.createElement("div", null, 
                    React.createElement("div", {className: "f-col-filters-selection pull-left"}, 
                        filterSettings
                    ), 
                    React.createElement("div", {id: "list-filters"})
                )
            );
        }
    });

    var FComFilterNodeContainer = React.createClass({displayName: "FComFilterNodeContainer",
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
                    node = React.createElement(FComFilterText, React.__spread({},  this.props));
                    break;
                case 'date-range':
                    node = React.createElement(FComFilterDateRange, React.__spread({},  this.props));
                    break;
                case 'number-range':
                    node = React.createElement(FComFilterNumberRange, React.__spread({},  this.props));
                    break;
                case 'multiselect':
                    node = React.createElement(FComFilterMultiSelect, React.__spread({},  this.props));
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
            var isClear = (event.target.dataset.clear == "1" || filter.val == '');
            filter.submit = !isClear;
            this.setState({filter: filter});
            this.props.setFilter(filter, isClear);
        },
        isRange: function(event) {
            return $(event.target).hasClass('range');
        },
        handleEnter: function(event) {
            if (event.which == 13) {
                var filter = this.state.filter;
                var isClear = false
                filter.submit = !isClear;
                this.setState({filter: filter});
                this.props.setFilter(filter, isClear);
            }
        }
    };

    var FComFilterText = React.createClass({displayName: "FComFilterText",
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
                return ( React.createElement("li", null, " ", React.createElement("a", {className: "filter_op", "data-id": item.op, onClick: that.setStateOperation, href: "#"}, item.name), " ") )
            });

            /*console.log('end render filter: ' +  filter.field);*/

            return (
                React.createElement("div", {className: "btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : ""), id: "f-grid-filter-" + filter.field}, 
                    React.createElement("button", {className: "btn dropdown-toggle filter-text-main", "data-toggle": "dropdown"}, 
                        React.createElement("span", {className: "f-grid-filter-field"}, filter.label), ":", 
                        React.createElement("span", {className: "f-grid-filter-value"}, " ", filter.submit ? filter.opLabel + "\"" + filter.val + "\"" : 'All', " "), 
                        React.createElement("span", {className: "caret"})
                    ), 
                    React.createElement("ul", {className: "dropdown-menu filter-box"}, 
                        React.createElement("li", null, 
                            React.createElement("div", {className: "input-group"}, 
                                React.createElement("div", {className: "input-group-btn dropdown"}, 
                                    React.createElement("button", {className: "btn btn-default dropdown-toggle filter-text-sub", "data-toggle": "dropdown"}, 
                                        filter.opLabel, 
                                        React.createElement("span", {className: "caret"})
                                    ), 
                                    React.createElement("ul", {className: "dropdown-menu filter-sub"}, 
                                        operations
                                    )
                                ), 
                                React.createElement("input", {type: "text", className: "form-control", onChange: this.setStateValue, onKeyUp: this.handleEnter}), 
                                React.createElement("div", {className: "input-group-btn"}, 
                                    React.createElement("button", {type: "button", className: "btn btn-primary update", onClick: this.submitFilter}, 
                                        React.createElement("i", {className: "icon-check-sign"}), " Update"
                                    )
                                )
                            )
                        )
                    ), 
                    React.createElement("abbr", {className: "select2-search-choice-close", "data-clear": "1", style: filter.submit ? {display: 'block'} : {display: 'none'}, onClick: this.submitFilter})
                )
            );
        }
    });

    var FComFilterDateRange = React.createClass({displayName: "FComFilterDateRange",
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
                return ( React.createElement("li", null, " ", React.createElement("a", {className: "filter_op " + (item.range ? 'range' : 'not_range'), "data-id": item.op, onClick: that.setStateOperation, href: "#"}, item.name), " ") )
            });

            return (
                React.createElement("div", {className: "btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : ""), id: "f-grid-filter-" + filter.field}, 
                    React.createElement("button", {className: "btn dropdown-toggle filter-text-main", "data-toggle": "dropdown"}, 
                        React.createElement("span", {className: "f-grid-filter-field"}, filter.label), ":", 
                        React.createElement("span", {className: "f-grid-filter-value"}, " ", filter.submit ? filter.opLabel + "\"" + filter.val + "\"" : 'All', " "), 
                        React.createElement("span", {className: "caret"})
                    ), 

                    React.createElement("ul", {className: "dropdown-menu filter-box"}, 
                        React.createElement("li", null, 
                            React.createElement("div", {className: "input-group"}, 
                                React.createElement("div", {className: "input-group-btn dropdown"}, 
                                    React.createElement("button", {className: "btn btn-default dropdown-toggle filter-text-sub", "data-toggle": "dropdown"}, 
                                        filter.opLabel, 
                                        React.createElement("span", {className: "caret"})
                                    ), 
                                    React.createElement("ul", {className: "dropdown-menu filter-sub"}, 
                                        operations
                                    )
                                ), 
                                React.createElement("div", {className: "input-group range", style: !filter.range ? {display: 'none'} : {display: 'table'}}, 
                                    React.createElement("input", {id: 'date-range-text-' + filter.field, type: "text", placeholder: "Select date range", className: "form-control daterange", onChange: this.setStateValue, onKeyUp: this.handleEnter}), 
                                    React.createElement("span", {id: "daterange2", className: "input-group-addon filter-date-range", "data-input": 'date-range-text-' + filter.field}, 
                                        React.createElement("i", {className: "icon-calendar"})
                                    )
                                ), 
                                React.createElement("div", {className: "datepicker input-group not_range", style: filter.range ? {display: 'none'} : {display: 'table'}}, 
                                    React.createElement("input", {type: "text", placeholder: "Select date", "data-format": "yyyy-MM-dd", className: "form-control", onChange: this.setStateValue, onKeyUp: this.handleEnter}), 
                                    React.createElement("span", {className: "input-group-addon"}, 
                                        React.createElement("span", {"data-time-icon": "icon-time", "data-date-icon": "icon-calendar", className: "icon-calendar"})
                                    )
                                ), 
                                React.createElement("div", {className: "input-group-btn"}, 
                                    React.createElement("button", {type: "button", className: "btn btn-primary update", onClick: this.submitFilter}, 
                                        React.createElement("i", {className: "icon-check-sign"}), " Update"
                                    )
                                )
                            )
                        )
                    ), 
                    React.createElement("abbr", {className: "select2-search-choice-close", "data-clear": "1", style: filter.submit ? {display: 'block'} : {display: 'none'}, onClick: this.submitFilter})
                )
            );
        }
    });

    var FComFilterNumberRange = React.createClass({displayName: "FComFilterNumberRange",
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
                return ( React.createElement("li", null, " ", React.createElement("a", {className: "filter_op " + (item.range ? 'range' : 'not_range'), "data-id": item.op, onClick: that.setStateOperation, href: "#"}, item.name), " ") )
            });

            return (
                React.createElement("div", {className: "btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : ""), id: "f-grid-filter-" + filter.field}, 
                    React.createElement("button", {className: "btn dropdown-toggle filter-text-main", "data-toggle": "dropdown"}, 
                        React.createElement("span", {className: "f-grid-filter-field"}, filter.label), ":", 
                        React.createElement("span", {className: "f-grid-filter-value"}, " ", filter.submit ? filter.opLabel + "\"" + filter.val + "\"" : 'All', " "), 
                        React.createElement("span", {className: "caret"})
                    ), 

                    React.createElement("ul", {className: "dropdown-menu filter-box"}, 
                        React.createElement("li", null, 
                            React.createElement("div", {className: "input-group"}, 
                                React.createElement("div", {className: "input-group-btn dropdown"}, 
                                    React.createElement("button", {className: "btn btn-default dropdown-toggle filter-text-sub", "data-toggle": "dropdown"}, 
                                        filter.opLabel, 
                                        React.createElement("span", {className: "caret"})
                                    ), 
                                    React.createElement("ul", {className: "dropdown-menu filter-sub"}, 
                                        operations
                                    )
                                ), 
                                React.createElement("div", {className: "input-group-btn range", style: !filter.range ? {display: 'none'} : {display: 'table'}}, 
                                    React.createElement("input", {type: "text", "data-type": "from", placeholder: "From", className: "form-control js-number1", style: {width: '45%'}, onChange: this.setStateRangeValue, onKeyUp: this.handleEnter}), 
                                    " ", React.createElement("i", {className: "icon-resize-horizontal"}), " ", 
                                    React.createElement("input", {type: "text", "data-type": "to", placeholder: "To", className: "form-control js-number2", style: {width: '45%'}, onChange: this.setStateRangeValue, onKeyUp: this.handleEnter})
                                ), 
                                React.createElement("div", {className: "input-group-btn not_range", style: filter.range ? {display: 'none'} : {display: 'table'}}, 
                                    React.createElement("input", {type: "text", placeholder: "Number", className: "form-control js-number", onChange: this.setStateValue, onKeyUp: this.handleEnter})
                                ), 
                                React.createElement("div", {className: "input-group-btn"}, 
                                    React.createElement("button", {type: "button", className: "btn btn-primary update", onClick: this.submitFilter}, 
                                        React.createElement("i", {className: "icon-check-sign"}), " Update"
                                    )
                                )
                            )
                        )
                    ), 
                    React.createElement("abbr", {className: "select2-search-choice-close", "data-clear": "1", style: filter.submit ? {display: 'block'} : {display: 'none'}, onClick: this.submitFilter})
                )
            );
        }
    });

    var FComFilterMultiSelect = React.createClass({displayName: "FComFilterMultiSelect",
        mixins: [FilterStateMixin],
        getInitialState: function() {
            var filter = this.props.filter;

            //get data to build select2
            var column = _.findWhere(this.props.getConfig('columns'), {name: filter.field});
            var data = [];

            var filterValueArr = filter.val.split(',');
            var valName = [];
            _.forEach(column.options, function(value, key) {
                data.push({ id: key, text: value });
                //add value label name
                if (_.contains(filterValueArr, key)) {
                    valName.push(value);
                }
            });
            filter.valName = valName.length ? valName.join(', ') : '';

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
                filter.valName = valName.join(', ');
            });

            filterContainer.find('.select2-container').on('click', function() {
                return false;
            });
        },
        render: function() {
            var filter = this.state.filter;

            return (
                React.createElement("div", {className: "btn-group dropdown f-grid-filter" + (filter.submit ? " f-grid-filter-val" : ""), id: "f-grid-filter-" + filter.field}, 
                    React.createElement("button", {className: "btn dropdown-toggle filter-text-main", "data-toggle": "dropdown"}, 
                        React.createElement("span", {className: "f-grid-filter-field"}, " ", filter.label, ": "), 
                        React.createElement("span", {className: "f-grid-filter-value"}, " ", filter.submit ? filter.opLabel + " " + filter.valName : 'All', " "), 
                        React.createElement("span", {className: "caret"})
                    ), 
                    React.createElement("ul", {className: "dropdown-menu filter-box"}, 
                        React.createElement("li", null, 
                            React.createElement("div", {className: "input-group"}, 
                                React.createElement("input", {type: "hidden", id: "multi_hidden", style: {width: '100%', minWidth: '120px'}}), 
                                React.createElement("div", {className: "input-group-btn"}, 
                                    React.createElement("button", {type: "button", className: "btn btn-primary update", onClick: this.submitFilter}, 
                                        React.createElement("i", {className: "icon-check-sign"}), " Update"
                                    )
                                )
                            )
                        )
                    ), 
                    React.createElement("abbr", {className: "select2-search-choice-close", "data-clear": "1", style: filter.submit ? {display: 'block'} : {display: 'none'}, onClick: this.submitFilter})
                )
            );
        }
    });

    return FComFilter;
});