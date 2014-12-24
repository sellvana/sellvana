/** @jsx React.DOM */

define(['underscore', 'react'], function (_, React) {
    var FComFilter = React.createClass({
        getInitialState: function() {
            var filters = this.props.getConfig('filters').map(function(f, index) {
                f.show = true;
                return f;
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
                    var operator = $(e.target);
                    var text = operator.html();
                    $(this).parents('.input-group-btn').find('button.filter-text-sub').html(text.charAt(0).toUpperCase() + text.slice(1) + "<span class='caret'></span>");
                    $(this).parents('div.dropdown').each(function() {
                        if ($(this).hasClass('input-group-btn')) {
                            $(this).removeClass('open');
                        }
                    });
                    e.stopPropagation();
                })
            ;
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
        /**
         * filter data
         * @param event
         */
        filter: function (event) {
            console.log('doing filter');
        },
        render: function() {
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
                                {that.getFieldName(f.field)}
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
                return (<FComFilterNodeContainer filter={f} getFieldName={that.getFieldName} setFilter={that.filter} />);
            });

            return (
                <div>
                    <div className="f-col-filters-selection pull-left">
                        {quickSearch}
                        {filterSettings}
                    </div>
                    <span className={id + " f-filter-btns"}>
                        {filterNodes}
                    </span>
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
        render: function() {
            return (
                <div className="btn-group dropdown f-grid-filter">
                    <button className="btn dropdown-toggle filter-text-main" data-toggle="dropdown">
                        <span className="f-grid-filter-field">{this.props.getFieldName(this.props.filter.field)}</span>:
                        <span className="f-grid-filter-value"> All </span>
                        <span className="caret"></span>
                    </button>
                    <ul className="dropdown-menu filter-box">
                        <li>
                            <div className="input-group">
                                <div className="input-group-btn dropdown">
                                    <button className="btn btn-default dropdown-toggle filter-text-sub" data-toggle="dropdown">
                                        Contains
                                        <span className="caret"></span>
                                    </button>
                                    <ul className="dropdown-menu filter-sub">
                                        <li>
                                            <a className="filter_op" data-id="contains" href="#">contains</a>
                                        </li>
                                        <li>
                                            <a className="filter_op" data-id="not" href="#">does not contain</a>
                                        </li>
                                        <li>
                                            <a className="filter_op" data-id="equal" href="#">is equal to</a>
                                        </li>
                                        <li>
                                            <a className="filter_op" data-id="start" href="#">start with</a>
                                        </li>
                                        <li>
                                            <a className="filter_op" data-id="end" href="#">end with</a>
                                        </li>
                                    </ul>
                                </div>
                                <input type="text" className="form-control" value="" />
                                <div className="input-group-btn">
                                    <button type="button" className="btn btn-primary update" onClick={this.props.setFilter}>
                                        Update
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <abbr className="select2-search-choice-close"></abbr>
                </div>
            );
        }
    });

    var FComFilterDateRange = React.createClass({
        render: function() {
            return null;
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