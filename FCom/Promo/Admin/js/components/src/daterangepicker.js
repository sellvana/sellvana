/**
 * Created by pp on 4.Nov14.
 */
define(['react'], function (React) {
    var DateRangePicker = React.createClass({
        componentDidMount: function () {
            $(this.getDOMNode()).daterangepicker(
                {
                    format: this.props.format,
                    startDate: this.props.startDate
                }
            )
        },

        render: function () {
            return (<input type="text"/>);
        }
    });
    return DateRangePicker;
});
