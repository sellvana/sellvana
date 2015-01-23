/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!fcom.components', 'fcom.locale', 'select2', 'bootstrap'], function (React, $, Components, Locale) {
    var labelClass = "col-md-3";
    var SingleCoupon = React.createClass({
        render: function () {
            return (
                <div className="single-coupon form-group">
                    <Components.ControlLabel input_id={this.props.id} label_class={this.props.labelClass}>
                        {this.props.labelText}<Components.HelpIcon id={"help-" + this.props.id} content={this.props.helpText}/>
                    </Components.ControlLabel>
                    <div className="col-md-5">
                        <input id={this.props.id} ref={this.props.name} className="form-control"/>
                        <span className="help-block">{this.props.helpText}</span>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                id: "model-use_coupon_code_single",
                name: "use_coupon_code_single",
                helpText: Locale._("(Leave empty for auto-generate)"),
                labelText: Locale._("Coupon Code")
            };
        },
        getInitialState: function () {
            // component default properties
            return {
                value: ''
            };
        }
    });
    var GenerateForm = React.createClass({
        render: function () {
            return (
                <div className="f-section" id="coupon-generate-container">
                    <div className="well well-sm help-block" style={{fontSize:12}}>
                        <p>{Locale._("You can have unique coupon codes generated for you automatically if you input simple patterns.")}</p>
                        <p>{Locale._("Pattern examples:")}</p>
                        <p><code>&#123;U8&#125;</code>{Locale._(" - 8 upper case alpha chars - will result to something like ")}<code>DKABWJKQ</code></p>
                        <p><code>&#123;l6&#125;</code>{Locale._(" - 6 lower case alpha chars - will result to something like ")}<code>dkabkq</code></p>
                        <p><code>&#123;D4&#125;</code>{Locale._(" - 4 digits - will result to something like ")}<code>5640</code></p>
                        <p><code>&#123;UD5&#125;</code>{Locale._(" - 5 alphanumeric (upper case) - will result to something like ")}<code>GHG76</code></p>
                        <p><code>&#123;ULD5&#125;</code>{Locale._(" - 5 alphanumeric (mixed case) - will result to something like ")}<code>GhG76</code></p>
                        <p><code>CODE-&#123;U4&#125;-&#123;UD6&#125;</code> - <code>CODE-HQNB-8A1NO3</code></p>
                        <p>Locale._("Note: dynamic parts of the code MUST be enclosed in &#123;&#125;")</p>
                    </div>
                    <div id="coupon-generate-container" ref="formContainer" className="form-horizontal">
                        <Components.Input field="code_pattern" label={Locale._("Code Pattern")}
                            helpBlockText={Locale._("(Leave empty to auto-generate)")}
                            inputDivClass='col-md-8' label_class='col-md-4'/>
                        <Components.Input field="code_length" label={Locale._("Coupon Code Length")}
                            helpBlockText={Locale._("(Will be used only if auto-generating codes)")}
                            inputDivClass='col-md-8' label_class='col-md-4'/>
                        <Components.Input field="coupon_count" label={Locale._("How many to generate")}
                            inputDivClass='col-md-8' label_class='col-md-4' inputValue="1" required/>
                        <div className={this.props.groupClass}>
                            <div className="col-md-offset-4">
                                <Components.Button type="button" id="coupon-generate-btn" onClick={this.handleGenerateClick}
                                    className="btn-danger btn-post">{Locale._("Generate")}</Components.Button>
                                <span style={{display: 'none', marginLeft: 20}} className="loading">Loading ... </span>
                                <span style={{display: 'none', marginLeft: 20}} className="result"></span>
                            </div>
                        </div>
                    </div>
                </div>
            );
        },
        handleGenerateClick: function (e) {
            this.props.onSubmit(e);
        },
        getDefaultProps: function () {
            // component default properties
            return {
                groupClass: "form-group"
            }
        }
    });

    var MultiCoupon = React.createClass({
        render: function () {
            return (
                <div className="multi-coupon form-group" style={{margin: "15px 0"}}>
                    <div className="btn-group col-md-offset-3">
                        <Components.Button onClick={this.props.onShowCodes} className="btn-primary" type="button">{this.props.buttonViewLabel}</Components.Button>
                        <Components.Button onClick={this.props.onGenerateCodes} className="btn-primary" type="button">{this.props.buttonGenerateLabel}</Components.Button>
                        <Components.Button onClick={this.props.onImportCodes} className="btn-primary" type="button">{this.props.buttonImportLabel}</Components.Button>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                buttonViewLabel: Locale._("View (100) codes"),
                buttonGenerateLabel: Locale._("Generate New Codes"),
                buttonImportLabel: Locale._("Import Existing Codes")
            }
        }
    });

    var UsesBlock = React.createClass({
        render: function () {
            return (
                <div className="uses-block form-group" style={{clear: 'both'}}>
                    <Components.ControlLabel input_id={this.props.idUpc} label_class={this.props.labelClass}>
                        {this.props.labelUpc}<Components.HelpIcon id={"help-" + this.props.idUpc} content={this.props.helpTextUpc}/>
                    </Components.ControlLabel>
                    <div className="col-md-2">
                        <input type="text" id={this.props.idUpc} ref="uses_pc" className="form-control"
                            value={this.state.valueUpc}/>
                    </div>

                    <Components.ControlLabel input_id={this.props.idUt}>
                        {this.props.labelUt}<Components.HelpIcon id={"help-" + this.props.idUt} content={this.props.helpTextUt}/>
                    </Components.ControlLabel>

                    <div className="col-md-2">
                        <input type="text" id={this.props.idUt} ref="uses_pc" className="form-control"
                            value={this.state.valueUt}/>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                labelUpc: Locale._("Uses Per Customer"),
                labelUt: Locale._("Total Uses"),
                idUpc: "coupon_uses_per_customer",
                idUt: "coupon_uses_total",
                helpTextUpc: Locale._("How many times a user can use a coupon?"),
                helpTextUt: Locale._("How many total times a coupon can be used?")
            };
        },
        getInitialState: function () {
            // component default properties
            return {
                valueUpc: '',
                valueUt: ''
            };
        }, componentWillMount: function () {
            if(this.props.options.valueUpc) {
                this.setState({valueUpc: this.props.options.valueUpc});
            }
            if(this.props.options.valueUt) {
                this.setState({valueUt: this.props.options.valueUt});
            }
        }
    });

    var App = React.createClass({
        displayName: 'CouponApp',
        render: function () {
            //noinspection BadExpressionStatementJS
            var child = "";

            if (this.state.mode == 1) {
                child = [<UsesBlock options={this.props.options} key="uses-block" labelClass={this.props.labelClass}/>,
                    <SingleCoupon key="single-coupon" options={this.props.options} labelClass={this.props.labelClass}/>];
            } else if(this.state.mode == 2) {
                var onShowCodes = this.onShowCodes ||'',
                    onGenerateCodes = this.onGenerateCodes ||'',
                    onImportCodes = this.onImportCodes ||'';
                child = [<UsesBlock options={this.props.options} key="uses-block" labelClass={this.props.labelClass}/>,
                    <MultiCoupon key="multi-coupon" options={this.props.options} onImportCodes={onImportCodes}
                    onGenerateCodes={onGenerateCodes} onShowCodes={onShowCodes} labelClass={this.props.labelClass}/>]
            }
            return (
                <div className="coupon-app">
                    <div className="coupon-group">
                        {child}
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                labelClass: labelClass
            }
        },
        getInitialState: function () {
            return {mode: 0};
        },
        componentWillReceiveProps: function (nextProps) {
            this.setState({mode: nextProps.mode});
        },
        componentWillMount: function () {
            this.setState({mode: this.props.mode});
        },
        onShowCodes: function () {
            return this.props.showCodes();
        },
        onGenerateCodes: function () {
            return this.props.generateCodes();
        },
        onImportCodes: function () {
            return this.props.importCodes();
        }
    });

    var CouponApp = {App, GenerateForm};
    return CouponApp;
});
