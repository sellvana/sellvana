//noinspection JSPotentiallyInvalidUsageOfThis
define(['react', 'jquery', 'fcom.locale', 'bootstrap', 'underscore'], function (React, $, Locale) {
    FCom.Components = {};

    /**
     * common mixin can be used in both of grid and form
     * @type {{text2html: Function, html2text: Function, fileSizeFormat: Function}}
     */
    FCom.Mixin = {
        text2html: function (val) {
            var text = $.parseHTML(val);
            return (text != null) ? text[0].data: null;
        },
        html2text: function (val) {
            return $('<div/>').text(val).html();
        },
        fileSizeFormat: function (size) {
            var size = parseInt(size);
            if (size / (1024 * 1024) > 1) {
                size = size / (1024 * 1024);
                size = size.toFixed(2) + ' MB';
            } else if (size / 1024 > 1) {
                size = size / 1024;
                size = size.toFixed(2) + ' KB';
            } else {
                size = size + ' Byte';
            }

            return size;
        },
        dateTimeNow: function () {
            var d = new Date();
            var dateTime = d.getFullYear() + '-' + toString((d.getMonth() + 1)) + '-' + toString(d.getDate()) + ' ' + toString(d.getHours()) + ':' + toString(d.getMinutes()) + ':' + toString(d.getSeconds());

            function toString(val) {
                return (val < 10) ? '0' + val : val;
            }

            return dateTime;
        },
        updateModalWidth: function(modal) {
            //todo: add css class to modal to pre-define width, eg: large, medium, small
            $(modal.getDOMNode()).find('.modal-dialog').css('width', '900px');
        },
        /**
         * remove special chars
         * @param {string} str
         */
        removeSpecialChars: function(str) { //todo: put this function to global utilities object
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
                return this.props.id
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
        validationRules: function(data) {
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

    FCom.Components.ControlLabel = React.createClass({displayName: "ControlLabel",
        render: function () {
            var cl = "control-label " + this.props.label_class + (this.props.required ? ' required' : '');
            return (
                React.createElement("label", {className: cl, 
                    htmlFor:  this.props.input_id}, this.props.children)
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

    FCom.Components.HelpBlock = React.createClass({displayName: "HelpBlock",
        render: function () {
            return (React.createElement("span", {className: "help-block "+ this.props.helpBlockClass},  this.props.text));
        }
    });

    FCom.Components.Input = React.createClass({displayName: "Input",
        mixins:[FCom.FormMixin],
        render: function () {
            var formGroupClass = this.props.formGroupClass,
                inputDivClass = this.props.inputDivClass,
                inputClass = this.props.inputClass,
                inputValue = this.props.inputValue,
                other = _.omit(this.props, ['formGroupClass', 'inputDivClass', 'inputClass', 'inputValue']);
            var className = "form-control";
            if(inputClass) {
                className += " " + inputClass;
            }
            if(this.props.required) {
                className += " required";
            }
            var helpBlock = React.createElement("span", null);
            if(this.props['helpBlockText']) {
                helpBlock = React.createElement(FCom.Components.HelpBlock, {text: this.props['helpBlockText']});
            }
        var inputId = this.getInputId();

        return (
                React.createElement("div", {className: "form-group " + formGroupClass}, 
                    React.createElement(FCom.Components.ControlLabel, React.__spread({},  other, {input_id: inputId}), 
                        this.props.label
                    ), 
                    React.createElement("div", {className: inputDivClass}, 
                        React.createElement("input", React.__spread({},  other, 
                            {id: inputId, 
                            name: this.getInputName(), 
                            className: className, 
                            defaultValue: inputValue, 
                            dataRuleRequired:  this.props.required ? "true":''})
                        ), 
                        helpBlock
                    )
                )
            );
        },
        getDefaultProps: function() {
            // component default properties
            return {
                formGroupClass: '',
                inputDivClass: 'col-md-5',
                type: 'text',
                inputId: '',
                inputName: '',
                inputClass:''
            };
        }
    });

    FCom.Components.HelpIcon = React.createClass({displayName: "HelpIcon",
        render: function () {
            return (
                React.createElement("a", {id: this.props.id, className: "pull-right", href: "#", ref: "icon", 
                    "data-toggle": "popover", "data-trigger": "focus", tabIndex: "-1", 
                    "data-content": this.props.content, "data-container": "body"}, 
                    React.createElement("span", {className: "icon-question-sign"})
                )
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

    FCom.Components.YesNo = React.createClass({displayName: "YesNo",
        render: function () {
            return (
                React.createElement("select", {name: this.props.name, id: this.props.id, 
                    className: "form-control to-select2 " + this.props.className, style: this.props.style, 
                    defaultValue: this.props.value}, 
                    React.createElement("option", {value: "0"}, this.props.optNo), 
                    React.createElement("option", {value: "1"}, this.props.optYes)
                )
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

    FCom.Components.Button = React.createClass({displayName: "Button",
        render: function () {
            var className = this.props.className,
                onClick = this.props.onClick,
                other = _.omit(this.props, ['className', 'onClick']);

            return (
                React.createElement("button", React.__spread({},  other, {className: "btn " + className, onClick: onClick}), this.props.children)
            );
        }
    });

    /**
     * {@link https://github.com/facebook/react/blob/master/examples/jquery-bootstrap/js/app.js}
     */
    FCom.Components.Modal = React.createClass({displayName: "Modal",
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
                    React.createElement(FCom.Components.Button, {onClick: this.handleConfirm, className: "btn-primary", type: "button"}, 
                        this.props.confirm
                    )
                );
            }
            if (this.props.cancel) {
                cancelButton = (
                    React.createElement(FCom.Components.Button, {onClick: this.handleCancel, className: "btn-default", type: "button"}, 
                        this.props.cancel
                    )
                );
            }

            return (
                React.createElement("div", {className: "modal fade", id: this.props.id}, 
                    React.createElement("div", {className: "modal-dialog"}, 
                        React.createElement("div", {className: "modal-content"}, 
                            React.createElement("div", {className: "modal-header"}, 
                                React.createElement("button", {type: "button", className: "close", onClick: this.handleCancel}, 
                                "×"
                                ), 
                                React.createElement("h4", {className: "modal-title"}, this.props.title)
                            ), 
                            React.createElement("div", {className: "modal-body"}, 
                                this.props.children
                            ), 
                            React.createElement("div", {className: "modal-footer"}, 
                                  cancelButton, 
                                  confirmButton
                            )
                        )
                    )
                )
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
                show: false //show modal after render
            }
        }
    });

    /**
     * render modal elements, only support for fcom grid
     */
    FCom.Components.ModalElement = React.createClass({displayName: "ModalElement",
        mixins: [FCom.Mixin, FCom.FormMixin],
        getDefaultProps: function() {
            return {
                'value': '', //default value
                'column': {}, //column info and option,
                'removeFieldDisplay': false, //remove field button for mass-edit
                'removeFieldHandle': null
            }
        },
        render: function() {
            var that = this;
            var column = this.props.column;

            var label = '';
            var iconRequired =(typeof column['validation'] != 'undefined' && column['validation'].hasOwnProperty('required')) ? '*' : '';
            if (typeof(column['form_hidden_label']) === 'undefined' || !column['form_hidden_label']) {
                label = (
                    React.createElement("div", {className: "control-label col-sm-3"}, 
                        React.createElement("label", {htmlFor: column.name}, 
                            column.label, " ", iconRequired
                        )
                    )
                );
            }

            var validationRules = that.validationRules(column.validation);
            var input = '';
            if (typeof column['element_print'] != 'undefined') { //custom html for element_print
                if (typeof(column['form_hidden_label']) === 'undefined' || !column['form_hidden_label']) {
                    input = '<div class="control-label col-sm-3"><label for='+column.name+'>'+column.label+'</label></div>';
                }
                input += '<div class="controls col-sm-8">' + column['element_print'] + '</div>';
                return React.createElement("div", {className: "form-group element_print", dangerouslySetInnerHTML: {__html: input}})
            } else {
                switch (column.editor) {
                    case 'select':
                        var options = [];
                        _.forEach(column.options, function(text, value) {
                            options.push(React.createElement("option", {value: value}, text));
                        });
                        input = React.createElement("select", React.__spread({name: column.name, id: column.name, className: "form-control", defaultValue: this.props.value},  validationRules), options);
                        break;
                    case 'textarea':
                        input = React.createElement("textarea", React.__spread({name: column.name, id: column.name, className: "form-control", rows: "5", defaultValue: this.props.value},  validationRules));
                        break;
                    default:
                        input = React.createElement("input", React.__spread({name: column.name, id: column.name, className: "form-control", defaultValue: this.props.value},  validationRules));
                        break;
                }
            }

            var removeFieldButton = '';
            if (this.props.removeFieldDisplay) {
                removeFieldButton = (React.createElement("button", {className: "btn box-remove btn-xs btn-link btn-remove remove-field icon-remove", type: "button", onClick: this.props.removeFieldHandle, "data-field": column.name}));
            }

            return (
                React.createElement("div", {className: "form-group"}, 
                    label, React.createElement("div", {className: "controls col-sm-8"}, input), removeFieldButton
                )
            )
        }
    });

    return FCom.Components;
});
