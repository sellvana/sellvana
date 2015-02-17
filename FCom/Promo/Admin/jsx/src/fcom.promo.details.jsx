define(['jquery', 'react', 'jsx!fcom.components'], function ($, React, Components) {
    var cmsBlocks, customerGroups;
    function getCmsBlocks(url, callback) {
        $.get(url).done(callback);
    }
    var CentralPageApp = React.createClass({
        getCmsOptions: function() {
            if (!cmsBlocks) {
                var self = this;
                var url = this.props.base_url + '/' + this.props.cmsBlocksUrl;
                getCmsBlocks(url, function (result) {
                    if(result.items) {
                        cmsBlocks = result.items.map(function (item) {
                            return <option key={item.id} value={item.text}>{item.text}</option>
                        });
                        //cmsBlocks.unshift(<option key="0" value="">Select block handle</option>);
                        self.forceUpdate();
                    }
                });
                //cmsBlocks = [<option key="1">Test</option>];
            }
            return cmsBlocks;
        },
        render: function () {
            var types;
            if(this.props.type === 'cms_block') {
                var cmsOptions = this.getCmsOptions(), value;
                if (this.props.values) {
                    value = this.props.values[this.props.cmsOptions.id];
                }
                types =
                    <div>
                        <Components.ControlLabel input_id={this.props.cmsOptions.id}
                            label_class={this.props.labelClass}>
                            {this.props.cmsOptions.label}<Components.HelpIcon id={"help-" + this.props.cmsOptions.id}
                            content={this.props.cmsOptions.help}/>
                        </Components.ControlLabel>
                        <div className="col-md-5">
                            <select ref={this.props.cmsOptions.id} id={this.props.cmsOptions.id}
                                defaultValue={value} className="form-control">{cmsOptions}</select>
                        </div>
                    </div>
            } else {
                var titleVal,applicationVal, conditionsVal,descriptionVal;
                if(this.props.values) {
                    titleVal = this.props.values['text_options'][this.props.textOptions.titleId];
                    applicationVal = this.props.values['text_options'][this.props.textOptions.applicationId];
                    conditionsVal = this.props.values['text_options'][this.props.textOptions.conditionsId];
                    descriptionVal = this.props.values['text_options'][this.props.textOptions.descriptionId];
                }
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
                                onChange={this.props.onTextChange}
                                name={this.props.textOptions.titleId} defaultValue={titleVal}/>
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
                                onChange={this.props.onTextChange}
                                name={this.props.textOptions.applicationId} defaultValue={applicationVal}/>
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
                                onChange={this.props.onTextChange}
                                name={this.props.textOptions.conditionsId} defaultValue={conditionsVal}/>
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
                                onChange={this.props.onTextChange}
                                name={this.props.textOptions.descriptionId} defaultValue={descriptionVal}/>
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
                },
                textOptions: {
                    titleLabel: "Promotion Title",
                    titleId: "promotion-title",
                    titleHelp: "Add custom title or leave empty for default",
                    titlePlaceholder: "(USE MAIN TITLE)",
                    applicationLabel: "Show Application Type",
                    applicationId: "show-application",
                    applicationHelp: "?",
                    applicationPlaceholder: "Coupon Code vs Auto Apply (Show Code if applies)",
                    conditionsLabel: "Show Conditions",
                    conditionsId: "show-conditions",
                    conditionsHelp: "(USE MAIN DESC) or add custom text",
                    conditionsPlaceholder: "(USE MAIN DESC)",
                    descriptionLabel: "Show Description",
                    descriptionId: "show-description",
                    descriptionHelp: "(USE MAIN DESC) or add custom text",
                    descriptionPlaceholder: "(USE MAIN DESC)"
                },
                labelClass: "col-md-3",
                cmsBlocksUrl: 'conditions/cmsblocks'
            }
        },
        componentDidMount: function () {
            this.initCmsBlockSelect();
        },
        componentDidUpdate: function () {
            this.initCmsBlockSelect();
        },
        initCmsBlockSelect: function () {
            var cmsSelect = this.refs[this.props.cmsOptions.id];
            if (cmsSelect) {
                $(cmsSelect.getDOMNode())
                    .select2({minimumResultsForSearch: 15})
                    .on('change', this.props.onCmsChange).select2('val', this.props.values[this.props.cmsOptions.id]);
            }
        }
    });
    function renderCentralPageApp(properties, container) {
        React.render(<CentralPageApp {...properties} id="central-page-app"/>, container);
    }

    return {
        initCentralPageApp: function (options) {
            var $selector = options.selector, $dataSerialized = $('#' + options.promo_serialized);
            if(!$selector.length) {
                console.warn("Display type selector not found");
                return;
            } else if(!$dataSerialized.length) {
                console.warn("Data serialized field not found");
                return;
            }

            var val = $dataSerialized.val();
            if (val) {
                try {
                    options.data = JSON.parse(val);
                } catch (e) {
                    console.log(e);
                }
            }
            function updateDataSerialized() {
                if ($dataSerialized.length) {
                    var values = options.data;
                    $dataSerialized.val(JSON.stringify(values));
                } else {
                    console.error("Cannot find serialized options element");
                }
            }

            var type = $selector.val();
            var centralAppProps = {
                type: type,
                base_url: options.base_url,
                values: options.data['display_type_details'],
                onCmsChange: function (e) {
                    console.log(e);
                    var val = e.val; // select2 sets new value in val field of event
                    if(!val) {
                        val = $(e.target).val();
                    }
                    if (options.data['display_type_details']) {
                        options.data['display_type_details']['block-handle'] = val;
                    } else {
                        options.data['display_type_details'] = {'block-handle': val};
                    }
                    updateDataSerialized();
                },
                onTextChange: function (e) {
                    var $el = $(e.target);
                    var id = $el.attr('id');
                    var val = $el.val();
                    console.log(id, val);
                    var data = options.data['display_type_details'] || {};
                    var textOptions = data['text_options'] || {};
                    textOptions[id] = val;
                    if (options.data['display_type_details']) {
                        options.data['display_type_details']['text_options'] = textOptions;
                    } else {
                        options.data['display_type_details'] = {'text_options': textOptions};
                    }
                    updateDataSerialized();
                }
            };
            /*
            {
                display_type_details: {
                    block-handle: handle
                    promotion-title:
                    show-application:
                    show-conditions:
                    show-description:
                }
            }
            */
            renderCentralPageApp(centralAppProps, options.container);
            $selector.on('change', function (e) {
                e.preventDefault();
                centralAppProps.type = $(this).val();
                centralAppProps.values = options.data['display_type_details'];
                renderCentralPageApp(centralAppProps, options.container);
            });
        }
    }
});
