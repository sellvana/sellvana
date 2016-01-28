/** @jsx React.DOM */

/**
 * FCom ModalForm Component
 */
define(['react', 'griddle.fcomRow', 'fcom.components', 'jquery-ui', 'jquery.validate'], function (React, FComRow, Components) {
	/**
     * form content for modal
     */
    return React.createClass({
        mixins: [FCom.Mixin, FCom.FormMixin],
        getDefaultProps: function () {
            return {
                'row': {},
                'id': 'modal-form',
                'columnMetadata': [],
                'hiddenId': ''
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
                return React.createElement(Components.ModalElement, {column: column, value: that.props.row[column.name] || column.default_value || '', key: index})
            });

            //add id
            nodes.push(React.createElement("input", {type: "hidden", name: "id", id: "id", value: this.props.hiddenId ? this.props.hiddenId : this.props.row.id, key: nodes.length++}));

            return (
                React.createElement("form", {className: "form form-horizontal", id: gridId + '-modal-form', noValidate: true}, 
                    nodes
                )
            )
        }
    });
});
