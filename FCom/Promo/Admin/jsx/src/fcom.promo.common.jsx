define(['react', 'jsx!fcom.components'], function (React, Components) {
    var Common = {
        DelBtn: React.createClass({
            render: function () {
                return (
                    <Components.Button className="btn-link btn-delete" onClick={this.props.onClick}
                        type="button" style={ {paddingRight: 10, paddingLeft: 10} }>
                        <span className="icon-trash"></span>
                    </Components.Button>
                );
            }
        }),
        Row: React.createClass({
            render: function () {
                var cls = "form-group condition";
                if (this.props.rowClass) {
                    cls += " " + this.props.rowClass;
                }
                return (<div className={cls}>
                    <div className="col-md-3">
                        <Components.ControlLabel label_class="pull-right">{this.props.label}
                            <Common.DelBtn onClick={this.props.onDelete}/>
                        </Components.ControlLabel>
                    </div>
                {this.props.children}
                </div>);
            }
        })
    };
    return Common;
});
