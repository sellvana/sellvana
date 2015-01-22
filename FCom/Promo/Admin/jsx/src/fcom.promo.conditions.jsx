/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!fcom.components', 'fcom.locale', 'jsx!fcom.promo.common',
    'select2', 'bootstrap', 'moment', 'daterangepicker'], function (React, $, Components, Locale, Common) {

    // what type of condition we have, total amount or quantity
    var ConditionsType = React.createClass({
        render: function () {
            var cls = this.props.select2 ? "to-select2 " : "";
            if (this.props.className) {
                cls += this.props.className;
            }
            return (
                <div className={this.props.containerClass}>
                    <select className={cls}>
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
                totalType: [{id: "qty", label: "TOTAL QTY"}, {id: "amt", label: "TOTAL $Amount"}],
                select2: true,
                containerClass: "col-md-2"
            };
        }
    });

    // condition to apply to the selection of products
    var ConditionsSkuCollection = React.createClass({
        mixins: [Common.removeMixin, Common.select2QueryMixin],
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="skuCollectionType" id="skuCollectionType"> of </ConditionsType>
                    <div className="col-md-2">
                        <input type="hidden" id="skuCollectionIds" ref="skuCollectionIds" className="form-control"/>
                    </div>
                    <div className="col-md-2">
                        <Common.Compare ref="skuCollectionCond" id="skuCollectionCond" />
                    </div>
                    <div className="col-md-1">
                        <input className="form-control pull-left" ref="skuCollectionValue" id="skuCollectionValue" type="text"/>
                    </div>
                </Common.Row>
            );
        },
        getDefaultProps: function () {
            return {
                label: "Sku Collection",
                rowClass: "sku-collection",
                url: 'conditions/products',
                type: 'skus'
            };
        },
        url: '',
        componentDidMount: function () {
            var skuCollectionIds = this.refs.skuCollectionIds;
            this.url = this.props.options.base_url + this.props.url;
            var self = this;
            $(skuCollectionIds.getDOMNode()).select2({
                placeholder: "Choose products",
                multiple: true,
                closeOnSelect: true,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: false,
                formatSelection: function (item) {
                    return item['sku'];
                },
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.text + '">' +
                        '<div class="span2">ID: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: ' + item.text.substr(0, 20);
                    if (item.text.length > 20) {
                        markup += '...';
                    }
                    markup += '</div>' +
                    '<div class="span2">SKU: <strong>' + item.sku + '</strong></div>' +
                    '</div>';

                    return markup;
                },
                query: self.select2query
            });
            $('select.to-select2', this.getDOMNode()).select2();
        },
        onChange: function () {
            //todo collect values
            if(this.props.onUpdate) {
                this.props.onUpdate({"sku": value});
            }
        }
    });

    // condition to apply to products which match the attributes condition configured here
    var ConditionsAttributeCombination = React.createClass({
        mixins: [Common.removeMixin],
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-5">
                        <textarea ref="attributesResume" id="attributesResume"
                            readOnly="readonly" value={this.state.valueText} className="form-control"/>
                    </div>
                    <div className="col-md-4">
                        <Components.Button type="button" className="btn-primary"
                            ref={this.props.configureId} onClick={this.handleConfigure}>Configure</Components.Button>
                    </div>
                </Common.Row>
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
        handleConfigure: function (e) {
            var modal = <Components.Modal onConfirm={this.handleConditionsConfirm}
                title="Product Combination Configuration" onLoad={this.registerModal} onUpdate={this.registerModal}>
                <ConditionsAttributesModalContent  baseUrl={this.props.options.base_url} idVar={this.props.options.id_var}
                    entityId={this.props.options.entity_id} onLoad={this.registerModalContent} />
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
            if(this.props.onUpdate) {
                this.props.onUpdate({"condition": value});
            }
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

    // content of the modal used to configure attribute combination
    var ConditionsAttributesModalContent = React.createClass({
        mixins: [Common.select2QueryMixin],
        render: function () {
            var fieldUrl = this.props.baseUrl + this.props.urlField;
            var paramObj = {};
            paramObj[this.props.idVar] = this.props.entityId;
            return (
                <div className="attribute-combinations form-horizontal">
                    <div className="form-group">
                        <div className="col-md-6">
                            <select ref="combinationType" className="form-control to-select2" id="attribute-combination-type">
                                <option value="0">All Conditions Have to Match</option>
                                <option value="1">Any Condition Has to Match</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <input ref="combinationField" className="form-control"/>
                        </div>
                    </div>
                    {this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        return <ConditionsAttributesModalField label={field.label} url={url} key={field.field}
                            id={field.field} input={field.input} removeField={this.removeField} ref={field.field} onChange={this.elementChange}/>
                    }.bind(this))}
                </div>
            );
        },
        serialize: function () {
            return this.state.values;
        },
        serializeText: function () {
            var text, glue, fieldTexts = [];
            var allShouldMatch = $(this.refs['combinationType'].getDOMNode()).val(); // && or ||
            if (allShouldMatch == 1) {
                glue = " or ";
            } else {
                glue = " and ";
            }

            for (var field in this.refs) {
                if (field == 'combinationType' || field == 'combinationField') {
                    continue;
                }
                if (this.refs[field]) {
                    var ref = this.refs[field];
                    var refText = ref.serializeText();
                    fieldTexts.push(refText);
                }
            }

            text = fieldTexts.join(glue);
            return text;
        },
        addField: function () {
            var fieldCombination = this.refs['combinationField'].getDOMNode();
            var fieldValue = $(fieldCombination).select2("data");
            if (null == fieldValue || fieldValue == []) {
                return;
            }
            var fields = this.state.fields;
            fields.push({label: fieldValue.text, field: fieldValue.id, input: fieldValue.input});
            $(fieldCombination).select2("val", "", false);
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
            var fieldCombination = this.refs['combinationField'];
            var self = this;
            this.url = this.props.baseUrl + this.props.url;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.labelCombinationField,
                multiple: false,
                closeOnSelect: true,
                query: self.select2query,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: false
            }).on('change', this.addField);
            $('.to-select2', this.getDOMNode()).select2().on('change', this.elementChange);
            if (typeof this.props.onLoad == 'function') {
                this.props.onLoad(this);
            }
        },
        shouldUpdate: true,
        shouldComponentUpdate: function () {
            var upd = this.shouldUpdate;
            if (!upd) { // shouldUpdate is one time flag that should be set only specifically and then dismissed
                this.shouldUpdate = true;
            }
            return upd;
        },
        elementChange: function (e) {
            var target = e.target;
            var val = e.val;
            var values = this.state.values;
            values[target.id] = val;
            if (val) {
                this.shouldUpdate = false; // no update needed, just capturing values
                this.setState({values: values});
            }
        }
    });

    var ConditionsAttributesModalField = React.createClass({
        mixins: [Common.select2QueryMixin],
        render: function () {
            var inputType = this.props.input;
            var opts = this.getOpts();
            var fieldId = "fieldCombination." + this.props.id;
            var input = <input className="form-control required" type="text" id={fieldId} ref={fieldId} onChange={this.onChange}/>;
            if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                if (inputType == 'number') {
                    if (this.state.range === false) {
                        input = <input className="form-control required" type="number" step="any" id={fieldId} ref={fieldId} style={{width: "auto"}} onChange={this.onChange}/>;
                    } else {
                        input = <div id={fieldId} ref={fieldId} className="input-group">
                            <input className="form-control required" type="number" step="any" id={fieldId + ".min"} placeholder="Min" style={{width: "50%"}} onChange={this.onChange}/>
                            <input className="form-control required" type="number" step="any" id={fieldId + ".max"} placeholder="Max" style={{width: "50%"}} onChange={this.onChange}/>
                        </div>;
                    }
                } else if (inputType == 'date' || inputType == 'time') {
                    var singleMode = true;
                    if (this.state.range === true) {
                        singleMode = false;
                    }
                    input = <div className="input-group">
                        <span className="input-group-addon">
                            <i className="glyphicon glyphicon-calendar"></i>
                        </span>
                        <input className="form-control required" type="text" id={fieldId} ref={fieldId} dataMode={singleMode} onChange={this.onChange}/>
                    </div>
                }
            } else if (inputType == 'select') {
                input = <input className="form-control required" type="hidden" id={fieldId} ref={fieldId}/>;
            } else if (this.props.bool_inputs.indexOf(inputType) != -1) {
                input = <Components.YesNo  id={fieldId} ref={fieldId} onChange={this.onChange}/>;
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <Common.Compare opts={ opts } id={"fieldCompare." + this.props.id}
                            ref={"fieldCompare." + this.props.id} onChange={this.onCompareChange}/>
                    </div>
                    <div className="col-md-5">{input}</div>
                </Common.Row>
            );
        },
        values: {},
        getOpts: function () {
            var opts = this.props.opts;
            var inputType = this.props.input;
            if (inputType == 'text') {
                opts = opts.concat(this.props.opts_text);
            } else if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                opts = opts.concat(this.props.opts_numeric);
            }
            return opts;
        },
        serialize: function () {
            return this.values;
        },
        serializeText: function () {
            var type = this.getInputType();
            var range = this.state.range;
            var text = this.props.label;
            var opts = this.getOpts();
            var opt = this.refs["fieldCompare." + this.props.id];
            var optext = $(opt.getDOMNode()).val();// getting compare operator from element because it might not of been changed
            for (var i = 0; i < opts.length; i++) {
                var o = opts[i];
                if (o.id == optext) {
                    text += " " + o.label;
                    break;
                }
            }

            var value = this.values["fieldCombination." + this.props.id];
            if (value) {
                if ($.isArray(value)) {
                    value = value.join(", ");
                }

                if (type == 'bool') {
                    value = (value == 0) ? Locale._("No") : Locale._("Yes");
                }
                // todo handle numeric ranges and dates

                text += " " + value;
            }

            return text;
        },
        remove: function () {
            if (this.props.removeField) {
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
                    {id: "is", label: "is"},
                    {id: "is_not", label: "is not"},
                    {id: "empty", label: "has no value"}
                ],
                opts_text: [ // add to base for text fields
                    {id: "contains", label: "contains"}
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
            //$('select.to-select2', this.getDOMNode()).select2().on('change', this.onCompareChange);
        },
        //componentDidUpdate: function () {
        //    this.componentDidMount();
        //},
        getInputType: function () {
            var inputType = this.props.input;
            var type = 'text';
            if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                type = 'numeric';
                if (inputType == 'date' || inputType == 'time') {
                    type = 'date';
                }
            } else if (inputType == 'select') {
                type = 'select';
            } else if (this.props.bool_inputs.indexOf(inputType) != -1) {
                type = 'bool';
            }
            return type;
        },
        onChange: function (e) {
            var type = this.getInputType();

            // only select2 event has e.val, for dom inputs it must be added
            if (!e.val) { // for native inputs, use blur event to capture value
                var $elem = $(e.target);
                e.value = $elem.val();
            } else {
                e.value = e.val;
            }
            //console.log(e);
            if (this.state.range && type == 'numeric') {
                // possibly have min/max value handle them
            } else if (type == 'date' && !this.state.range) {
                // potentially range of dates
            }
            this.values["fieldCombination." + this.props.id] = e.value;
            if (this.props.onChange) {
                this.props.onChange(e);
            }
        },
        onCompareChange: function (e) {
            this.values["fieldCompare." + this.props.id] = e.val;
            if (this.props.numeric_inputs.indexOf(this.props.input) == -1) {
                return;
            }
            var target = e.target;
            var state = {range: false};
            state.range = (target.value == 'between');
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
        url: '',
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
            }).on('change', this.onChange);
        }
    });

    var ConditionsCategories = React.createClass({
        mixins: [Common.removeMixin, Common.select2QueryMixin],
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="catProductsType" id="catProductsType" containerClass="col-md-3"> of products in </ConditionsType>
                    <div className="col-md-3">
                        <input type="hidden" id="catProductsIds" ref="catProductsIds" className="form-control"/>
                    </div>
                    <div className="col-md-2">
                        <Common.Compare ref="catProductsCond" id="catProductsCond" />
                    </div>
                    <div className="col-md-1">
                        <input ref="catProductsValue" id="catProductsValue" type="text" className="form-control pull-left"/>
                    </div>
                </Common.Row>
            );
        },
        getDefaultProps: function () {
            return {
                rowClass: "category-products",
                label: "Categories",
                url: 'conditions/categories',
                type: 'cats'
            };
        },
        url: '',
        componentDidMount: function () {
            var catProductsIds = this.refs.catProductsIds;
            this.url = this.props.options.base_url + this.props.url;
            $(catProductsIds.getDOMNode()).select2({
                placeholder: "Select categories",
                maximumSelectionSize: 4,
                multiple: true,
                closeOnSelect: true,
                query: this.select2query,
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.full_name + '">' +
                        '<div class="span2">ID: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: <strong>' + item.full_name.substr(0, 20);
                    if (item.full_name.length > 20) {
                        markup += '...';
                    }
                    markup += '</strong></div>' +
                    '</div>';

                    return markup;
                }
            });
            $('select.to-select2', this.getDOMNode()).select2();
        },
        onChange: function () {
            //todo collect values
            if(this.props.onUpdate) {
                this.props.onUpdate({"category": value});
            }
        }
    });

    var ConditionTotal = React.createClass({
        mixins: [Common.removeMixin],
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="cartTotalType" id="cartTotalType" totalType={this.props.totalType} onChange={this.onChange}/>
                    <div className="col-md-2">
                        <Common.Compare ref="cartTotalCond" id="cartTotalCond" onChange={this.onChange}/>
                    </div>
                    <div className="col-md-1">
                        <input ref="cartTotalValue" id="cartTotalValue" type="text" className="form-control pull-left" onChange={this.onChange}/>
                    </div>
                </Common.Row>
            );
        },
        getDefaultProps: function () {
            return {
                rowClass: "cart-total",
                totalType: [{id: "qty", label: "QTY OF ITEMS"}, {id: "amt", label: "$ Value/Amount OF ITEMS"}],
                label: "Cart Total",
                type: 'total'
            };
        },
        componentDidMount: function () {
            $('select.to-select2', this.getDOMNode()).select2();
        },
        onChange: function () {
            var value = {};
            value.type = $(this.refs['cartTotalType'].getDOMNode()).val();
            value.filter = $(this.refs['cartTotalCond'].getDOMNode()).val();
            value.value = $(this.refs['cartTotalValue'].getDOMNode()).val();

            if(this.props.onUpdate) {
                this.props.onUpdate({'total': value});
            }
        }
    });

    var ConditionsShipping = React.createClass({
        mixins: [Common.removeMixin],
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-5">
                        <textarea ref="shippingResume" id="shippingResume"
                            readOnly="readonly" value={this.state.valueText} className="form-control"/>
                    </div>
                    <div className="col-md-4">
                        <Components.Button type="button" className="btn-primary pull-left" ref={this.props.configureId}
                            onClick={this.handleConfigure}>Configure</Components.Button>
                    </div>
                </Common.Row>
            );
        },
        getDefaultProps: function () {
            return {
                label: "Destination",
                modalTitle: "Shipping Reward Configuration",
                configureId: "shippingCombinationConfigure",
                type: 'shipping'
            };
        },
        getInitialState: function () {
            return {value: "", valueText: ""};
        },
        modal: null,
        modalContent: null,
        handleConfigure: function (e) {
            var modal = <Components.Modal onConfirm={this.handleShippingConfirm} id={"modal-" + this.props.id} key={"modal-" + this.props.id}
                title={this.props.modalTitle} onLoad={this.openModal} onUpdate={this.openModal}>
                <ConditionsShippingModalContent baseUrl={this.props.options.base_url} onLoad={this.registerModalContent}
                    key={"modal-content-" + this.props.id} />
            </Components.Modal>;

            React.render(modal, this.props.modalContainer.get(0));
        },
        handleShippingConfirm: function (modal) {
            var mc = this.modalContent;
            var value = mc.serialize();
            var valueText = mc.serializeText();
            this.setState({
                valueText: valueText,
                value: value
            });
            modal.close();
            if(this.props.onUpdate) {
                this.props.onUpdate({"shipping": value});
            }
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

    var ConditionsShippingModalContent = React.createClass({
        render: function () {
            var fieldUrl = this.props.baseUrl + this.props.url;
            var paramObj = {};
            paramObj[this.props.idVar] = this.props.entityId;
            return (
                <div className="shipping-combinations form-horizontal">
                    <div className="form-group">
                        <div className="col-md-5">
                            <select ref="combinationType" className="form-control to-select2">
                                <option value="0">All Conditions Have to Match</option>
                                <option value="1">Any Condition Has to Match</option>
                            </select>
                        </div>
                        <div className="col-md-5">
                            <select ref="combinationField" className="form-control">
                                <option value="-1">{this.props.labelCombinationField}</option>
                                {this.props.fields.map(function (field) {
                                    return <option value={field.field} key={field.field}>{field.label}</option>
                                })}
                            </select>
                        </div>
                    </div>
                    {this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        return <ConditionsShippingModalField label={field.label} url={url} key={field.field}
                            id={field.field} ref={field.field} removeField={this.removeField}  onChange={this.elementChange}/>
                    }.bind(this))}
                </div>
            );
        },
        serialize: function () {
            return this.state.values;
        },
        serializeText: function () {
            var text, glue, fieldTexts = [];
            var allShouldMatch = $(this.refs['combinationType'].getDOMNode()).val(); // && or ||
            if (allShouldMatch == 1) {
                glue = " or ";
            } else {
                glue = " and ";
            }

            for (var field in this.refs) {
                if (field == 'combinationType' || field == 'combinationField') {
                    continue;
                }
                if (this.refs[field]) {
                    var ref = this.refs[field];
                    var refText = ref.serializeText();
                    fieldTexts.push(refText);
                }
            }

            text = fieldTexts.join(glue);

            return text;
        },
        addField: function () {
            var fieldCombination = this.refs['combinationField'].getDOMNode();
            var fieldValue = $(fieldCombination).select2("data"); // get selected entry as object
            if (null == fieldValue || fieldValue == [] || fieldValue.id == "-1") {
                return;
            }
            $(fieldCombination).select2("val", "-1", false);// reset to default prompt
            var fields = this.state.fields;
            for (var i in fields) { // loop current state fields and check if new one matches existing one, if so skip it
                if (fields.hasOwnProperty(i)) {
                    var f = fields[i];
                    if (f.field == fieldValue.id) {
                        return;
                    }
                }
            }
            var field = {label: fieldValue.text, field: fieldValue.id};
            //console.log(fields.indexOf(field));
            fields.push(field);
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
                fields: [
                    {label: Locale._("Method"), field: 'methods'},
                    {label: Locale._("Country"), field: 'country'},
                    {label: Locale._("State/Province"), field: 'state'},
                    {label: Locale._("ZIP Code"), field: 'zipcode'}
                ],
                labelCombinationField: Locale._("Add a Field to Condition..."),
                url: "conditions/shipping"
            };
        },
        componentDidMount: function () {
            $(this.refs['combinationField'].getDOMNode()).select2().on("change", this.addField);
            $('select.to-select2', this.getDOMNode()).select2();
            if (typeof this.props.onLoad == 'function') {
                this.props.onLoad(this);
            }
        },
        componentWillMount: function () {
            // todo load fields
        },
        shouldUpdate: true,
        shouldComponentUpdate: function () {
            var upd = this.shouldUpdate;
            if (!upd) { // shouldUpdate is one time flag that should be set only specifically and then dismissed
                this.shouldUpdate = true;
            }
            return upd;
        },
        elementChange: function (e) {
            var target = e.target;
            var val = e.val;
            var values = this.state.values;
            values[target.id] = val;
            if (val) {
                this.shouldUpdate = false; // no update needed, just capturing values
                this.setState({values: values});
            }
        }
    });

    var ConditionsShippingModalField = React.createClass({
        mixins: [Common.select2QueryMixin],
        render: function () {
            var fieldId = "fieldCombination." + this.props.id;
            var input = <input className="form-control" type="hidden" id={fieldId} key={fieldId} ref={fieldId}/>;
            var helperBlock = '';
            if (this.props.id == 'zipcode') {
                helperBlock = <span key={fieldId + '.help'} className="help-block">{this.props.zipHelperText }</span>;
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <Common.Compare opts={this.props.opts} id={"fieldCompare." + this.props.id}
                            ref={"fieldCompare." + this.props.id} onChange={this.onCompareChange}/>
                    </div>
                    <div className="col-md-5">{[input, helperBlock]}</div>
                </Common.Row>
            );
        },
        values: {},
        serialize: function () {
            return this.values;
        },
        serializeText: function () {
            var text = this.props.label;
            var opts = this.props.opts;
            var opt = this.refs["fieldCompare." + this.props.id];
            var optext = $(opt.getDOMNode()).val();// getting compare operator from element because it might not of been changed
            for (var i = 0; i < opts.length; i++) {
                var o = opts[i];
                if (o.id == optext) {
                    text += " " + o.label;
                    break;
                }
            }

            var value = this.values["fieldCombination." + this.props.id];
            if (value) {
                if ($.isArray(value)) {
                    value = value.join(", ");
                }
                text += " " + value;
            }

            return text;
        },
        onChange: function (e) {
            // only select2 event has e.val, for dom inputs it must be added
            if (!e.val) { // for native inputs, use blur event to capture value
                var $elem = $(e.target);
                e.value = $elem.val();
            } else {
                e.value = e.val;
            }

            this.values["fieldCombination." + this.props.id] = e.value;
            if (this.props.onChange) {
                this.props.onChange(e);
            }
        },
        onCompareChange: function (e) {
            this.values["fieldCompare." + this.props.id] = e.val;
        },
        remove: function () {
            if (this.props.removeField) {
                this.props.removeField(this.props.id);
            }
        },
        getDefaultProps: function () {
            return {
                label: Locale._("Unknown"),
                url: "",
                fcLabel: "",
                zipHelperText: "Use .. (e.g. 90000..99999) to add range of zip codes",
                opts: [
                    {id: "in", label: "is one of"},
                    {id: "not_in", label: "is not one of"}
                ]
            };
        },
        url: '',
        componentDidMount: function () {
            var fieldCombination = this.refs['fieldCombination.' + this.props.id];
            var self = this;
            this.url = this.props.url;
            if (this.props.id != 'zipcode') {
                $(fieldCombination.getDOMNode()).select2({
                    placeholder: self.props.fcLabel,
                    maximumSelectionSize: 4,
                    multiple: true,
                    selectOnBlur: false,
                    closeOnSelect: true,
                    query: self.select2query,
                    dropdownCssClass: "bigdrop",
                    dropdownAutoWidth: true,
                    createSearchChoice: function (term) {
                        return {id: term, text: term}
                    },
                    createSearchChoicePosition: function (list, item) {
                        list.unshift(item);
                    },
                    formatSelection: function (item) {
                        return item.id;
                    }
                }).on('change', this.onChange);
            } else {
                $(fieldCombination.getDOMNode()).select2({
                    tags: [],
                    tokenSeparators: [',']
                }).on('change', this.onChange);
            }
        }
    });

    var ConditionsApp = React.createClass({
        displayName: 'ConditionsApp',
        render: function () {
            var children = [];
            var options = this.props.options;
            var mc = this.props.modalContainer;
            var rc = this.removeCondition;
            var cu = this.conditionUpdate;
            for(var type in this.state.data.rules) {
                if(this.state.data.rules.hasOwnProperty(type)) {
                    var rules = this.state.data.rules[type];
                    if($.isFunction(rules.map)) {
                        rules.map(function (field, idx) {
                            var el;
                            var key = type + '-' + idx;
                            switch (type) {
                                case 'sku':
                                    el = <ConditionsSkuCollection onUpdate={cu} data={field} options={options} key={key} id={key} removeCondition={rc}/>;
                                    break;
                                case 'category':
                                    el = <ConditionsCategories onUpdate={cu} options={options} key={key} id={key} data={field} removeCondition={rc}/>;
                                    break;
                                case 'total':
                                    el = <ConditionTotal onUpdate={cu} options={options} key={key} id={key} data={field} removeCondition={rc}/>;
                                    break;
                                case 'combination':
                                    el = <ConditionsAttributeCombination onUpdate={cu} options={options} data={field} modalContainer={mc} key={key} id={key} removeCondition={rc}/>;
                                    break;
                                case 'shipping':
                                    el = <ConditionsShipping onUpdate={cu} options={options} data={field} modalContainer={mc} key={key} id={key} removeCondition={rc}/>;
                                    break;
                            }
                            if (el) {
                                children.push(el);
                            }
                        })
                    } else {
                        console.log(rules, "is not an array");
                    }
                }
            }
            return (
                <div className="conditions">
                    {children}
                </div>
            );
        },
        componentDidMount: function () {
            this.props.conditionType.on('change', this.addCondition);

            $('select.to-select2', this.getDOMNode()).select2();
        },
        componentWillMount: function () {
            var data = this.state.data;

            if (this.props.conditions.length) {
                data = this.props.conditions;
                this.setState({data: data});
            }
        },
        addCondition: function () {
            // add condition data to state
            var $conditionTypes = this.props.conditionType;
            if ($conditionTypes.length == 0) {
                return;
            }

            var conditionType = $conditionTypes.val();
            if (conditionType == -1) {
                return;
            }
            $conditionTypes.select2('val', "-1", false);// reset to placeholder value and do NOT trigger change event
            var data = this.state.data;
            if(!data.rules[conditionType]) {
                data.rules[conditionType] = [];
            }
            //var rule = {type: conditionType, id: conditionType + '-' + this.state.lastConditionId};
            data.rules[conditionType].push({}); // push new empty rule
            this.setState({data: data, lastConditionId: (this.state.lastConditionId + 1)});
        },
        removeCondition: function (conditionId) {
            var data = this.state.data;
            data = data.filter(function (field) {
                return field.id != conditionId;
            });
            this.setState({data: data});
        },
        conditionUpdate: function (data) {
            //todo
        },
        getInitialState: function () {
            return {
                data: {
                    match: '0',
                    rules: {}
                },
                lastConditionId: 0
            };
        }
    });

    return ConditionsApp;
});
