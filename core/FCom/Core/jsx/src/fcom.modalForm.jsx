/** @jsx React.DOM */

/**
 * FCom ModalForm Component
 */
define(['react', 'griddle.fcomRow', 'fcom.components', 'jquery-ui', 'jquery.validate'], function (React, FComRow, Components) {
	/**
     * form content for modal
     */
    var FComModalForm = React.createClass({
        mixins: [FCom.Mixin, FCom.FormMixin],
        getDefaultProps: function () {
            return {
                'row': {},
                'id': 'modal-form',
                'columnMetadata': []
            }
        },
        getInitialState: function () {
            return {
                isNew: (this.props.row.id > 0)
            }
        },
        componentDidMount: function () {
            //console.log('row', this.props.row);
            var that = this;

            //update value for element is rendered as element_print
            $(this.getDOMNode()).find('.element_print').find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                var value = (typeof that.props.row[name] !== 'undefined') ? that.props.row[name] : '';
                $(this).val(that.text2html(value));
            });
        },
        render: function () {
            var that = this;
            var gridId = this.props.id;
            //console.log('row', this.props.row);

            var nodes = this.props.columnMetadata.map(function(column, index) {
                if( (that.props.row && !column.editable) || (!that.props.row && !column.addable)) return null;
                return <Components.ModalElement column={column} value={that.props.row[column.name]} key={index} />
            });

            //add id
            nodes.push(<input type="hidden" name="id" id="id" value={this.props.row.id} key={nodes.length++} />);

            return (
                <form className="form form-horizontal validate-form" id={gridId + '-modal-form'}>
                    {nodes}
                </form>
            )
        }
    });

	return FComModalForm;
});
