/**
 * Created by pp on 22.Dec14.
 */

define(['react', 'jquery', 'jsx!fcom.components', 'jsx!fcom.promo.common', 'fcom.locale', 'select2'], function (React, $, Components, Common, Locale) {
    var Type = React.createClass({
        render: function () {
            var cls = this.props.select2 ? "to-select2 " : "";
            if (this.props.className) {
                cls += this.props.className;
            }
            return (
                <div className={this.props.containerClass}>
                    <select className={cls} onChange={this.props.onChange} defaultValue={this.props.value}>
                        {this.props.totalType.map(function (type) {
                            return <option value={type.id} key={type.id}>{type.label}</option>
                        })}
                    </select>
                    {this.props.children}
                </div>
            );
        },
        getDefaultProps: function () {
            return {
                totalType: [{id: "pcnt", label: "% Off"}, {id: "amt", label: "$ Amount"}],
                select2: true,
                containerClass: "col-md-2",
                className: "form-control"
            };
        }, componentDidMount: function () {
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15}).on('change', this.props.onChange);
        }
    });
    var DiscountDetailsCombination = React.createClass({
        render: function () {
            return (
                <div>
                    <div className="col-md-8">
                        <input type="text" readOnly="readonly" ref="attributesResume" id="attributesResume" className="form-control" value={this.state.valueText}/>
                    </div>
                    <div className="col-md-4">
                        <Components.Button type="button" className="btn-primary"
                            ref={this.props.configureId} onClick={this.handleConfigure}>Configure</Components.Button>
                    </div>
                </div>
            );
        },
        getInitialState: function () {
            return {value: ''};
        },
        getDefaultProps: function () {
            return {
                rowClass: "attr-combination",
                label: "Combination",
                configureId: "attributeCombinationConfigure",
                type: 'comb'
            };
        },
        modal: null,
        modalContent: null,
        handleConfigure: function () {
            var modal = <Components.Modal onConfirm={this.handleConditionsConfirm}
                title="Product Combination Configuration" onLoad={this.registerModal} onUpdate={this.registerModal}>
                <DiscountDetailsCombinationsModalContent  baseUrl={this.props.options.base_url} onLoad={this.registerModalContent} />
            </Components.Modal>;

            React.render(modal, this.props.modalContainer.get(0));
        },
        handleConditionsConfirm: function (modal) {
            var mc = this.modalContent;
            var value = mc.serialize();
            var valueText = mc.serializeText();
            this.setState({
                valueText: valueText,
                value: value
            });
            modal.close();
        },
        registerModal: function (modal) {
            this.modal = modal;
            this.openModal(modal);
        },
        registerModalContent: function (content) {
            this.modalContent = content;
        },
        openModal: function (modal) {
            modal.open();
        }
    });

    var DiscountDetailsCombinationsModalContent = React.createClass({
        mixins: [Common.select2QueryMixin],
        render: function () {
            var fieldUrl = this.props.baseUrl + this.props.urlField;
            var paramObj = {};
            return (
                <div className="attribute-combinations form-horizontal">
                    <div className="form-group">
                        <div className="col-md-5">
                            <select ref="combinationType" className="form-control to-select2" id="attribute-combination-type">
                                <option value="0">All Conditions Have to Match</option>
                                <option value="1">Any Condition Has to Match</option>
                            </select>
                        </div>
                        <div className="col-md-5">
                            <input ref="combinationField" className="form-control"/>
                        </div>
                        <div className="col-md-2">
                            <Common.AddFieldButton onClick={this.addField}/>
                        </div>
                    </div>
                    {this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        return <DiscountDetailsCombinationsModalField label={field.label} url={url} key={field.field}
                            id={field.field} input={field.input} removeField={this.removeField} ref={field.field} onChange={this.elementChange}/>
                    }.bind(this))}
                </div>
            );
        },
        serialize: function () {
            //todo serialize form data
            //var $data = $('input, select', this.getDOMNode());
            //var result = {};
            //$data.each(function (elem) {
            //    //
            //});
            //return result;
            return this.state.values;
        },
        serializeText: function () {
            // todo serialize text for human display
            var text = '';
            var allShouldMatch = $(this.refs['combinationType'].getDOMNode()).val(); // && or ||
            if(allShouldMatch == 1) {
                text += "Any of: ";
            } else {
                text += "all of: ";
            }

            for(var field in this.refs) {
                if(field == 'combinationType' || field == 'combinationField') {
                    continue;
                }
                if(this.refs.hasOwnProperty(field)) {
                    var ref = this.refs[field];
                    text += ref.serializeText();
                }
            }

            text += " should match";
            return text;
        },
        addField: function () {
            var fieldCombination = this.refs.combinationField.getDOMNode();
            var fieldValue = $(fieldCombination).select2("data");
            if(null == fieldValue || fieldValue == []) {
                return;
            }
            var fields = this.state.fields;
            fields.push({label: fieldValue.text, field: fieldValue.id, input: fieldValue.input});
            this.setState({fields: fields});
        },
        removeField: function (id) {
            var fields = this.state.fields;
            fields = fields.filter(function (field) {
                return field.field != id;
            });
            this.setState({fields: fields});
        },
        getInitialState: function () {
            return {fields: [], values: {}};
        },
        getDefaultProps: function () {
            return {
                labelCombinationField: Locale._("Add a Field to Condition..."),
                urlField: "conditions/attributes_field",
                url: 'conditions/attributes_list'
            };
        },
        url: '',
        componentDidMount: function () {
            var fieldCombination = this.refs.combinationField;
            var self = this;
            this.url = this.props.baseUrl + this.props.url;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.labelCombinationField,
                multiple: false,
                closeOnSelect: true,
                query: self.select2query,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: true
            });
            $('.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15}).on('change', this.elementChange);
            if (typeof this.props.onLoad == 'function') {
                this.props.onLoad(this);
            }
        },
        shouldUpdate: true,
        shouldComponentUpdate: function () {
            var upd = this.shouldUpdate;
            if(!upd) { // shouldUpdate is one time flag that should be set only specifically and then dismissed
                this.shouldUpdate = true;
            }
            return upd;
        },
        elementChange: function (e) {
            var target = e.target;
            var val = e.val;
            var values = this.state.values;
            values[target.id] = val;
            if(val) {
                this.shouldUpdate = false; // no update needed, just capturing values
                this.setState({values: values});
            }
        }
    });

    var DiscountDetailsCombinationsModalField = React.createClass({
        mixins:[Common.select2QueryMixin],
        render: function () {
            var inputType = this.props.input;
            var opts = this.getOpts();
            var fieldId = "fieldCombination." + this.props.id;
            var input = <input className="form-control required" type="text" id={fieldId} ref={fieldId}/>;
            if(this.props.numeric_inputs.indexOf(inputType) != -1) {
                if (inputType == 'number') {
                    if(this.state.range === false) {
                        input = <input className="form-control required" type="number" step="any" id={fieldId} ref={fieldId} style={{width: "auto"}}/>;
                    } else {
                        input = <div id={fieldId} ref={fieldId} className="input-group">
                            <input className="form-control required" type="number" step="any" placeholder="Min" style={{width: "50%"}}/>
                            <input className="form-control required" type="number" step="any" placeholder="Max" style={{width: "50%"}}/>
                        </div>;
                    }
                } else if (inputType == 'date' || inputType == 'time') {
                    var singleMode = true;
                    if (this.state.range === true) {
                        singleMode = false;
                    }
                    input = <div className="input-group">
                        <span className="input-group-addon"><i className="glyphicon glyphicon-calendar"></i></span>
                        <input className="form-control required" type="text" id={fieldId} ref={fieldId} dataMode={singleMode} />
                    </div>
                }
            } else if(inputType == 'select'){
                input = <input className="form-control required" type="hidden" id={fieldId} ref={fieldId}/>;
            } else if(this.props.bool_inputs.indexOf(inputType) != -1) {
                input = <Components.YesNo  id={fieldId} ref={fieldId} />;
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <Common.Compare opts={ opts } id={"fieldCompare." + this.props.id} ref={"fieldCompare." + this.props.id} onChange={this.onCompareChange}/>
                    </div>
                    <div className="col-md-5">{input}</div>
                </Common.Row>
            );
        },
        getOpts: function () {
            var opts = this.props.opts;
            if(this.props.input == 'text') {
                opts = opts.concat(this.props.opts_text);
            }

            return opts;
        },
        serializeText: function () {
            var text = this.props.label;
            var opts = this.getOpts();
            var opt = this.refs["fieldCompare." + this.props.id];
            var optext = $(opt.getDOMNode()).val();
            for(var i = 0; i < opts.length; i++) {
                var o = opts[i];
                if(o.id == optext) {
                    text += " " + o.label;
                }
            }


            return text;
        },
        remove: function () {
            if(this.props.removeField) {
                this.props.removeField(this.props.id);
            }
        },
        getInitialState: function () {
            return {
                range: false
            };
        },
        getDefaultProps: function () {
            return {
                label: Locale._("Unknown"),
                url: "",
                fcLabel: "",
                opts: [ // base options, for bool and select fields
                    {id:"is", label: "is"},
                    {id:"is_not", label: "is not"},
                    {id:"empty", label: "has no value"}
                ],
                opts_text:[ // add to base for text fields
                    {id:"contains", label: "contains"}
                ],
                opts_numeric: [ // add to base for numeral fields
                    {id: "lt", label: "is less than"},
                    {id: "lte", label: "is less than or equal"},
                    {id: "gt", label: "is greater than"},
                    {id: "gte", label: "is greater than or equal"},
                    {id: "between", label: "is between"}
                ],
                numeric_inputs: ['number', 'date', 'time'],
                bool_inputs: ['yes_no']
            };
        },
        componentDidMount: function () {
            var inputType = this.props.input;
            switch (inputType) {
                case 'select':
                    this.initSelectInput();
                    break;
                case 'date':
                    this.initDateInput();
                    break;
                default :
                    break;
            }
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15}).on('change', this.props.onChange);
        },
        componentDidUpdate: function () {
            this.componentDidMount();
        },
        onCompareChange: function (e) {
            if(this.props.numeric_inputs.indexOf(this.props.input) == -1){
                return;
            }
            var target = e.target;
            var state = {range: false};
            state.range = (target.value =='between');
            this.setState(state);
        },
        initDateInput: function () {
            var startDate = new Date();
            var s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            var fieldCombination = this.refs["fieldCombination." + this.props.id];
            var $input = $(fieldCombination.getDOMNode());
            var mode = fieldCombination.props.dataMode;
            var parent = $input.closest('.modal');
            $input.daterangepicker(
                {
                    format: 'YYYY-MM-DD',
                    startDate: s,
                    singleDatePicker: mode,
                    parentEl: parent
                }
            );
        },
        url:'',
        initSelectInput: function () {
            var fieldCombination = this.refs["fieldCombination." + this.props.id];
            var self = this;
            this.url = this.props.url;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.fcLabel,
                maximumSelectionSize: 4,
                multiple: true,
                closeOnSelect: true,
                query: this.select2query,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true
            }).on('change', this.props.onChange);
        }
    });

    var DiscountDetails = React.createClass({
        render: function () {
            var details = <span/>;
            if(this.props.type == 'other_prod') {
                details = <input type="hidden" id="otherProd" ref="otherProd" key="otherProd" />
            } else if(this.props.type == 'attr_combination') {
                details = <DiscountDetailsCombination id="attrCombination" ref="attrCombination" key="attrCombination"/>
            }
            return details;
        }
    });
    var Discount = React.createClass({
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <Type ref="discountType" id="discountType" value={this.state.type} onChange={this.onTypeChange}> of </Type>
                    <div className="col-md-3">
                        <input className="form-control pull-left" ref="discountValue" id="discountValue" type="text" defaultValue={this.state.discountValue}/>
                    </div>
                    <div className="col-md-3">
                        <select className="to-select2" ref="discountScope" id="discountScope" onChange={this.onScopeChange}>
                        {this.props.scopeOptions.map(function (type) {
                            return <option value={type.id} key={type.id}>{type.label}</option>
                        })}
                        </select>
                    </div>
                    <div className="col-md-3">
                        <DiscountDetails type={this.state.scope} />
                    </div>
                </Common.Row>
            );
        },
        componentDidMount: function () {
            $('select.to-select', this.getDOMNode()).select2().on('change', function (e) {
                var id = e.target.id;
                if(id == 'discountScope') {
                    this.setState({scope: e.val});
                }
            });
        },
        getDefaultProps: function () {
            return {
                label: Locale._('Discount'),
                rowClass: 'discount',
                scopeOptions: [
                    {id:'whole_order', label: 'Whole Order'},
                    {id:'cond_prod', label: 'Product from Conditions'},
                    {id:'other_prod', label: 'Other SKUs'},
                    {id:'attr_combination', label: 'Combination'}
                ]
            }
        },
        getInitialState: function () {
            return {
                discountValue: 0,
                type: '',
                scope: 'whole_order'
            };
        },
        onScopeChange: function (ev) {
            ev.preventDefault();
            var newScope = $(ev.target).val();
            this.setState({scope: newScope});
        },
        onTypeChange: function (ev) {
            ev.preventDefault();
            var newType = $(ev.target).val();
            this.setState({type: newType});
        }
    });

    var FreeProduct = React.createClass({
        render: function () {
            return (
                <Common.Row >
                    <div className="col-md-3">
                        <input type="hidden" className="form-control" id="productSku" ref="productSku"/>
                    </div>
                    <div className="col-md-3">
                        <Components.ControlLabel input_id="productQty">{Locale._('Qty')}</Components.ControlLabel>
                        <input type="text" className="form-control" id="productQty" ref="productQty" defaultValue={this.state.qty}/>
                    </div>
                    <div className="col-md-3">
                        <Components.ControlLabel input_id="productTerms">{Locale._('Terms')}</Components.ControlLabel>
                        <input type="hidden" className="form-control" id="productTerms" ref="productTerms"/>
                    </div>
                </Common.Row>
            );
        },
        getInitialState: function () {
            return {
                skus: [],
                terms: [],
                qty: 0
            }
        }
    });

    var Shipping = React.createClass({
        mixins: [Common.select2QueryMixin, Common.removeMixin],
        render: function () {
            var amount = '';
            if(this.state.type != 'free') {
                amount = <input type="number" defaultValue={this.state.amount} id="shippingAmount" className="form-control" />
            }
            var type = <Type ref="shippingType" id="shippingType" onChange={this.onTypeChange} value={this.state.type}
                    totalType={this.props.fields}/>;
            var label = <Components.ControlLabel label_class="col-md-1" input_id="shippingMethods">{Locale._('For')}</Components.ControlLabel>;
            var input = <input type="hidden" className="form-control" id="shippingMethods" ref="shippingMethods"/>;
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    {type}
                    <div className="col-md-6">
                        <div className={amount? "col-md-2": ""}>{amount}</div>
                        {label}
                        <div className={amount? "col-md-9": "col-md-11"}>{input}</div>
                        {/*if no amount field, make this wider*/}
                    </div>
                </Common.Row>
            );
        },
        onTypeChange: function (ev) {
            ev.preventDefault();
            var newType = $(ev.target).val();
            this.setState({type: newType});
        },
        getDefaultProps: function () {
            return {
                fields: [
                    {id: "pcnt", label: "% Off"}, {id: "amt", label: "$ Amount Off"}, {id: "free", label:"Free"}
                ],
                labelMethodsField: Locale._("Select shipping methods"),
                url: "conditions/shipping"
            };
        },
        getInitialState: function () {
            return {
                type: 'free',
                methods: [],
                amount: 0
            }
        },
        url: '',
        componentDidMount: function(){
            var shippingMethods = this.refs.shippingMethods;
            var self = this;
            this.url = this.props.options.base_url + '/' + this.props.url + '?'+ $.param({field: 'methods'});
            $(shippingMethods.getDOMNode()).select2({
                placeholder: self.props.labelMethodsField,
                multiple: true,
                query: self.select2query,
                dropdownAutoWidth: true
            });
        }
    });

    return React.createClass({
        render: function () {
            return (<div className="actions panel panel-default">
                    {this.state.data.map(function (field) {
                        //todo make a field based on field
                        var el;
                        var key = field.id;
                        switch(field.type){
                            case 'discount':
                                el = <Discount options={this.props.options} key={key} id={key} removeAction={this.removeAction}
                                    modalContainer={this.props.modalContainer}/>;
                                break;
                            case 'free_product':
                                el = <FreeProduct options={this.props.options} key={key} id={key} removeAction={this.removeAction}/>;
                                break;
                            case 'shipping':
                                el = <Shipping label={Locale._("Shipping")} options={this.props.options} key={key} id={key} removeAction={this.removeAction}/>;
                                break;

                        }
                        return el;
                    }, this)}
            </div> );
        },
        componentDidMount: function () {
            var $conditionsSerialized = $('#'+this.props.options.conditions_serialized);
            var data = this.state.data;

            if ($conditionsSerialized.length > 0) {
                try {
                    data = JSON.parse($conditionsSerialized.val());
                    this.setProps({data: data});
                    // todo actually update state
                } catch (e) {
                    console.log(e);
                }
            }

            $('#' + this.props.newAction).on('click', this.addAction);

            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15});
        },
        addAction: function () {
            // add condition data to state
            var $actionTypes = this.props.actionType;
            if($actionTypes.length == 0) {
                return;
            }

            var actionType = $actionTypes.val();
            var data = this.state.data;
            var condition = {type: actionType, id: actionType + '-' + this.state.lastActionId};
            data.push(condition);
            this.setState({data: data, lastActionId: (this.state.lastActionId + 1)});
        },
        removeAction: function (actionId) {
            var data = this.state.data;
            data = data.filter(function (field) {
                return field.id != actionId;
            });
            this.setState({data: data});
        },
        getInitialState: function () {
            return {
                data: [],
                lastActionId: 0
            };
        }
    });
});
