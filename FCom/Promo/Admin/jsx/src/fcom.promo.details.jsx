define(['jquery', 'react', 'jsx!fcom.components'], function ($, React, Components) {
    var cmsBlocks, customerGroups;
    function getCmsBlocks() {
        if(!cmsBlocks) {
            cmsBlocks = [<option key="1">Test</option>];
        }
        return cmsBlocks;
    }
    var CentralPageApp = React.createClass({
        render: function () {
            var types;
            if(this.props.type === 'cms_block') {
                var cmsOptions = getCmsBlocks();
                types =
                    <div>
                        <Components.ControlLabel input_id={this.props.cmsOptions.id}
                            label_class={this.props.labelClass}>
                            {this.props.cmsOptions.label}<Components.HelpIcon id={"help-" + this.props.cmsOptions.id}
                            content={this.props.cmsOptions.help}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <select defaultValue={this.props.cmsOptions.value} className="form-control">{cmsOptions}</select>
                        </div>
                    </div>
            } else {
                types =[
                    <div className="form-group" key={this.props.textOptions.titleId}>
                        <Components.ControlLabel input_id={this.props.textOptions.titleId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.titleLabel}<Components.HelpIcon id={"help-" + this.props.textOptions.titleId}
                            content={this.props.textOptions.titleHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.titleId} ref={this.props.textOptions.titleId}
                                placeholder={this.props.textOptions.titlePlaceholder} className="form-control"
                                name={this.props.textOptions.titleId} defaultValue={this.props.textOptions.titleValue}/>
                        </div>
                    </div>,
                    <div className="form-group" key={this.props.textOptions.applicationId}>
                        <Components.ControlLabel input_id={this.props.textOptions.applicationId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.applicationLabel}<Components.HelpIcon id={"help-" + this.props.textOptions.applicationId}
                            content={this.props.textOptions.applicationHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.applicationId} ref={this.props.textOptions.applicationId}
                                placeholder={this.props.textOptions.applicationPlaceholder} className="form-control"
                                name={this.props.textOptions.applicationId} defaultValue={this.props.textOptions.applicationValue}/>
                        </div>
                    </div>,
                    <div className="form-group" key={this.props.textOptions.conditionsId}>
                        <Components.ControlLabel input_id={this.props.textOptions.conditionsId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.conditionsLabel}<Components.HelpIcon id={"help-" + this.props.textOptions.conditionsId}
                            content={this.props.textOptions.conditionsHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.conditionsId} ref={this.props.textOptions.conditionsId}
                                placeholder={this.props.textOptions.conditionsPlaceholder} className="form-control"
                                name={this.props.textOptions.conditionsId} defaultValue={this.props.textOptions.conditionsValue}/>
                        </div>
                    </div>,
                    <div className="form-group" key={this.props.textOptions.descriptionId}>
                        <Components.ControlLabel input_id={this.props.textOptions.descriptionId}
                            label_class={this.props.labelClass}>
                            {this.props.textOptions.descriptionLabel}<Components.HelpIcon id={"help-" + this.props.textOptions.descriptionId}
                            content={this.props.textOptions.descriptionHelp}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <input id={this.props.textOptions.descriptionId} ref={this.props.textOptions.descriptionId}
                                placeholder={this.props.textOptions.descriptionPlaceholder} className="form-control"
                                name={this.props.textOptions.descriptionId} defaultValue={this.props.textOptions.descriptionValue}/>
                        </div>
                    </div>
                ]
            }
            return (
                <div className="col-md-offset-1">{types}</div>
            );
        },
        getDefaultProps: function () {
            return {
                cmsOptions:{
                    label: "Block Handle",
                    id: "block-handle",
                    help: "Select a cms block handle",
                    value: ''
                },
                textOptions: {
                    titleLabel: "Promotion Title",
                    titleId: "promotion-title",
                    titleHelp: "Add custom title or leave empty for default",
                    titlePlaceholder: "(USE MAIN TITLE)",
                    titleValue: '',
                    applicationLabel: "Show Application Type",
                    applicationId: "show-application",
                    applicationHelp: "?",
                    applicationPlaceholder: "Coupon Code vs Auto Apply (Show Code if applies)",
                    applicationValue: '',
                    conditionsLabel: "Show Conditions",
                    conditionsId: "show-conditions",
                    conditionsHelp: "(USE MAIN DESC) or add custom text",
                    conditionsPlaceholder: "(USE MAIN DESC)",
                    conditionsValue: '',
                    descriptionLabel: "Show Description",
                    descriptionId: "show-description",
                    descriptionHelp: "(USE MAIN DESC) or add custom text",
                    descriptionPlaceholder: "(USE MAIN DESC)",
                    descriptionValue: ''
                },
                labelClass: "col-md-3"
            }
        }
    });
    function renderCentralPageApp(properties, container) {
        React.render(<CentralPageApp {...properties} id="central-page-app"/>, container);
    }
    return {
        initCentralPageApp: function (options) {
            var $selector = options.selector;
            if(!$selector) {
                console.warn("Display type selector not found");
                return;
            }
            var type = $selector.val();
            renderCentralPageApp({type: type}, options.container);
            $selector.on('change', function (e) {
                e.preventDefault();
                var type = $(this).val();
                renderCentralPageApp({type: type}, options.container);
            });
        }
    }
});
