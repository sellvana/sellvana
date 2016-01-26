//noinspection JSPotentiallyInvalidUsageOfThis
define(['react', 'jquery', 'fcom.locale', 'sortable', 'bootstrap', 'underscore', 'select2', 'jquery.validate'], function (React, $, Locale, Sortable) {
    FCom.Components = {};

    /**
     * common mixin can be used in both of grid and form
     * @type {{text2html: Function, html2text: Function, fileSizeFormat: Function}}
     */
    FCom.Mixin = {
        text2html: function (val) {
            var text = $.parseHTML(val);
            return (text !== null) ? text[0].data : null;
        },
        html2text: function (val) {
            return $('<div/>').text(val).html();
        },
        fileSizeFormat: function (size) {
            size = parseInt(size);
            if (size / (1024 * 1024) > 1) {
                size = size / (1024 * 1024);
                size = size.toFixed(2) + ' MB';
            } else if (size / 1024 > 1) {
                size = size / 1024;
                size = size.toFixed(2) + ' KB';
            } else {
                size = size ? size + ' Byte' : '';
            }

            return size;
        },
        dateTimeNow: function () {
            var d = new Date();
            return d.getFullYear() + '-' + toString((d.getMonth() + 1)) + '-' + toString(d.getDate()) + ' ' + toString(d.getHours()) + ':' + toString(d.getMinutes()) + ':' + toString(d.getSeconds());

            function toString(val) {
                return (val < 10) ? '0' + val : val;
            }
        },
        updateModalWidth: function (modal) {
            //todo: add css class to modal to pre-define width, eg: large, medium, small
            $(modal.getDOMNode()).find('.modal-dialog').css('width', '900px');
        },
        /**
         * remove special chars
         * @param {string} str
         */
        removeSpecialChars: function (str) { //todo: put this function to global utilities object
            var label = str.substr(0, str.lastIndexOf('.'));
            return label.replace(/[^A-Z0-9]/ig, ' ');
        }
    };

    /**
     * form mixin
     * @type {{getInputId: Function, getInputName: Function, validationRules: Function}}
     */
    FCom.FormMixin = {
        getInputId: function () {
            var field = this.props.field;
            if (this.props.id) {
                return this.props.id;
            }
            if (!field) {
                return '';
            }
            if (this.props.settings_module && !this.props.id_prefix) {
                return 'modules-' + this.props.settings_module + '-' + field;
            }
            return ((this.props.id_prefix) ? this.props.id_prefix : 'model') + '-' + field;
        },
        getInputName: function () {
            if ((this.props.name)) {
                return this.props.name;
            }
            if (!this.props.field) {
                return '';
            }
            var name;
            if (this.props.settings_module && !this.props.name_prefix) {
                name = 'config[modules][' + this.props.settings_module + '][' + this.props.field + ']';
            } else {
                name = (this.props.name_prefix ? this.props.name_prefix : 'model') + '[' + this.props.field + ']';
            }
            if (this.props.multiple) {
                name += '[]';
            }
            return name;
        },
        /**
         * Set validation rules for input element
         *
         * @param {object} data
         */
        validationRules: function (data) {
            var rules = {};
            for (var key in data) {
                if (!data.hasOwnProperty(key)) {
                    continue;
                }
                switch (key) {
                    case 'required':
                        rules['data-rule-required'] = 'true';
                        break;
                    case 'email':
                        rules['data-rule-email'] = 'true';
                        break;
                    case 'number':
                        rules['data-rule-number'] = 'true';
                        break;
                    case 'digits':
                        rules['data-rule-digits'] = 'true';
                        break;
                    case 'ip':
                        rules['data-rule-ipv4'] = 'true';
                        break;
                    case 'url':
                        rules['data-rule-url'] = 'true';
                        break;
                    case 'phoneus':
                        rules['data-rule-phoneus'] = 'true';
                        break;
                    case 'minlength':
                        rules['data-rule-minlength'] = data[key];
                        break;
                    case 'maxlength':
                        rules['data-rule-maxlength'] = data[key];
                        break;
                    case 'max':
                        rules['data-rule-max'] = data[key];
                        break;
                    case 'min':
                        rules['data-rule-min'] = data[key];
                        break;
                    case 'range':
                        rules['data-rule-range'] = '[' + data[key][0] + ',' + data[key][1] + ']';
                        break;
                    case 'date':
                        rules['data-rule-dateiso'] = 'true';
                        rules['data-mask'] = '9999-99-99';
                        rules['placeholder'] = 'YYYY-MM-DD';
                        break;
                }
            }

            return rules;
        }
    };

    FCom.Components.MultiSite = React.createClass({
        displayName: "MultiSite",
        getDefaultProps: function () {
            return {
                defaultValue: [''],
                sites: []
            };
        },
        getInitialState: function () {
            return {
                selections: []
            };
        },
        componentWillMount: function () {
            this.setState({ sites: this.getSites() });
        },
        getSites: function () {
            var sites = this.props.sites;
            sites[''] = Locale._('Default configuration');
            sites = _(sites).map(function (site, id) {
                return {
                    id: id, text: site
                }
            });

            return _.sortBy(sites, 'id');
        },
        initSelect2: function () {
            return {
                id: 'multisite_list',
                className: '',
                multiple: false
            };
        },
        handleSelections: function (e, sites) {
            this.setState({sites: sites});

            if (this.props.onChange) {
                this.props.onChange(e, this.props.callback, this.state.sites);
            }
        },
        shouldComponentUpdate: function (nextProps, nextState) {
            return nextState.selections !== this.state.selections || nextProps.sites !== this.props.sites;
        },
        render: function () {
            return (
                <div className={this.props.cClass || 'col-md-5'}>
                    <input type="hidden" id="site_values" name="site_values" />
                    <FCom.Components.Select2 {...this.initSelect2()}
                                        options={this.getSites()}
                                        onSelection={this.handleSelections}
                                        multiple={this.props.multiple || false} val={this.props.defaultValue}/>
                </div>
            );
        }
    });

    FCom.Components.ControlLabel = React.createClass({
        render: function () {
            var cl = "control-label " + this.props.label_class + (this.props.required ? ' required' : '');
            return (
                <label className={cl}
                       htmlFor={ this.props.input_id }>{this.props.children}</label>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                label_class: "col-md-2",
                required: false,
                input_id: ''
            };
        }
    });

    FCom.Components.ControlInput = React.createClass({
        mixins: [FCom.Mixin, FCom.FormMixin],
        getDefaultProps: function () {
            return {
                value: '',
                type: 'text',
                attrs: {},
                validation: {}
            }
        },
        getInitialState: function () {
            return {
                value: this.props.value
            };
        },
        componentWillReceiveProps: function (nextProps) {
            this.setState({ value: nextProps.value });
        },
        handleChange: function (e) {
            this.setState({ value: e.target.value });
        },
        render: function () {
            var node = null;
            var validationRules = this.validationRules(this.props.validation);
            switch (this.props.type) {
                case 'textarea':
                    node = <textarea id={this.props.id || guid()}
                                    name={this.props.name || guid()}
                                    className={"form-control " + this.props.className}
                                    onChange={this.handleChange}
                                    onBlur={this.props.callback}
                                    value={this.state.value} {...this.props.attrs} {...validationRules} />;
                    break;
                case 'select':
                    var options = [];
                    _(this.props.options).each(function (text, value) {
                        options.push(<option value={value} key={value}>{text}</option>);
                    });
                    node = <select id={this.props.id || guid()}
                            name={this.props.name || guid()}
                            className={"form-control " + this.props.className}
                            onChange={this.handleChange}
                            value={this.state.value} {...this.props.attrs} {...validationRules}>{options}</select>;
                    break;
                default:
                    node = <input type={this.props.type}
                                  id={this.props.id || guid()}
                                  name={this.props.name || guid()}
                                  className={"form-control " + this.props.className}
                                  onChange={this.handleChange}
                                  onBlur={this.props.callback}
                                  value={this.state.value} {...this.props.attrs} {...validationRules} />;
                    break;
            }
            return node;
        }
    });

    FCom.Components.SpecialInput = React.createClass({
        getDefaultProps: function () {
            return {
                type: '',
                disabled: false,
                attrs: {}
            };
        },
        getInitialState: function () {
            return {
                value: this.props.value
            };
        },
        componentDidMount: function () {
            switch (this.props.type) {
                case 'switch':
                    $(this.refs['switch-cbx-' + this.props.id].getDOMNode()).bootstrapSwitch({
                        state: parseInt(this.state.value) == 1,
                        onSwitchChange: this.props.onChange
                    });
                    break;
                case 'wysiwyg':
                    adminForm.wysiwygInit(null, this.state.value, this.props.onChange);
                    break;
            }
        },
        componentWillReceiveProps: function (nextProps) {
            this.setState({ value: nextProps.value });
        },
        componentWillUnmount: function () {
            if (this.refs['switch-cbx-' + this.props.id])
                React.unmountComponentAtNode(this.refs['switch-cbx-' + this.props.id]);
            if (this.refs['wysiwyg-' + this.props.id])
                React.unmountComponentAtNode(this.refs['wysiwyg-' + this.props.id]);
        },
        handleSwitch: function (e, state) {
            this.setState({ value: state });
        },
        handleChange: function (e) {
            this.setState({ value: e.target.value });
        },
        createSwitchBox: function () {
            return <input type="checkbox" id={this.props.id}
                          name={this.props.name}
                          className={"switch-cbx " + this.props.className}
                          defaultChecked={!!(this.state.value === undefined || this.state.value === '1')}
                          value={this.state.value}
                          onChange={this.handleSwitch}
                          ref={'switch-cbx-' + this.props.id} {...this.props.attrs} />;
        },
        createWysiwyg: function () {
            if (!this.props.id) {
                this.props.id = guid();
            }
            return <div><textarea id={this.props.id}
                             name={this.props.name}
                             className={'form-control ' + this.props.className}
                             defaultValue={this.state.value}
                             onChange={this.handleChange}
                             ref={'wysiwyg-' + this.props.id} {...this.props.attrs} />
                        <label htmlFor={this.props.id} className="error" style={{ display: 'none' }} />
                    </div>;
        },
        renderNode: function () {
            switch (this.props.type) {
                case 'switch':
                    return this.createSwitchBox();
                    break;
                case 'wysiwyg':
                    return this.createWysiwyg();
                    break;
            }
        },
        render: function () {
            return (
                <div>{this.renderNode()}</div>
            );
        }
    });

    FCom.Components.HelpBlock = React.createClass({
        render: function () {
            return (<span className={"help-block "+ this.props.helpBlockClass}>{ this.props.text }</span>);
        }
    });

    FCom.Components.Input = React.createClass({
        mixins: [FCom.FormMixin],
        render: function () {
            var formGroupClass = this.props.formGroupClass,
                inputDivClass = this.props.inputDivClass,
                inputClass = this.props.inputClass,
                inputValue = this.props.inputValue,
                other = _.omit(this.props, ['formGroupClass', 'inputDivClass', 'inputClass', 'inputValue']);
            var className = "form-control";
            if (inputClass) {
                className += " " + inputClass;
            }
            if (this.props.required) {
                className += " required";
            }
            var helpBlock = <span/>;
            if (this.props['helpBlockText']) {
                helpBlock = <FCom.Components.HelpBlock text={this.props['helpBlockText']}/>;
            }
            var inputId = this.getInputId();

            return (
                <div className={"form-group " + formGroupClass}>
                    <FCom.Components.ControlLabel {...other} input_id={inputId}>
                        {this.props.label}
                    </FCom.Components.ControlLabel>
                    <div className={inputDivClass}>
                        <input {...other}
                            id={inputId}
                            name={this.getInputName()}
                            className={className}
                            defaultValue={inputValue}
                            dataRuleRequired={ this.props.required ? "true":'' }
                        />
                        {helpBlock}
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                formGroupClass: '',
                inputDivClass: 'col-md-5',
                type: 'text',
                inputId: '',
                inputName: '',
                inputClass: ''
            };
        }
    });

    FCom.Components.HelpIcon = React.createClass({
        render: function () {
            return (
                <a id={this.props.id} className="pull-right" href="#" ref="icon"
                   data-toggle="popover" data-trigger="focus" tabIndex="-1"
                   data-content={this.props.content} data-container="body">
                    <span className="icon-question-sign" />
                </a>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                id: '',
                content: ''
            };
        },
        componentDidMount: function () {
            // component default properties
            var $help = $(this.refs.icon.getDOMNode());
            $help.popover({placement: 'auto', trigger: 'hover focus'});
            $help.on('click', function (e) {
                e.preventDefault();
            });
        }
    });

    FCom.Components.YesNo = React.createClass({
        render: function () {
            return (
                <select name={this.props.name} id={this.props.id}
                        className={"form-control to-select2 " + this.props.className} style={this.props.style}
                        defaultValue={this.props.value}>
                    <option value="0">{this.props.optNo}</option>
                    <option value="1">{this.props.optYes}</option>
                </select>
            )
        },
        getDefaultProps: function () {
            return {
                style: {width: "auto"},
                optYes: "YES",
                optNo: "no",
                value: "1"
            };
        },
        componentDidMount: function () {
            $(this.getDOMNode()).select2({minimumResultsForSearch: 15}).on('change', this.props.onChange);
        }
    });

    FCom.Components.Button = React.createClass({
        render: function () {
            var className = this.props.className,
                onClick = this.props.onClick,
                other = _.omit(this.props, ['className', 'onClick']);

            return (
                <button {...other} className={"btn " + className} onClick={onClick}>{this.props.children}</button>
            );
        }
    });

    /**
     * {@link https://github.com/facebook/react/blob/master/examples/jquery-bootstrap/js/app.js}
     */
    FCom.Components.Modal = React.createClass({
        // The following methods are the only places we need to
        // integrate with Bootstrap or jQuery!
        componentDidMount: function () {
            // When the component is added, turn it into a modal
            $(this.getDOMNode())
                .modal({backdrop: 'static', keyboard: false, show: false});
            if (this.props.show) {
                this.open();
            }
            if (this.props.onLoad) {
                this.props.onLoad(this);
            }
        },
        componentDidUpdate: function (prevProps, prevState) {
            if (this.props.show) {
                this.open();
            }
            if (this.props.onUpdate) {
                this.props.onUpdate(this, prevProps, prevState);
            }
        },
        componentWillUnmount: function () {
            $(this.getDOMNode()).off('hidden', this.handleHidden);
        },
        close: function () {
            $(this.getDOMNode()).modal('hide');
        },
        open: function () {
            $(this.getDOMNode()).modal('show');
        },
        render: function () {
            var confirmButton = null;
            var cancelButton = null;

            if (this.props.confirm) {
                confirmButton = (
                    <FCom.Components.Button type="button" onClick={this.handleConfirm}
                                            className={"btn-primary " + (this.props.confirmClass || '')} {...this.props.confirmAttrs}>
                        {this.props.confirm}
                    </FCom.Components.Button>
                );
            }
            if (this.props.cancel) {
                cancelButton = (
                    <FCom.Components.Button onClick={this.handleCancel} className={"btn-default " + (this.props.cancelClass || '')} type="button">
                        {this.props.cancel}
                    </FCom.Components.Button>
                );
            }

            return (
                <div className="modal fade" id={this.props.id}>
                    <div className="modal-dialog">
                        <div className="modal-content">
                            <div className="modal-header">
                                {cancelButton ? <button type="button" className="close" onClick={this.handleCancel}>
                                    &times;
                                </button> : null}
                                <h4 className="modal-title">{this.props.title}</h4>
                            </div>
                            <div className="modal-body">
                                {this.props.children}
                            </div>
                            <div className="modal-footer">
                                {cancelButton}
                                {confirmButton}
                            </div>
                        </div>
                    </div>
                </div>
            );
        },
        handleCancel: function () {
            if (this.props.onCancel) {
                this.props.onCancel(this);
            } else {
                this.close();
            }
        },
        handleConfirm: function () {
            if (this.props.onConfirm) {
                this.props.onConfirm(this);
            } else {
                this.close();
            }
        },
        getDefaultProps: function () {
            // component default properties
            return {
                confirm: Locale._("OK"),
                cancel: Locale._("Cancel"),
                title: Locale._("Title"),
                id: 'fcom-modal-form-wrapper',
                show: false, //show modal after render
                confirmClass: '',
                confirmAttrs: {},
                cancelClass: ''
            }
        }
    });

    var _nextSibling;

    var _activeComponent;

    var _defaultOptions = {
        ref: 'list',
        model: 'items',

        animation: 100,
        onStart: 'handleStart',
        onEnd: 'handleEnd',
        onAdd: 'handleAdd',
        onUpdate: 'handleUpdate',
        onRemove: 'handleRemove',
        onSort: 'handleSort',
        onFilter: 'handleFilter',
        onMove: 'handleMove'
    };


    function _getModelName(component) {
        return component.sortableOptions && component.sortableOptions.model || _defaultOptions.model;
    }


    function _getModelItems(component) {
        var name = _getModelName(component),
            items = component.state && component.state[name] || component.props[name];

        return items.slice();
    }


    function _extend(dst, src) {
        for (var key in src) {
            if (src.hasOwnProperty(key)) {
                dst[key] = src[key];
            }
        }

        return dst;
    }

    FCom.Components.SortableMixin = {
        sortableMixinVersion: '0.1.1',

        /**
         * @type {Sortable}
         * @private
         */
        _sortableInstance: null,

        componentDidMount: function () {
            var DOMNode, options = _extend(_extend({}, _defaultOptions), this.sortableOptions || {}),
                copyOptions = _extend({}, options),

                emitEvent = function (/** string */type, /** Event */evt) {
                    var method = this[options[type]];
                    method && method.call(this, evt, this._sortableInstance);
                }.bind(this);

            // Bind callbacks so that "this" refers to the component
            'onStart onEnd onAdd onSort onUpdate onRemove onFilter onMove'.split(' ').forEach(function (/** string */name) {
                copyOptions[name] = function (evt) {
                    if (name === 'onStart') {
                        _nextSibling = evt.item.nextElementSibling;
                        _activeComponent = this;
                    }
                    else if (name === 'onAdd' || name === 'onUpdate') {
                        evt.from.insertBefore(evt.item, _nextSibling);

                        var newState = {},
                            remoteState = {},
                            oldIndex = evt.oldIndex,
                            newIndex = evt.newIndex,
                            items = _getModelItems(this),
                            remoteItems,
                            item;

                        if (name === 'onAdd') {
                            remoteItems = _getModelItems(_activeComponent);
                            item = remoteItems.splice(oldIndex, 1)[0];
                            items.splice(newIndex, 0, item);

                            remoteState[_getModelName(_activeComponent)] = remoteItems;
                        }
                        else {
                            items.splice(newIndex, 0, items.splice(oldIndex, 1)[0]);
                        }

                        newState[_getModelName(this)] = items;

                        if (copyOptions.stateHandler) {
                            this[copyOptions.stateHandler](newState);
                        } else {
                            this.setState(newState);
                        }

                        (this !== _activeComponent) && _activeComponent.setState(remoteState);
                    }

                    setTimeout(function () {
                        emitEvent(name, evt);
                    }, 0);
                }.bind(this);
            }, this);

            DOMNode = this.getDOMNode() ? (this.refs[options.ref] || this).getDOMNode() : this.refs[options.ref] || this;

            /** @namespace this.refs — http://facebook.github.io/react/docs/more-about-refs.html */
            this._sortableInstance = Sortable.create(DOMNode, copyOptions);
        },

        componentWillReceiveProps: function (nextProps) {
            var newState = {},
                modelName = _getModelName(this),
                items = nextProps[modelName];

            if (items) {
                newState[modelName] = items;
                this.setState(newState);
            }
        },

        componentWillUnmount: function () {
            this._sortableInstance.destroy();
            this._sortableInstance = null;
        }
    };

    /**
     * render modal elements, only support for fcom grid
     */
    FCom.Components.ModalElement = React.createClass({
        mixins: [FCom.Mixin, FCom.FormMixin],
        getDefaultProps: function () {
            return {
                'value': '', //default value
                'column': {}, //column info and option,
                'removeFieldDisplay': false, //remove field button for mass-edit
                'removeFieldHandle': null
            }
        },
        render: function () {
            var that = this;
            var column = this.props.column;

            var label = '';
            var iconRequired = (typeof column['validation'] != 'undefined' && column['validation'].hasOwnProperty('required')) ? '*' : '';
            if (typeof(column['form_hidden_label']) === 'undefined' || !column['form_hidden_label']) {
                label = (
                    <div className="control-label col-sm-3" key={this.props.key}>
                        <label htmlFor={column.name}>
                            {column.label} {iconRequired}
                        </label>
                    </div>
                );
            }

            var validationRules = that.validationRules(column.validation);
            var input = '';
            if (typeof column['element_print'] != 'undefined') { //custom html for element_print
                if (typeof(column['form_hidden_label']) === 'undefined' || !column['form_hidden_label']) {
                    input = '<div class="control-label col-sm-3"><label for=' + column.name + '>' + column.label + '</label></div>';
                }
                input += '<div class="controls col-sm-8">' + column['element_print'] + '</div>';
                return <div key={this.props.key} className="form-group element_print"
                            dangerouslySetInnerHTML={{__html: input}}></div>
            } else {
                switch (column.editor) {
                    case 'select':
                        var options = [];
                        _.forEach(column.options, function (text, value) {
                            options.push(<option value={value} key={value}>{text}</option>);
                        });
                        input = <select key={this.props.key} name={column.name} id={column.name}
                                        className={"form-control " + (column.className ? column.className : '')}
                                        defaultValue={this.props.value} {...validationRules}>{options}</select>;
                        break;
                    case 'textarea':
                        input = <textarea key={this.props.key} name={column.name} id={column.name}
                                          className={"form-control " + (column.className ? column.className : '')}
                                          rows="5" defaultValue={this.props.value} {...validationRules} />;
                        break;
                    default:
                        input = <input key={this.props.key} name={column.name} id={column.name}
                                       className={"form-control " + (column.className ? column.className : '')}
                                       defaultValue={this.props.value} {...column.attributes} {...validationRules} />;
                        break;
                }
            }

            var removeFieldButton = '';
            if (this.props.removeFieldDisplay) {
                removeFieldButton = (<button key={this.props.key}
                                             className="btn box-remove btn-xs btn-link btn-remove remove-field icon-remove"
                                             type="button" onClick={this.props.removeFieldHandle}
                                             data-field={column.name} />);
            }

            return (
                <div className="form-group">
                    {label}
                    <div className="controls col-sm-8">{input}</div>
                    {removeFieldButton}
                </div>
            )
        }
    });

    /**
     * Render select2 element
     */
    FCom.Components.Select2 = React.createClass({
        getDefaultProps: function () {
            return {
                hasError: false,
                errorClass: "has-error",  // default to Bootstrap 3's error class
                multiple: false,
                val: [],
                style: {
                    witdh: "100%"
                },
                enabled: true,
                options: [],
                attrs: {}
            };
        },
        componentDidUpdate: function (prevProps, prevState) {
            if (this._isOptionsUpdated(prevProps.options)) {
                this.createSelect2();
            } else {
                // Change placeholder
                if (prevProps.placeholder !== this.props.placeholder) {
                    this.setPlaceholderTo(this.getElement(), this.props.placeholder);
                }

                // Handle val prop
                var updateVal = false;
                if (prevProps.val.length === this.props.val.length) {
                    $.each(prevProps.val, function (index, value) {
                        if (this.props.val[index] != value) {
                            updateVal = true;
                        }
                    }.bind(this));

                } else {
                    updateVal = true;
                }

                // ...update our val if we need to
                if (updateVal) this.getElement().select2("val", this.props.val);

                // Enable/disable
                if (prevProps.enabled != this.props.enabled) this.getElement().select2("enable", this.props.enabled);
            }
        },
        componentDidMount: function () {
            // Set up Select2
            var $select2 = this.createSelect2();
        },
        setPlaceholderTo: function ($elem, placeholder) {
            var currData = $elem.select2("data");

            // Now workaround the fact that Select2 doesn't pick up on this
            // ..First assign null
            $elem.select2("data", null);

            // ..Then assign dummy value in case that currData is null since
            //   that won't do anything.
            $elem.select2("data", {});

            // ..Then put original data back
            $elem.select2("data", currData);
        },
        createSelect2: function () {
            // Get inital value
            var val = null;
            if (this.props.val.length > 0) {
                val = this.props.multiple ? this.props.val : this.props.val[0];
            }

            var $select2 = this.getElement();
            var options = {
                data: this.props.options,
                multiple: this.props.multiple,
                val: val
            };

            var attrs = {
                'name': this.props.name,
                'class': this.props.className,
                'data-col': this.props['data-col']
            };

            if (this.props.attrs)
                attrs = _.extend({}, attrs, this.props.attrs);

            if (!this.props.multiple)
                options['placeholder'] = this.props.placeholder;

            $select2.attr(attrs)
            .val(val)
            .select2(options)
            .on("change", this.handleChange)
            .on("select2-open", this.handleErrorState)
            .select2("enable", this.props.enabled);

            this.setPlaceholderTo($select2, this.props.placeholder);
        },
        handleErrorState: function () {
            var $dropNode = $('#select2-drop');
            var classNames = $dropNode[0].className.split(/\s+/);

            if (this.props.hasError) {
                var hasErrorClass = $.inArray(this.props.errorClass, classNames);

                if (hasErrorClass == -1) {
                    $dropNode.addClass(this.props.errorClass);
                }

            } else {
                $dropNode.removeClass(this.props.errorClass);
            }
        },
        handleChange: function (e) {
            if (this.props.onSelection) {
                this.props.onSelection(e, this.getElement().select2("data"));
            }
        },
        getElement: function () {
            return $("#" + this.props.id);
        },
        _isOptionsUpdated: function (oldOptions) {
            return oldOptions.length != this.props.options.length || false;
        },
        render: function () {
            return (
                <div>
                    <input id={this.props.id} {...this.props.attrs} type='hidden' style={this.props.style}/>
                </div>
            );
        }
    });

    var cx = React.addons.classSet;
    var RatingStep = React.createClass({
        getDefaultProps: function () {
            return {
                type: 'empty',
                temporaryRating: false,
                stepTitle: {1: 'Bad', 2: 'Poor', 3: 'Ok', 4: 'Good', 5: 'Very good'}
            };
        },
        handleClick: function(e) {
            this.props.onClick(this.props.step, e);
        },
        handleMouseMove: function(e) {
            this.props.onMouseMove(this.props.step, e);
        },
        render: function () {
            var classes = {
                'rating-widget-step': true,
                'rating-widget-step-css': true,
                'rating-widget-step-hover': this.props.temporaryRating
            };
            classes['rating-widget-step-' + this.props.type] = true;

            return (
                <span
                    className={cx(classes)}
                    onClick={this.handleClick}
                    onMouseMove={this.handleMouseMove}
                    title={this.props.stepTitle[this.props.step]}
                />
            );
        }
    });

    FCom.Components.RatingWidget = React.createClass({
        mixins: [FCom.Mixin],
        mouseLastX: 0,
        mouseLastY: 0,
        getDefaultProps: function () {
            return {
                size: 5,
                disabled: false,
                initialRating: 0,
                halfRating: false,
                hover: true,
                className: '',
                onChange: function () {}
            };
        },
        getInitialState: function () {
            return {
                rating: this.props.initialRating,
                tempRating: null
            };
        },
        handleClick: function(newRating, e) {
            if (this.props.disabled) {
                return;
            }

            newRating = this.calcHalfRating(newRating, e);
            if (newRating === this.state.rating) {
                newRating = 0;
            }

            this.setState({rating: newRating, tempRating: null});
            this.props.onChange(newRating);
        },
        handleOnMouseMove: function(newTempRating, e) {
            if (this.props.disabled || !this.props.hover
            ) {
                return;
            }

            // Make sure the mouse has really moved. IE8 thinks the mouse is
            // always moving
            if (
                e.clientX == this.mouseLastX &&
                e.clientY == this.mouseLastY
            ) {
                return;
            }
            this.mouseLastX = e.clientX;
            this.mouseLastY = e.clientY;

            newTempRating = this.calcHalfRating(newTempRating, e);
            this.setState({tempRating: newTempRating})
        },
        handleOnMouseLeave: function() {
            this.setState({tempRating: null});
        },
        calcHalfRating: function(newRating, e) {
            if (!this.props.halfRating) {
                return newRating;
            }

            var stepClicked = e.target;
            var stepWidth = stepClicked.offsetWidth;
            var halfWidth = stepWidth / 2;

            var stepClickedRect = stepClicked.getBoundingClientRect();
            var clickPos = e.pageX - (stepClickedRect.left + document.body.scrollLeft);

            if (clickPos <= halfWidth) {
                newRating -= .5;
            }

            return newRating;
        },
        renderSteps: function() {
            var ratingSteps = [];
            var rating = this.state.tempRating || this.state.rating;

            var roundRating = Math.round(rating);
            var ceilRating = Math.ceil(rating);

            for (var i = 1; i <= this.props.size; i++) {
                var type = 'empty';

                if (i <= rating) {
                    type = 'whole';
                } else if(
                    roundRating == i &&
                    roundRating == ceilRating &&
                    this.props.halfRating
                ) {
                    type = 'half';
                }

                ratingSteps.push(
                    <RatingStep
                        step={i}
                        type={type}
                        temporaryRating={this.state.tempRating !== null}
                        onClick={this.handleClick}
                        onMouseMove={this.handleOnMouseMove}
                        key={"rating-step-" + i}
                    />
                );
            }

            return ratingSteps;
        },
        render: function () {
            var ratingSteps = this.renderSteps();

            var classes = {
                'rating-widget': true,
                'rating-widget-disabled': this.props.disabled
            };
            classes = cx(classes) + ' ' + this.props.className;

            return (
                <div className={classes} onMouseLeave={this.handleOnMouseLeave}>
                    {ratingSteps}
                </div>
            );
        }
    });

    return FCom.Components;
});
