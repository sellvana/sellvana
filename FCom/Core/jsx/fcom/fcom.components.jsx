define(['react', 'jquery', 'fcom.locale', 'bootstrap'], function (React, $, Locale) {
    FCom.Components = {};

    var formMixin = {
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
        }
    };

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

    FCom.Components.HelpBlock = React.createClass({
        render: function () {
            return (<span className={"help-block "+ this.props.helpBlockClass}>{ this.props.text }</span>);
        }
    });

    FCom.Components.Input = React.createClass({
        mixins:[formMixin],
        render: function () {
            var { formGroupClass, inputDivClass, inputClass, inputValue, ...other } = this.props;
            var className = "form-control";
            if(inputClass) {
                className += " " + inputClass;
            }
            if(this.props.required) {
                className += " required";
            }
            var helpBlock = <span/>;
            if(this.props.helpBlockText) {
                helpBlock = <FCom.Components.HelpBlock text={this.props.helpBlockText}/>;
            }
        var inputId = this.getInputId();

        return (
                <div className={"form-group " + formGroupClass}>
                    <FCom.Components.ControlLabel {...other} input_id={inputId}>
                        {this.props.label}
                    </FCom.Components.ControlLabel>
                    <div className={inputDivClass}>
                        <input {...this.props}
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

    FCom.Components.HelpIcon = React.createClass({
        render: function () {
            return (
                <a id={this.props.id} className="pull-right" href="#" ref="icon"
                    data-toggle="popover" data-trigger="focus"
                    data-content={this.props.content} data-container="body">
                    <span className="glyphicon glyphicon-question-sign"></span>
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

    FCom.Components.Button = React.createClass({
        render: function () {
            var { className, onClick, ...other } = this.props;
            return (
                <button {...other} className={"btn " + className} onClick={onClick}>{this.props.children}</button>
            );
        }
    });

    FCom.Components.Modal = React.createClass({
        // The following methods are the only places we need to
        // integrate with Bootstrap or jQuery!
        componentDidMount: function () {
            // When the component is added, turn it into a modal
            var modal =$(this.getDOMNode())
                .modal({backdrop: 'static', keyboard: false, show: false});
            this.props.onLoad(this);
        },
        componentDidUpdate: function (prevProps, prevState) {
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
                    <FCom.Components.Button onClick={this.handleConfirm} className="btn-primary" type="button">
                        {this.props.confirm}
                    </FCom.Components.Button>
                );
            }
            if (this.props.cancel) {
                cancelButton = (
                    <FCom.Components.Button onClick={this.handleCancel} className="btn-default" type="button">
                        {this.props.cancel}
                    </FCom.Components.Button>
                );
            }

            return (
                <div className="modal">
                    <div className="modal-dialog">
                        <div className="modal-content">
                            <div className="modal-header">
                                <button type="button" className="close" onClick={this.handleCancel}>
                                &times;
                                </button>
                                <h3>{this.props.title}</h3>
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
                title: Locale._("Title")
            }
        }
    });

    return FCom.Components;
});
