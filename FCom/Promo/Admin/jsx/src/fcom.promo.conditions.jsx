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
                    <select className={cls} defaultValue={this.props.value}>
                        {this.props.totalType.map(function (type) {
                            return <option value={type.id} key={type.id}>{type.label}</option>
                        })}
                    </select>
                    {this.props.children}
                </div>
            );
        },
        value: null,
        onChange: function (e) {
            this.value = $('select', this.getDOMNode()).select2('val');
            if(this.props.onChange) {
                this.props.onChange(e);
            }
        },
        serialize: function () {
            return this.value || $('select', this.getDOMNode()).select2('val');
        },
        getDefaultProps: function () {
            return {
                totalType: [{id: "qty", label: "TOTAL QTY"}, {id: "amt", label: "TOTAL $Amount"}],
                select2: true,
                containerClass: "col-md-2"
            };
        },
        componentDidMount: function () {
            $('select', this.getDOMNode()).select2().on('change', this.onChange);
        }
    });

    // condition to apply to the selection of products
    var ConditionsSkuCollection = React.createClass({
        mixins: [Common.removeMixin, Common.select2QueryMixin],
        render: function () {
            var productId = this.state.sku;
            if($.isArray(productId)) {
                productId = productId.join(",");
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="skuCollectionType" id="skuCollectionType" onChange={this.onChange} value={this.state.type}> of </ConditionsType>
                    <div className="col-md-2">
                        <input type="hidden" id="skuCollectionIds" ref="skuCollectionIds" className="form-control" defaultValue={productId}/>
                    </div>
                    <div className="col-md-2">
                        <Common.Compare ref="skuCollectionCond" id="skuCollectionCond" onChange={this.onChange} value={this.state.filter}/>
                    </div>
                    <div className="col-md-1">
                        <input className="form-control pull-left" ref="skuCollectionValue" id="skuCollectionValue"
                            defaultValue={this.state.value} type="text" onBlur={this.onChange}/>
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
        componentWillMount: function () {
            var propData = this.props.data;
            var state = {
                type: propData.type || null,
                sku: propData.sku || [],
                filter: propData.filter || null,
                value: propData.value || 0
            };
            this.setState(state);
        },
        componentDidMount: function () {
            var skuCollectionIds = this.refs['skuCollectionIds'];
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
                    return item['id'];
                },
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.text + '">' +
                        '<div class="span2">SKU: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: ' + item.text.substr(0, 20);
                    if (item.text.length > 20) {
                        markup += '...';
                    }
                    markup += '</div></div>';

                    return markup;
                },
                initSelection: self.initSelection,
                query: self.select2query
            }).on('change', this.onChange);
            $('select.to-select2', this.getDOMNode()).select2();
        },
        onChange: function () {
            var value = {};
            value.type = this.refs['skuCollectionType'].serialize();
            value.sku = $(this.refs['skuCollectionIds'].getDOMNode()).select2('val');
            value.filter = $(this.refs['skuCollectionCond'].getDOMNode()).val();
            value.value = $(this.refs['skuCollectionValue'].getDOMNode()).val();
            if(this.props.onUpdate) {
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);
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
                        <Components.Button type="button" className="btn-primary" ref={this.props.configureId}
                            onClick={this.handleConfigure}>Configure</Components.Button>
                    </div>
                </Common.Row>
            );
        },
        getInitialState: function () {
            return {value: "", valueText: ""};
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
            var modal = <Components.Modal onConfirm={this.handleConditionsConfirm} onCancel={this.handleConditionsCancel}
                    id={"modal-" + this.props.id} key={"modal-" + this.props.id}
                    title="Product Combination Configuration" onLoad={this.registerModal} onUpdate={this.registerModal}>
                <ConditionsAttributesModalContent  baseUrl={this.props.options.base_url} data={this.state.value}
                    onLoad={this.registerModalContent} key={"modal-content-" + this.props.id} />
            </Components.Modal>;

            React.render(modal, this.props.modalContainer.get(0));
        },
        handleConditionsCancel: function (modal) {
            modal.close();
            var mc = this.modalContent;
            if (this.state.value == "" || this.state.value == []) {
                mc.setState({fields: [], values: {}});
            }
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
            if(this.props.onUpdate) {// update main data field
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);
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
        },
        componentWillMount: function () {
            this.setState({
                value: this.props.data,
                valueText: this.serializeText(this.props.data)
            });
        },
        serializeText: function (value) {
            var text, glue, fieldTexts = [];
            var allShouldMatch = value['match']; // && or ||
            if (allShouldMatch == 'any') {
                glue = " or ";
            } else {
                glue = " and ";
            }

            for (var field in value['fields']) {
                if (!value['fields'].hasOwnProperty(field)) {
                    continue;
                }
                if (value['fields'][field]) {
                    var ref = value['fields'][field];
                    var refText = this.serializeFieldText(ref);
                    fieldTexts.push(refText);
                }
            }

            text = fieldTexts.join(glue);

            return text;
        },
        serializeFieldText: function (field) {
            var text = field.label, type;
            if(['number', 'date', 'time'].indexOf(field.input) != -1) {
                type = 'numeric';
            } else if(field.input == 'text') {
                type = 'text';
            } else if (field.input == 'select') {
                type = 'select';
            } else if (field.input == "yes_no") {
                type = 'bool';
            }

            var opts = ConditionsAttributesModalField.opts(type);
            var optext = field.filter;
            for (var i = 0; i < opts.length; i++) {
                var o = opts[i];
                if (o.id == optext) {
                    text += " " + o.label;
                    break;
                }
            }

            var value = field.value;
            if (value) {
                if ($.isArray(value)) {
                    value = value.join(", ");
                }

                if (type == 'bool') {
                    value = (value == 0) ? Locale._("No") : Locale._("Yes");
                }
                text += " " + value;
            }

            return text;
        }
    });

    // content of the modal used to configure attribute combination
    var ConditionsAttributesModalContent = React.createClass({
        mixins: [Common.select2QueryMixin],
        render: function () {
            var fieldUrl = this.props.baseUrl + this.props.urlField;
            var paramObj = {};
            return (
                <div className="attribute-combinations form-horizontal">
                    <div className="form-group">
                        <div className="col-md-6">
                            <select ref="combinationType" className="form-control to-select2"
                                    id="attribute-combination-type" defaultValue={this.state.match}>
                                <option value="all">All Conditions Have to Match</option>
                                <option value="any">Any Condition Has to Match</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <input ref="combinationField" className="form-control"/>
                        </div>
                    </div>
                    {this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        var data = field.value || [];
                        return <ConditionsAttributesModalField label={field.label} url={url} key={field.field}
                            data={data} filter={field.filter}
                            id={field.field} input={field.input} removeField={this.removeField} ref={field.field} onChange={this.elementChange}/>
                    }.bind(this))}
                </div>
            );
        },
        serialize: function () {
            // serialize all values each time when its requested
            var data = {}, fields = [];
            for (var field in this.refs) {
                if(!this.refs.hasOwnProperty(field) || field == 'combinationField') { // condition name field is reset after each selection, so we can ignore it
                    continue;
                }
                if (field == 'combinationType') {
                    data.match = $(this.refs[field].getDOMNode()).select2('val'); // all || any
                    continue;
                }
                if (this.refs[field]) {
                    var ref = this.refs[field];
                    fields.push(ref.serialize());
                }
            }
            if(fields.length) {
                data.fields = fields;
            }
            return data;
        },
        serializeText: function () {
            var text, glue, fieldTexts = [];
            var allShouldMatch = $(this.refs['combinationType'].getDOMNode()).val(); // all or any
            if (allShouldMatch === 'any') {
                glue = " or ";
            } else {
                glue = " and ";
            }

            for (var field in this.refs) {
                if (!this.refs.hasOwnProperty(field) || field == 'combinationType' || field == 'combinationField') {
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
            var field = {label: fieldValue.text, field: fieldValue.id, input: fieldValue.input};
            if(fieldValue.value) {
                field.value = fieldValue.value;
            }
            fields.push(field);
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
            $('select.to-select2', this.getDOMNode()).select2().on('change', this.elementChange);
            if (typeof this.props.onLoad == 'function') {
                this.props.onLoad(this);
            }
        },
        componentWillMount: function () {
            // load fields from data, they come in form of plain js object
            console.log(this.props.data);
            var state = {values: this.props.data || {}};
            for(var field in this.props.data) {
                if(this.props.data.hasOwnProperty(field)) {
                    if(field == 'fields') {
                        var fields = this.props.data[field].map(function (field) {
                            if(!field.label) {
                                var fieldId = field['field'].split('.');
                                field.label = fieldId[1] || fieldId[0]; // if label is missing use field code instead
                            }
                            return field;
                        });
                        fields = this.state.fields.concat(fields);
                        state.fields = fields;
                    } else if(field == 'match') {
                        // condition should match
                        state.match = this.props.data[field];
                    }
                }
            }
            this.setState(state);
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
        statics: {
            opts: function (type) {
                var opts = [ // base options, for bool and select fields
                        {id: "is", label: "is"},
                        {id: "is_not", label: "is not"},
                        {id: "empty", label: "has no value"}
                    ],
                    opts_text = [ // add to base for text fields
                        {id: "contains", label: "contains"}
                    ],
                    opts_numeric = [ // add to base for numeral fields
                        {id: "lt", label: "is less than"},
                        {id: "lte", label: "is less than or equal"},
                        {id: "gt", label: "is greater than"},
                        {id: "gte", label: "is greater than or equal"},
                        {id: "between", label: "is between"}
                    ];
                if(type == 'text') {
                    return opts.concat(opts_text);
                } else if(type == 'numeric') {
                    return opts.concat(opts_numeric);
                }

                return opts;
            }
        },
        render: function () {
            var inputType = this.props.input;
            var opts = this.getOpts();
            var fieldId = "fieldCombination." + this.props.id;
            var value;
            if(this.props.data && this.props.data.length) {
                if ($.isArray(this.props.data)) {
                    value = this.props.data.join(",");
                } else {
                    value = this.props.data;
                }
            }
            var input = <input className="form-control required" type="text" id={fieldId} ref={fieldId}
                onChange={this.onChange} defaultValue={value}/>;
            if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                if (inputType == 'number') {
                    if (this.state.range === false) {
                        input = <input className="form-control required" type="number" step="any" id={fieldId}
                            ref={fieldId} style={{width: "auto"}} onChange={this.onChange} defaultValue={value}/>;
                    } else {
                        value = this.props.data;
                        var min, max;
                        if(value.length > 0) {
                            min = value[0]
                        }

                        if(value.length > 1) {
                            max = value[1];
                        }
                        input = <div id={fieldId} ref={fieldId} className="input-group">
                            <input className="form-control required" type="number" step="any" id={fieldId + ".min"} ref={"min"}
                                placeholder="Min" style={{width: "50%"}} onChange={this.onChange} defaultValue={min}/>
                            <input className="form-control required" type="number" step="any" id={fieldId + ".max"} ref={"max"}
                                placeholder="Max" style={{width: "50%"}} onChange={this.onChange} defaultValue={max}/>
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
                        <input className="form-control required" type="text" id={fieldId} ref={fieldId}
                            dataMode={singleMode} onChange={this.onChange} defaultValue={value}/>
                    </div>
                }
            } else if (inputType == 'select') {
                input = <input className="form-control required" type="hidden" id={fieldId} ref={fieldId}
                    defaultValue={value}/>;
            } else if (this.props.bool_inputs.indexOf(inputType) != -1) {
                input = <Components.YesNo  id={fieldId} ref={fieldId} onChange={this.onChange} defaultValue={value}/>;
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <Common.Compare opts={ opts } id={"fieldCompare." + this.props.id} value={this.props.filter}
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
            var type = this.getInputType();
            var data = {
                field: this.props.id
            };
            data.filter = this.values["fieldCompare." + this.props.id] || $(this.refs["fieldCompare." + this.props.id].getDOMNode()).val();
            if (this.state.range && type == 'numeric' && !this.values["fieldCombination." + this.props.id]) {
                // if this is between value and there is no value saved for it
                var $min = $(this.refs['min'].getDOMNode());
                var $max = $(this.refs['max'].getDOMNode());
                data.value = [
                    $min.val(),
                    $max.val()
                ];
            } else {
                data.value = this.values["fieldCombination." + this.props.id] || $(this.refs["fieldCombination." + this.props.id].getDOMNode()).val();
            }
            data.label = this.props.label;
            data.input = this.props.input;

            return data;
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

            var data = this.serialize();
            var value = data.value;
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
        componentWillMount: function () {
            var state = {
                range: this.props.filter == 'between'
            };
            this.setState(state);
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
                var id = $elem.attr('id');
                var idArray = id.split('.');
                if(idArray.length > 1) { // id is like field.min/max
                    var minMax = idArray[1]; // min || max
                    // if value is already set in non range mode, it will be scalar, or null if this is first time
                    var value = this.values["fieldCombination." + this.props.id] || [null, null];
                    if (!$.isArray(value)) {
                        //if scalar, dump it and set again
                        value = [null, null];
                    }
                    // min is at index 0, max index 1
                    if ('min' == minMax) {
                        value[0] = e.value;
                    } else {
                        value[1] = e.value;
                    }

                    e.value = value;
                }
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
            var data = this.props.data, s, e;
            var fieldCombination = this.refs["fieldCombination." + this.props.id];
            var mode = fieldCombination.props.dataMode;
            if(!data) {
                var startDate = new Date();
                s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
            } else {
                if(!mode) {
                    // not single picker mode
                    var dates = data.split(" - ");
                    s = dates[0];
                    e = dates[1] || dates[0];
                } else {
                    s = data;
                }
            }
            var $input = $(fieldCombination.getDOMNode());
            var parent = $input.closest('.modal');
            var options = {
                format: 'YYYY-MM-DD',
                startDate: s,
                singleDatePicker: mode,
                parentEl: parent
            };
            if(e) {
                options.endDate = e;
            }
            $input.daterangepicker(options);
            //todo set setStartDate and setEndDate
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
                dropdownAutoWidth: true,
                initSelection: self.initSelection
            }).on('change', this.onChange);
        }
    });

    var ConditionsCategories = React.createClass({
        mixins: [Common.removeMixin, Common.select2QueryMixin],
        render: function () {
            var values = this.props.data;
            var categories = values.category_id;
            if($.isArray(categories)) {
                categories = categories.join(",");
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="catProductsType" id="catProductsType" containerClass="col-md-3" onChange={this.onChange} value={values.type}> of products in </ConditionsType>
                    <input type="hidden" id="catProductsIds" ref="catProductsIds" defaultValue={categories}/>
                    <select id="catProductInclude" ref="catProductInclude" className="to-select2" defaultValue={values.include}>
                        <option value="only_this">{Locale._("Only This")}</option>
                        <option value="include_subcategories">{Locale._("This and sub categories")}</option>
                    </select>
                    <Common.Compare ref="catProductsCond" id="catProductsCond"  onChange={this.onChange} value={values.filter}/>
                    <input ref="catProductsValue" id="catProductsValue" type="text" className="" onBlur={this.onChange} defaultValue={values.value}/>
                </Common.Row>
            );
        },
        getDefaultProps: function () {
            return {
                rowClass: "category-products",
                label: "Categories",
                url: 'conditions/categories',
                type: 'cats',
                include: 'only_this'
            };
        },
        url: '',
        componentDidMount: function () {
            var catProductsIds = this.refs['catProductsIds'];
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
                },
                initSelection: this.initSelection
            });
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15});
        },
        onChange: function () {
            var value = {};
            value.type = this.refs['catProductsType'].serialize();
            value.category_id = $(this.refs['catProductsIds'].getDOMNode()).select2('val');
            value.filter = $(this.refs['catProductsCond'].getDOMNode()).val();
            value.value = $(this.refs['catProductsValue'].getDOMNode()).val();
            value.include = $(this.refs['catProductInclude'].getDOMNode()).val();
            if(this.props.onUpdate) {
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);
            }
        }
    });

    var ConditionTotal = React.createClass({
        mixins: [Common.removeMixin],
        render: function () {
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="cartTotalType" id="cartTotalType" totalType={this.props.totalType} onChange={this.onChange} value={this.props.data.type}/>
                    <Common.Compare ref="cartTotalCond" id="cartTotalCond" onChange={this.onChange} value={this.props.data.filter}/>
                    <input ref="cartTotalValue" id="cartTotalValue" type="text" className="" onBlur={this.onChange} defaultValue={this.props.data.value}/>
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
            value.type = this.refs['cartTotalType'].serialize();
            value.filter = $(this.refs['cartTotalCond'].getDOMNode()).val();
            value.value = $(this.refs['cartTotalValue'].getDOMNode()).val();

            if(this.props.onUpdate) {
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);

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
        handleConfigure: function () {
            var modal = <Components.Modal onConfirm={this.handleShippingConfirm} id={"modal-" + this.props.id} key={"modal-" + this.props.id}
                title={this.props.modalTitle} onLoad={this.openModal} onUpdate={this.openModal}>
                <ConditionsShippingModalContent baseUrl={this.props.options.base_url} onLoad={this.registerModalContent}
                    key={"modal-content-" + this.props.id} data={this.state.value}/>
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
            //console.log(this.state);
            if(this.props.onUpdate) {
                var updateData = {};
                updateData[this.props.id] = value;
                this.props.onUpdate(updateData);
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
        },
        componentWillMount: function() {
            this.setState({value: this.props.data, valueText: this.serializeText(this.props.data)});
            //var data = this.props.data;
            //console.warn("Use provided data to load content", data);
        },
        serializeText: function (value) {
            var text, glue, fieldTexts = [];
            var allShouldMatch = value['match']; // && or ||
            if (allShouldMatch == 'any') {
                glue = " or ";
            } else {
                glue = " and ";
            }

            for (var field in value['fields']) {
                if (!value['fields'].hasOwnProperty(field)) {
                    continue;
                }
                if (value['fields'][field]) {
                    var ref = value['fields'][field];
                    var refText = this.serializeFieldText(ref);
                    fieldTexts.push(refText);
                }
            }

            text = fieldTexts.join(glue);

            return text;
        },
        serializeFieldText: function (field) {
            var text = field.label;
            var opts = ConditionsShippingModalField.opts();
            var optext = field.filter;
            for (var i = 0; i < opts.length; i++) {
                var o = opts[i];
                if (o.id == optext) {
                    text += " " + o.label;
                    break;
                }
            }

            var value = field.value;
            if (value) {
                if ($.isArray(value)) {
                    value = value.join(", ");
                }
                text += " " + value;
            }

            return text;
        }
    });

    var ConditionsShippingModalContent = React.createClass({
        render: function () {
            var fieldUrl = this.props.baseUrl + this.props.url;
            var paramObj = {};
            //paramObj[this.props.idVar] = this.props.entityId;
            return (
                <div className="shipping-combinations form-horizontal">
                    <div className="form-group">
                        <div className="col-md-5">
                            <select ref="combinationType" className="form-control to-select2" defaultValue={this.state.match}>
                                <option value="all">All Conditions Have to Match</option>
                                <option value="any">Any Condition Has to Match</option>
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
                        var data = field.value || [];
                        return <ConditionsShippingModalField label={field.label} url={url} key={field.field} data={data} filter={field.filter}
                            id={field.field} ref={field.field} removeField={this.removeField} onChange={this.elementChange} opts={ConditionsShippingModalField.opts()}/>
                    }.bind(this))}
                </div>
            );
        },
        serialize: function () {
            var data = {}, fields = [];
            for (var field in this.refs) {
                if(!this.refs.hasOwnProperty(field) || field == 'combinationField') {
                    continue;
                }
                if (field == 'combinationType') {
                    data.match = $(this.refs[field].getDOMNode()).select2('val');
                    continue;
                }
                if (this.refs[field]) {
                    var ref = this.refs[field];
                    fields.push(ref.serialize());
                }
            }
            if(fields.length) {
                data.fields = fields;
            }
            return data;
        },
        serializeText: function () {
            var text, glue, fieldTexts = [];
            var allShouldMatch = $(this.refs['combinationType'].getDOMNode()).val(); // && or ||
            if (allShouldMatch == 'any') {
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
            this.updateFields(fieldValue);
        },
        updateFields: function (fieldValue) {
            var fields = this.state.fields;
            for (var i in fields) { // loop current state fields and check if new one matches existing one, if so skip it
                if (fields.hasOwnProperty(i)) {
                    var f = fields[i];
                    if (f.field == fieldValue.id) {
                        if(fieldValue.compare) { // if field is the compare field, update and return
                            f.filter = fieldValue.value;
                            this.setState({fields: fields});
                        }
                        return;
                    }
                }
            }
            var field = {label: fieldValue.text, field: fieldValue.id};
            if(fieldValue.value) {
                field.value = fieldValue.value;
            }
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
            return {fields: [], values: {}, match: 0};
        },
        getDefaultProps: function () {
            return {
                fields: [
                    {label: Locale._("Method"), field: 'methods'},
                    {label: Locale._("Country"), field: 'country'},
                    {label: Locale._("Region"), field: 'region'},
                    {label: Locale._("Post Code"), field: 'postcode'}
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
            // load fields from data, they come in form of plain js object
            console.log(this.props.data);
            this.setState({values: this.props.data || {}});
            for(var field in this.props.data) {
                if(this.props.data.hasOwnProperty(field)) {
                    if(field == 'fields') {
                        var defaultFields = this.props.fields;
                        var fields = this.props.data[field].map(function (field) {
                            if (!field.label) {
                                var fieldId = field['field'];
                                var fieldText = defaultFields.filter(function (el) {
                                    return el.field == fieldId;
                                });
                                if (fieldText.length) {
                                    fieldText = fieldText[0]['label'];
                                } else {
                                    fieldText = 'N/A';
                                }
                                field.label = fieldText;
                            }
                            return field;
                        });
                        fields = this.state.fields.concat(fields);
                        this.setState({fields: fields});
                    } else if(field == 'match') {
                        // condition should match
                        this.setState({match: this.props.data[field]});
                    }
                }
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

    var ConditionsShippingModalField = React.createClass({
        mixins: [Common.select2QueryMixin],
        statics: {
            opts: function () {
                return [
                    {id: "in", label: "is one of"},
                    {id: "not_in", label: "is not one of"}
                ];
            }
        },
        render: function () {
            var fieldId = "fieldCombination." + this.props.id;
            var value;
            if(this.props.data && this.props.data.length) {
                if ($.isArray(this.props.data)) {
                    value = this.props.data.join(",");
                } else {
                    value = this.props.data;
                }
            }
            var input = <input className="form-control" type="hidden" id={fieldId} key={fieldId} ref={fieldId} defaultValue={value}/>;
            var helperBlock = '';
            if (this.props.id == 'postcode') {
                helperBlock = <span key={fieldId + '.help'} className="help-block">{this.props.postHelperText }</span>;
            }
            return (
                <Common.Row rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <Common.Compare opts={this.props.opts} id={"fieldCompare." + this.props.id} value={this.props.filter}
                            ref={"fieldCompare." + this.props.id} onChange={this.onCompareChange}/>
                    </div>
                    <div className="col-md-5">{[input, helperBlock]}</div>
                </Common.Row>
            );
        },
        values: {},
        serialize: function () {
            var data = {
                field: this.props.id
            };
            data.filter = this.values["fieldCompare." + this.props.id] || $(this.refs["fieldCompare." + this.props.id].getDOMNode()).val();
            data.value = this.values["fieldCombination." + this.props.id] || $(this.refs["fieldCombination." + this.props.id].getDOMNode()).val();
            data.label = this.props.label;
            return data;
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

            var value = this.values["fieldCombination." + this.props.id] || $(this.refs["fieldCombination." + this.props.id].getDOMNode()).val();
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

            if (this.props.onChange) {
                this.props.onChange(e);
            }
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
                postHelperText: "Use .. (e.g. 90000..99999) to add range of post codes",
            };
        },
        url: '',
        componentDidMount: function () {
            var fieldCombination = this.refs['fieldCombination.' + this.props.id];
            var self = this;
            this.url = this.props.url;
            if (this.props.id != 'postcode') {
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
                    },
                    initSelection: function (el, callback) {
                        var data = [];
                        $(el.val().split(",")).each(function () {
                            data.push({id: this, text: this});
                        });
                        callback(data);
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
                    if($.isArray(rules)) {
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

            if (this.props.conditions.rules) {
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
            this.setState({data: data, lastConditionId: (this.state.lastConditionId + 1)}, function () {
                this.props.onUpdate(this.state.data);
            });
        },
        removeCondition: function (conditionId) {
            var data = this.state.data;
            var condArray = conditionId.split("-");
            if(condArray.length == 2) {
                var rule = condArray[0], idx = condArray[1];
                data.rules[rule].splice(idx, 1);
                if(data.rules[rule].length == 0) {
                    delete data.rules[rule];
                }
            } else {
                console.log("wrong condition id: " + conditionId);
            }
            //data = data.filter(function (field) {
            //    return field.id != conditionId;
            //});
            this.setState({data: data}, function () {
                this.props.onUpdate(this.state.data);
            });
        },
        conditionUpdate: function (data) {
            //todo
            console.log(data);
            var localData = this.state.data;
            for(var type in data) {
                if(data.hasOwnProperty(type)) {
                    var condArray = type.split("-"); // to keep track of multiple conditions of same type shipping-0, shipping-1 ...
                    if(condArray.length == 2) {
                        var rule = condArray[0], idx = condArray[1];
                        localData.rules[rule][idx] = data[type];
                    } else {
                        console.log("wrong condition id: " + type);
                    }
                }
            }
            this.shouldUpdate = false;
            this.props.onUpdate(localData);
            this.setState({data: localData});
        },
        shouldUpdate: true,
        shouldComponentUpdate: function () {
            var upd = this.shouldUpdate;
            if (!upd) { // shouldUpdate is one time flag that should be set only specifically and then dismissed
                this.shouldUpdate = true;
            }
            return upd;
        },
        getInitialState: function () {
            return {
                data: {
                    rules: {}
                },
                lastConditionId: 0
            };
        }
    });

    return ConditionsApp;
});
