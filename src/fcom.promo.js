define(['react', 'jquery', 'select2', 'bootstrap'], function (React, $) {
    var Button = React.createClass({
            render: function () {
                return (<button className="special-button" onClick={this.handleClick}>{this.props.label}</button>);
            },
            handleClick: function (e) {
                e.preventDefault();
                alert('Button clicked');
            }
        }
    );
    var ControlLabel = React.createClass({
        render: function () {
            var cl = "control-label " + this.props.label_class + (this.props.required ? ' required' : '');
            return (
                <label className={cl}
                    for={ this.props.input_id }>{this.props.children}</label>
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

    var HelpIcon = React.createClass({
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
            return (
                <div className="multi-coupon"></div>
            );
        }
    });

    var UsesBlock = React.createClass({
        render: function () {
            return (
                <div className="row">
                    <ControlLabel input_id={this.props.id_upc}>{this.props.label_upc}</ControlLabel>
                    <HelpIcon id={"help-" + this.props.id_upc} content={this.props.helpTextUpc}/>
                    <div className="col-md-3">
                        <input type="text" id={this.props.id_upc} ref="uses_pc" className="form-control"
                            value={this.state.value_upc}/>
                    </div>
                    <ControlLabel input_id={this.props.id_ut}>{this.props.label_ut}</ControlLabel>
                    <HelpIcon id={"help-" + this.props.id_ut} content={this.props.helpTextUt}/>
                    <div className="col-md-3">
                        <input type="text" id={this.props.id_ut} ref="uses_pc" className="form-control"
                            value={this.state.value_ut}/>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                label_upc: "Uses Per Customer",
                label_ut: "Total Uses",
                id_upc: "coupon_uses_per_customer",
                id_ut: "coupon_uses_total",
                helpTextUpc: "How many times a user can use a coupon?",
                helpTextUt: "How many total times a coupon can be used?"
            };
        },
        getInitialState: function () {
            // component default properties
            return {
                value_upc: '',
                value_ut: ''
            };
        }
    });
    var CouponApp = React.createClass({
        displayName: 'CouponApp',
        render: function () {
            //noinspection BadExpressionStatementJS
            var child = <SingleCoupon />;
            return (
                <div className="form-group">
                    <div className="col-md-5 .col-md-offset-3">
                    {child}
                    </div>
                    <UsesBlock />
                </div>
            );
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

            $couponSelector.on('change', function (e) {
                var selected = $(this).val();
                if (selected == 0) {
                    // revert to original state, No coupon code required
                    $element.html(''); // do it better with React !!!
                } else {
                    // actions on selected Single coupon code
                    // actions on Multiple coupon codes
                    React.render(<CouponApp mode={selected}/>, $element[0]);
                }
            });
        }
    };
    return Promo;
});
