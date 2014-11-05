define(['react', 'jquery', 'select2', 'bootstrap'], function (React, $) {
    FCom.React = {};

    FCom.React.ControlLabel = React.createClass({
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

    FCom.React.HelpIcon = React.createClass({
        render: function () {
            return (
                <div className="col-md-1">
                    <a id={this.props.id} className="pull-right" href="#" ref="icon"
                        data-toggle="popover" data-trigger="focus"
                        data-content={this.props.content} data-container="body">
                        <span className="glyphicon glyphicon-question-sign"></span>
                    </a>
                </div>
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

    FCom.React.Button = React.createClass({
        render: function () {
            var {className, onClick, ...other} = this.props;
            return (
                <button {...other} className={"btn " + className} onClick={onClick}>{this.props.children}</button>
            );
        }
    });

    FCom.React.Modal = React.createClass({
        // The following methods are the only places we need to
        // integrate with Bootstrap or jQuery!
        componentDidMount: function () {
            // When the component is added, turn it into a modal
            $(this.getDOMNode())
                .modal({backdrop: 'static', keyboard: false, show: false})
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
                    <FCom.React.Button onClick={this.handleConfirm} className="btn-primary">
                        {this.props.confirm}
                    </FCom.React.Button>
                );
            }
            if (this.props.cancel) {
                cancelButton = (
                    <FCom.React.Button onClick={this.handleCancel} className="btn-default">
                        {this.props.cancel}
                    </FCom.React.Button>
                );
            }

            return (
                <div className="modal fade">
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
                this.props.onCancel();
            }
        },
        handleConfirm: function () {
            if (this.props.onConfirm) {
                this.props.onConfirm();
            }
        },
        getDefaultProps: function () {
            // component default properties
            return {
                confirm: "OK",
                cancel: "Cancel",
                title: "Title"
            }
        }
    });

    var SingleCoupon = React.createClass({
        render: function () {
            return (
                <div className="single-coupon">
                    <input id={this.props.id} ref={this.props.name} value={this.state.value}
                        className="form-control"/>
                    <span className="help-block">{this.props.helpText}</span>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                id: "model-use_coupon_code_single",
                name: "use_coupon_code_single",
                helpText: "(Leave empty for auto-generate)"
            };
        },
        getInitialState: function () {
            // component default properties
            return {
                value: ''
            };
        }
    });

    var MultiCoupon = React.createClass({
        render: function () {
            var showModal = <FCom.React.Modal ref="showModal" onConfirm={this.handleShowConfirm} onCancel={this.closeShowModal} ></FCom.React.Modal>;
            var generateModal = <FCom.React.Modal ref="generateModal" onConfirm={this.handleGenerateConfirm} onCancel={this.closeGenerateModal} ></FCom.React.Modal>;
            var importModal = <FCom.React.Modal ref="importModal" onConfirm={this.handleImportConfirm} onCancel={this.closeImportModal} ></FCom.React.Modal>;
            return (
                <div className="multi-coupon btn-group">
                    <FCom.React.Button onClick={this.showCodes} className="btn-primary">{this.props.buttonViewLabel}</FCom.React.Button>
                    <FCom.React.Button onClick={this.generateCodes} className="btn-primary">{this.props.buttonGenerateLabel}</FCom.React.Button>
                    <FCom.React.Button onClick={this.importCodes} className="btn-primary">{this.props.buttonImportLabel}</FCom.React.Button>
                    {showModal}
                    {generateModal}
                    {importModal}
                </div>
            );
        },
        closeShowModal: function () {
            this.refs.showModal.close();
        },
        closeGenerateModal: function () {
            this.refs.generateModal.close();
        },
        closeImportModal: function () {
            this.refs.importModal.close();
        },
        getDefaultProps: function () {
            // component default properties
            return {
                buttonViewLabel: "View (100) codes",
                buttonGenerateLabel: "Generate New Codes",
                buttonImportLabel: "Import Existing Codes"
            }
        },
        showCodes: function () {
            // component default properties
            console.log("showCodes");
            this.refs.showModal.open();
        },
        generateCodes: function () {
            // component default properties
            console.log("generateCodes");
            this.refs.generateModal.open();
        },
        importCodes: function () {
            // component default properties
            console.log("importCodes");
            this.refs.importModal.open();
        }
    });

    var UsesBlock = React.createClass({
        render: function () {
            return (
                <div className="row">
                    <FCom.React.ControlLabel input_id={this.props.idUpc}>{this.props.labelUpc}</FCom.React.ControlLabel>
                    <FCom.React.HelpIcon id={"help-" + this.props.idUpc} content={this.props.helpTextUpc}/>
                    <div className="col-md-3">
                        <input type="text" id={this.props.idUpc} ref="uses_pc" className="form-control"
                            value={this.state.valueUpc}/>
                    </div>
                    <FCom.React.ControlLabel input_id={this.props.idUt}>{this.props.labelUt}</FCom.React.ControlLabel>
                    <FCom.React.HelpIcon id={"help-" + this.props.idUt} content={this.props.helpTextUt}/>
                    <div className="col-md-3">
                        <input type="text" id={this.props.idUt} ref="uses_pc" className="form-control"
                            value={this.state.valueUt}/>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                labelUpc: "Uses Per Customer",
                labelUt: "Total Uses",
                idUpc: "coupon_uses_per_customer",
                idUt: "coupon_uses_total",
                helpTextUpc: "How many times a user can use a coupon?",
                helpTextUt: "How many total times a coupon can be used?"
            };
        },
        getInitialState: function () {
            // component default properties
            return {
                valueUpc: '',
                valueUt: ''
            };
        }
    });

    var CouponApp = React.createClass({
        displayName: 'CouponApp',
        render: function () {
            //noinspection BadExpressionStatementJS

            var child = "";

            if (this.state.mode == 1) {
                child = [<SingleCoupon key="single-coupon"/>,<UsesBlock key="uses-block"/>];
            } else if(this.state.mode == 2) {
                child = [<MultiCoupon key="multi-coupon"/>,<UsesBlock key="uses-block"/>]
            }
            return (
                <div className="form-group">
                    <div className="col-md-5 .col-md-offset-3">
                        {child}
                    </div>
                </div>
            );
        },
        getInitialState: function () {
            return {mode: 0};
        },
        componentWillReceiveProps: function (nextProps) {
            this.setState({mode: nextProps.mode});
        },
        componentWillMount: function () {
            this.setState({mode: this.props.mode});
        }
    });


    var Promo = {
        createButton: function () {
            React.render(<Button label="Hello button"/>, document.getElementById('test'));
        },
        init: function (options) {
            var couponSelectId = options.coupon_select_id || "model-use_coupon";
            var $couponSelector = $('#' + couponSelectId);
            if ($couponSelector.length == 0) {
                console.log("Use coupon dropdown not found");
                return;
            }

            var $parent = $couponSelector.closest('.form-group');
            var $element = $("<div class='form-group'/>").appendTo($parent);

            $couponSelector.on('change', function () {
                var selected = $(this).val();
                React.render(<CouponApp mode={parseInt(selected)}/>, $element[0]);
            });
        }
    };
    return Promo;
});
