/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!griddle', 'jsx!fcom.components', 'fcom.locale', 'select2', 'bootstrap'], function (React, $, Griddle, Components, Locale) {
    var SingleCoupon = React.createClass({
        render: function () {
            return (
                <div className="single-coupon">
                    <Components.ControlLabel input_id={this.props.id}>
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
                    <div id="coupon-generate-container" ref="formContainer" >
                        <Components.Input field="code_pattern" label={Locale._("Code Pattern")}
                            helpBlockText={Locale._("(Leave empty to auto-generate)")}
                            inputDivClass='col-md-8' label_class='col-md-4'/>
                        <Components.Input field="code_length" label={Locale._("Coupon Code Length")}
                            helpBlockText={Locale._("(Will be used only if auto-generating codes)")}
                            inputDivClass='col-md-8' label_class='col-md-4'/>
                        <Components.Input field="coupon_count" label={Locale._("How many to generate")}
                            inputDivClass='col-md-8' label_class='col-md-4' inputValue="1" required/>
                        <div className="formg-group">
                            <Components.Button type="button" id="coupon-generate-btn" onClick={this.props.onSubmit}
                                className="btn-danger btn-post">{Locale._("Generate")}</Components.Button>
                            <span style={{display: 'none', marginLeft: 20}} className="loading">Loading ... </span>
                            <span style={{display: 'none', marginLeft: 20}} className="result"></span>
                        </div>
                    </div>
                </div>
            );
        }
    });

    var MultiCoupon = React.createClass({
        render: function () {
            var showModal = <Components.Modal ref="showModal" onConfirm={this.handleShowConfirm}
                onCancel={this.closeShowModal} url={this.props.showCouponsurl} title="Coupon grid"/>;
            var generateModal = <Components.Modal ref="generateModal" onConfirm={this.handleGenerateConfirm}
                onCancel={this.closeGenerateModal} url={this.props.generateCouponsurl} title="Generate coupons">
                    <GenerateForm ref="generateForm" onSubmit={this.postGenerate}/>
                </Components.Modal>;
            var importModal = <Components.Modal ref="importModal" onConfirm={this.handleImportConfirm}
                onCancel={this.closeImportModal} url={this.props.importCouponsurl} title="Import coupons"/>;
            return (
                <div className="multi-coupon col-md-offset-2" style={{marginBottom: 15}}>
                    <div className="btn-group">
                        <Components.Button onClick={this.showCodes} className="btn-primary" type="button">{this.props.buttonViewLabel}</Components.Button>
                        <Components.Button onClick={this.generateCodes} className="btn-primary" type="button">{this.props.buttonGenerateLabel}</Components.Button>
                        <Components.Button onClick={this.importCodes} className="btn-primary" type="button">{this.props.buttonImportLabel}</Components.Button>
                    </div>
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
                buttonViewLabel: Locale._("View (100) codes"),
                buttonGenerateLabel: Locale._("Generate New Codes"),
                buttonImportLabel: Locale._("Import Existing Codes"),
                showCouponsUrl:"",
                generateCouponsUrl:"",
                importCouponsUrl:""
            }
        },
        loadModalContent: function ($modalBody, url, success) {
            if ($modalBody.length > 0 && $modalBody.data('content-loaded') == undefined) {
                $.get(url).done(function (result) {
                    if (result.hasOwnProperty('html')) {
                        $modalBody.html(result.html);
                        //$modalBody.data('content-loaded', true)
                        if(typeof success == 'function'){
                            success($modalBody);
                        }
                    }
                }).fail(function(result){
                    var jsonResult = result.responseJSON;
                    if (jsonResult.hasOwnProperty('html')) {
                        $modalBody.html(jsonResult.html);
                    }
                });
            }
        },
        showCodes: function () {
            // component default properties
            console.log("showCodes");
            this.refs.showModal.open();
            var $modalBody = $('.modal-body', this.refs.showModal.getDOMNode());
            this.loadModalContent($modalBody, this.props.showCouponsUrl)
        },
        generateCodes: function () {
            // component default properties
            console.log("generateCodes");
            this.refs.generateModal.open();
            var $formContainer = $(this.refs.generateForm.refs.formContainer.getDOMNode());
            //var $formContainer = $(this.refs.generateForm).find('#coupon-generate-container');
            var $codeLength = $formContainer.find('#model-code_length');
            var $codePattern = $formContainer.find('#model-code_pattern');
            if ($.trim($codePattern.val()) == '') { // code length should be settable only if no pattern is provided
                $codeLength.prop('disabled', false);
            }
            $codePattern.change(function (e) {
                console.log(e);
                var val = $.trim($codePattern.val());
                if (val == '') {
                    $codeLength.prop('disabled', false);
                } else {
                    $codeLength.prop('disabled', true);
                    $codePattern.val(val);
                }
            });
        },
        postGenerate: function(e){
            var $formContainer = $(this.refs.generateForm.refs.formContainer.getDOMNode());
            //var $button = $form.find('button.btn-post');
            console.log(e, $formContainer);
            var url = this.props.generateCouponsUrl;
            var $progress = $formContainer.find('.loading');
            var $result = $formContainer.find('.result').hide();
            $progress.show();
            //$button.click(function (e) {
                e.preventDefault();
                var data = {};
                $formContainer.find('input').each(function(){
                    var $self = $(this);
                    var name = $self.attr('name');
                    data[name] = $self.val();
                });
                // show indication that something happens?
                $.get(url, data)
                    .done(function (result) {
                        var status = result.status;
                        var message = result.message;
                        $result.text(message);
                    })
                    .always(function (r) {
                        $progress.hide();
                        $result.show();
                        // hide notification
                        console.log(r);
                    });
            //});
        },
        importCodes: function () {
            // component default properties
            console.log("importCodes");
            this.refs.importModal.open();
            var $modalBody = $('.modal-body', this.refs.importModal.getDOMNode());
            this.loadModalContent($modalBody, this.props.importCouponsUrl);
        }
    });

    var UsesBlock = React.createClass({
        render: function () {
            return (
                <div className="uses-block" style={{clear: 'both'}}>
                    <Components.ControlLabel input_id={this.props.idUpc}>
                        {this.props.labelUpc}<Components.HelpIcon id={"help-" + this.props.idUpc} content={this.props.helpTextUpc}/>
                    </Components.ControlLabel>
                    <div className="col-md-3">
                        <input type="text" id={this.props.idUpc} ref="uses_pc" className="form-control"
                            value={this.state.valueUpc}/>
                    </div>

                    <Components.ControlLabel input_id={this.props.idUt}>
                        {this.props.labelUt}<Components.HelpIcon id={"help-" + this.props.idUt} content={this.props.helpTextUt}/>
                    </Components.ControlLabel>

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

    var CouponApp = React.createClass({
        displayName: 'CouponApp',
        render: function () {
            //noinspection BadExpressionStatementJS
            var child = "";

            if (this.state.mode == 1) {
                child = [<SingleCoupon key="single-coupon" options={this.props.options}/>,
                    <UsesBlock options={this.props.options} key="uses-block"/>];
            } else if(this.state.mode == 2) {
                var showCouponsUrl = this.props.options.showCouponsUrl ||'',
                    generateCouponsUrl = this.props.options.generateCouponsUrl ||'',
                    importCouponsUrl = this.props.options.importCouponsUrl ||'';
                child = [<MultiCoupon key="multi-coupon" options={this.props.options} importCouponsUrl={importCouponsUrl}
                    generateCouponsUrl={generateCouponsUrl} showCouponsUrl={showCouponsUrl}/>,
                                        <UsesBlock options={this.props.options} key="uses-block"/>]
            }
            return (
                <div className="form-group">
                    <div className="coupon-group">
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
            React.render(<Button label="Hello button"/>, document.getElementById('testbed'));
        },
        createGrid: function() {
            React.render(<Griddle/>, document.getElementById('testbed'));
        },
        init: function (options) {
            var couponSelectId = options.coupon_select_id || "model-use_coupon";
            var $couponSelector = $('#' + couponSelectId);
            if ($couponSelector.length == 0) {
                console.log("Use coupon dropdown not found");
                return;
            }
            var containerID = options.coupon_container_id || "coupon-options";
            var $element = $("#" + containerID);
            var selected = $couponSelector.val();
            if(selected != 0) {
                React.render(<CouponApp mode={parseInt(selected)} options={options}/>, $element[0]);
            }

            $couponSelector.on('change', function () {
                selected = $couponSelector.val();
                React.render(<CouponApp mode={parseInt(selected)} options={options}/>, $element[0]);
            });
        }
    };
    return Promo;
});
