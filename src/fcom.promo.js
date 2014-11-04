define(['react', 'jquery'], function (React, $) {
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

    var CouponApp = React.createClass({
        render: function () {

        }
    });

    var Promo = {
        createButton: function () {
            React.render(<Button label="Hello button"/>, document.getElementById('test'));
        },
        init: function (options) {
            var couponSelectId = options.coupon_select_id || "model-use_coupon";
            var $couponSelector = $('#' + couponSelectId);
            alert($couponSelector);
            $couponSelector.on('change', function (e) {
                var selected = $(this).val();
                if(selected == 0) {
                    // revert to original state, No coupon code required
                } else if(selected == 1) {
                    // actions on selected Single coupon code
                } else if(selected == 2) {
                    // actions on Multiple coupon codes
                }
            });
        }
    };
    return Promo;
});
