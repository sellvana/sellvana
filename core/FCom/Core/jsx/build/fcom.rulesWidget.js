/** @jsx React.DOM */

define(['react', 'jquery', 'fcom.components', 'fcom.locale', 'store', 'bootstrap-ladda', 'select2', 'jquery.bootstrap-growl', 'bootstrap-ladda-spin'], function (React, $, Components, Locale, store, Ladda) {
    function conditions(React, $, Components, Locale, Common) {
        // what type of condition we have, total amount or quantity
        var ConditionsType = React.createClass({displayName: "ConditionsType",
            render: function () {
                if (this.props.promoType == 'catalog') {
                    return null;
                }
                var cls = this.props.select2 ? "to-select2 " : "";
                if (this.props.className) {
                    cls += this.props.className;
                }
                return (
                    React.createElement("div", {className: this.props.containerClass}, 
                        React.createElement("select", {className: cls, defaultValue: this.props.value}, 
                        this.props.totalType.map(function (type) {
                            return React.createElement("option", {value: type.id, key: type.id}, type.label)
                        })
                        ), 
                    this.props.children
                    )
                );
            },
            value: null,
            onChange: function (e) {
                this.value = $('select', this.getDOMNode()).select2('val');
                if (this.props.onChange) {
                    this.props.onChange(e);
                }
            },
            serialize: function () {
                var value = '';
                if (this.props.promoType !== 'catalog') {
                    if (this.value) {
                        value = this.value;
                    } else {
                        var $sel = $('select', this.getDOMNode());
                        if ($sel.length) {
                            value = $sel.select2('val');
                        }
                    }
                }
                return value;
            },
            getDefaultProps: function () {
                return {
                    totalType: [{id: "qty", label: "TOTAL QTY"}, {id: "amt", label: "TOTAL $Amount"}],
                    select2: true,
                    containerClass: "col-md-2",
                    promoType: 'cart'
                };
            },
            componentDidMount: function () {
                if (this.props.promoType !== 'catalog') {
                    this.registerSelect2();
                }
            },
            componentDidUpdate: function () {
                if (this.props.promoType !== 'catalog') {
                    this.registerSelect2();
                }
            },
            registerSelect2: function () {
                $('select', this.getDOMNode()).select2().on('change', this.onChange);
            }
        });

        // condition to apply to the selection of products
        var ConditionsSkuCollection = React.createClass({displayName: "ConditionsSkuCollection",
            mixins: [Common.removeMixin, Common.select2QueryMixin],
            render: function () {
                var productId = this.state.sku;
                var promoType = this.props.options.promo_type;
                var display = {
                    display: promoType === 'catalog' ? 'none' : 'inherit'
                };
                var disabled = promoType === 'catalog';
                if ($.isArray(productId)) {
                    productId = productId.join(",");
                }
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement(ConditionsType, {ref: "skuCollectionType", id: "skuCollectionType", onChange: this.onChange, 
                            value: this.state.type, promoType: promoType}, " of "), 
                        React.createElement("div", {className: "col-md-2"}, 
                            React.createElement("input", {type: "hidden", id: "skuCollectionIds", ref: "skuCollectionIds", className: "form-control", defaultValue: productId})
                        ), 
                        React.createElement("div", {className: "col-md-2", style: display}, 
                            React.createElement(Common.Compare, {ref: "skuCollectionCond", id: "skuCollectionCond", onChange: this.onChange, value: this.state.filter, disabled: disabled})
                        ), 
                        React.createElement("div", {className: "col-md-1", style: display}, 
                            React.createElement("input", {className: "form-control pull-left", ref: "skuCollectionValue", id: "skuCollectionValue", 
                                defaultValue: this.state.value, type: "text", onChange: this.onChange, disabled: disabled})
                        )
                    )
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
                this.onChange(); // make sure initial state is saved
            },
            componentDidUpdate: function () {
                this.onChange();
            },
            onChange: function () {
                var value = {};
                value.sku = $(this.refs['skuCollectionIds'].getDOMNode()).select2('val');
                if (this.props.options.promo_type !== 'catalog') {
                    value.type = this.refs['skuCollectionType'].serialize();
                    value.filter = $(this.refs['skuCollectionCond'].getDOMNode()).val();
                    value.value = $(this.refs['skuCollectionValue'].getDOMNode()).val();
                }
                if (this.props.onUpdate) {
                    var updateData = {};
                    updateData[this.props.id] = value;
                    this.props.onUpdate(updateData);
                }
            }
        });

        // condition to apply to products which match the attributes condition configured here
        var ConditionsAttributeCombination = React.createClass({displayName: "ConditionsAttributeCombination",
            mixins: [Common.removeMixin],
            render: function () {
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement("div", {className: "col-md-5"}, 
                            React.createElement("textarea", {ref: "attributesResume", id: "attributesResume", 
                                readOnly: "readonly", value: this.state.valueText, className: "form-control"})
                        ), 
                        React.createElement("div", {className: "col-md-4"}, 
                            React.createElement(Components.Button, {type: "button", className: "btn-primary", ref: this.props.configureId, 
                                onClick: this.handleConfigure}, "Configure")
                        )
                    )
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
                var modal = React.createElement(Components.Modal, {onConfirm: this.handleConditionsConfirm, onCancel: this.handleConditionsCancel, 
                    id: "modal-" + this.props.id, key: "modal-" + this.props.id, 
                    title: "Product Combination Configuration", onLoad: this.registerModal, onUpdate: this.registerModal}, 
                    React.createElement(ConditionsAttributesModalContent, {baseUrl: this.props.options.base_url, data: this.state.value, 
                        onLoad: this.registerModalContent, key: "modal-content-" + this.props.id, promo_type: this.props.options.promo_type})
                );

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
                if (this.props.onUpdate) {// update main data field
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
                if (['number', 'date', 'time'].indexOf(field.input) != -1) {
                    type = 'numeric';
                } else if (field.input == 'text') {
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
        var ConditionsAttributesModalContent = React.createClass({displayName: "ConditionsAttributesModalContent",
            mixins: [Common.select2QueryMixin],
            render: function () {
                var fieldUrl = this.props.baseUrl + this.props.urlField;
                var paramObj = {};
                return (
                    React.createElement("div", {className: "attribute-combinations form-horizontal"}, 
                        React.createElement("div", {className: "form-group"}, 
                            React.createElement("div", {className: "col-md-6"}, 
                                React.createElement("select", {ref: "combinationType", className: "form-control to-select2", 
                                    id: "attribute-combination-type", defaultValue: this.state.match}, 
                                    React.createElement("option", {value: "all"}, "All Conditions Have to Match"), 
                                    React.createElement("option", {value: "any"}, "Any Condition Has to Match")
                                )
                            ), 
                            React.createElement("div", {className: "col-md-6"}, 
                                React.createElement("input", {ref: "combinationField", className: "form-control"})
                            )
                        ), 
                    this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        var data = field.value || [];
                        return React.createElement(ConditionsAttributesModalField, {label: field.label, url: url, key: field.field, 
                            data: data, filter: field.filter, 
                            id: field.field, input: field.input, removeField: this.removeField, ref: field.field, onChange: this.elementChange})
                    }.bind(this))
                    )
                );
            },
            serialize: function () {
                // serialize all values each time when its requested
                var data = {}, fields = [];
                for (var field in this.refs) {
                    if (!this.refs.hasOwnProperty(field) || field == 'combinationField') { // condition name field is reset after each selection, so we can ignore it
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
                if (fields.length) {
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
                if (fieldValue.value) {
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
                if(this.props.promo_type) {
                    this.url += "?promo_type=" + encodeURIComponent(this.props.promo_type);
                }
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
                //console.log(this.props.data);
                var state = {values: this.props.data || {}};
                for (var field in this.props.data) {
                    if (this.props.data.hasOwnProperty(field)) {
                        if (field == 'fields') {
                            var fields = this.props.data[field].map(function (field) {
                                if (!field.label) {
                                    var fieldId = field['field'].split('.');
                                    field.label = fieldId[1] || fieldId[0]; // if label is missing use field code instead
                                }
                                return field;
                            });
                            fields = this.state.fields.concat(fields);
                            state.fields = fields;
                        } else if (field == 'match') {
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

        var ConditionsAttributesModalField = React.createClass({displayName: "ConditionsAttributesModalField",
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
                    if (type == 'text') {
                        return opts.concat(opts_text);
                    } else if (type == 'numeric') {
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
                if (this.props.data && this.props.data.length) {
                    if ($.isArray(this.props.data)) {
                        value = this.props.data.join(",");
                    } else {
                        value = this.props.data;
                    }
                }
                var input = React.createElement("input", {className: "form-control required", type: "text", id: fieldId, ref: fieldId, 
                    onChange: this.onChange, defaultValue: value});
                if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                    if (inputType == 'number') {
                        if (this.state.range === false) {
                            input = React.createElement("input", {className: "form-control required", type: "number", step: "any", id: fieldId, 
                                ref: fieldId, style: {width: "auto"}, onChange: this.onChange, defaultValue: value});
                        } else {
                            value = this.props.data;
                            var min, max;
                            if (value.length > 0) {
                                min = value[0]
                            }

                            if (value.length > 1) {
                                max = value[1];
                            }
                            input = React.createElement("div", {id: fieldId, ref: fieldId, className: "input-group"}, 
                                React.createElement("input", {className: "form-control required", type: "number", step: "any", id: fieldId + ".min", ref: "min", 
                                    placeholder: "Min", style: {width: "50%"}, onChange: this.onChange, defaultValue: min}), 
                                React.createElement("input", {className: "form-control required", type: "number", step: "any", id: fieldId + ".max", ref: "max", 
                                    placeholder: "Max", style: {width: "50%"}, onChange: this.onChange, defaultValue: max})
                            );
                        }
                    } else if (inputType == 'date' || inputType == 'time') {
                        var singleMode = true;
                        if (this.state.range === true) {
                            singleMode = false;
                        }
                        input = React.createElement("div", {className: "input-group"}, 
                            React.createElement("span", {className: "input-group-addon"}, 
                                React.createElement("i", {className: "glyphicon glyphicon-calendar"})
                            ), 
                            React.createElement("input", {className: "form-control required", type: "text", id: fieldId, ref: fieldId, 
                                dataMode: singleMode, onChange: this.onChange, defaultValue: value})
                        )
                    }
                } else if (inputType == 'select') {
                    input = React.createElement("input", {className: "form-control required", type: "hidden", id: fieldId, ref: fieldId, 
                        defaultValue: value});
                } else if (this.props.bool_inputs.indexOf(inputType) != -1) {
                    input = React.createElement(Components.YesNo, {id: fieldId, ref: fieldId, onChange: this.onChange, defaultValue: value});
                }
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement("div", {className: "col-md-4"}, 
                            React.createElement(Common.Compare, {opts:  opts, id: "fieldCompare." + this.props.id, value: this.props.filter, 
                                ref: "fieldCompare." + this.props.id, onChange: this.onCompareChange})
                        ), 
                        React.createElement("div", {className: "col-md-5"}, input)
                    )
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
                    if (idArray.length > 1) { // id is like field.min/max
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
                if (!data) {
                    var startDate = new Date();
                    s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
                } else {
                    if (!mode) {
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
                if (e) {
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

        var ConditionsCategories = React.createClass({displayName: "ConditionsCategories",
            mixins: [Common.removeMixin, Common.select2QueryMixin],
            render: function () {
                var values = this.props.data;
                var categories = values.category_id;
                var promoType = this.props.options.promo_type;
                var display = {
                    display: (promoType === 'catalog') ? 'none' : 'inherit'
                };
                var disabled = (promoType === 'catalog');
                if ($.isArray(categories)) {
                    categories = categories.join(",");
                }
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement(ConditionsType, {ref: "catProductsType", id: "catProductsType", containerClass: "col-md-3", promoType: promoType, 
                            onChange: this.onChange, value: values.type}, " of products in "), 
                        React.createElement("input", {type: "hidden", id: "catProductsIds", ref: "catProductsIds", defaultValue: categories}), 
                        React.createElement("select", {id: "catProductInclude", ref: "catProductInclude", className: "to-select2", defaultValue: values.include}, 
                            React.createElement("option", {value: "only_this"}, Locale._("Only This")), 
                            React.createElement("option", {value: "include_subcategories"}, Locale._("This and sub categories"))
                        ), 
                        React.createElement("div", {style: display}, 
                            React.createElement(Common.Compare, {ref: "catProductsCond", id: "catProductsCond", onChange: this.onChange, 
                                value: values.filter, disabled: disabled})
                        ), 
                        React.createElement("input", {ref: "catProductsValue", id: "catProductsValue", type: "text", className: "", onChange: this.onChange, 
                            defaultValue: values.value, style: display, disabled: disabled})
                    )
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
                var self = this;
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
                    initSelection: function (el, callback) {
                        //var data = [];
                        var val = el.val();

                        $.get(self.url, {cats: val}).done(function (result) {
                            //console.log(result);
                            callback(result.items);
                        });

                        //var val = el.val().split(",");
                        //for (var i in val) {
                        //    var val2 = val[i];
                        //    data.push({id: val2, text: val2});
                        //}
                        //callback(data);
                    }

                }).on('change', this.onChange);
                $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15}).on('change', this.onChange);
                this.onChange();
            },
            componentDidUpdate: function () {
                this.onChange(); // on update set values
            },
            onChange: function () {
                var value = {};
                value.category_id = $(this.refs['catProductsIds'].getDOMNode()).select2('val');
                value.include = $(this.refs['catProductInclude'].getDOMNode()).val();
                if (this.props.options.promo_type !== 'catalog') {
                    value.type = this.refs['catProductsType'].serialize();
                    value.filter = $(this.refs['catProductsCond'].getDOMNode()).val();
                    value.value = $(this.refs['catProductsValue'].getDOMNode()).val();
                }
                if (this.props.onUpdate) {
                    var updateData = {};
                    updateData[this.props.id] = value;
                    this.props.onUpdate(updateData);
                }
            }
        });

        var ConditionTotal = React.createClass({displayName: "ConditionTotal",
            mixins: [Common.removeMixin],
            render: function () {
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement(ConditionsType, {ref: "cartTotalType", id: "cartTotalType", totalType: this.props.totalType, onChange: this.onChange, value: this.props.data.type}), 
                        React.createElement(Common.Compare, {ref: "cartTotalCond", id: "cartTotalCond", onChange: this.onChange, value: this.props.data.filter}), 
                        React.createElement("input", {ref: "cartTotalValue", id: "cartTotalValue", type: "text", className: "", onBlur: this.onChange, defaultValue: this.props.data.value})
                    )
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

                if (this.props.onUpdate) {
                    var updateData = {};
                    updateData[this.props.id] = value;
                    this.props.onUpdate(updateData);

                }
            }
        });

        var ConditionsShipping = React.createClass({displayName: "ConditionsShipping",
            mixins: [Common.removeMixin],
            render: function () {
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement("div", {className: "col-md-5"}, 
                            React.createElement("textarea", {ref: "shippingResume", id: "shippingResume", 
                                readOnly: "readonly", value: this.state.valueText, className: "form-control"})
                        ), 
                        React.createElement("div", {className: "col-md-4"}, 
                            React.createElement(Components.Button, {type: "button", className: "btn-primary pull-left", ref: this.props.configureId, 
                                onClick: this.handleConfigure}, "Configure")
                        )
                    )
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
                var modal = React.createElement(Components.Modal, {onConfirm: this.handleShippingConfirm, id: "modal-" + this.props.id, key: "modal-" + this.props.id, 
                    title: this.props.modalTitle, onLoad: this.openModal, onUpdate: this.openModal}, 
                    React.createElement(ConditionsShippingModalContent, {baseUrl: this.props.options.base_url, onLoad: this.registerModalContent, 
                        key: "modal-content-" + this.props.id, data: this.state.value})
                );

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
                if (this.props.onUpdate) {
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

        var ConditionsShippingModalContent = React.createClass({displayName: "ConditionsShippingModalContent",
            render: function () {
                var fieldUrl = this.props.baseUrl + this.props.url;
                var paramObj = {};
                //paramObj[this.props.idVar] = this.props.entityId;
                return (
                    React.createElement("div", {className: "shipping-combinations form-horizontal"}, 
                        React.createElement("div", {className: "form-group"}, 
                            React.createElement("div", {className: "col-md-5"}, 
                                React.createElement("select", {ref: "combinationType", className: "form-control to-select2", defaultValue: this.state.match}, 
                                    React.createElement("option", {value: "all"}, "All Conditions Have to Match"), 
                                    React.createElement("option", {value: "any"}, "Any Condition Has to Match")
                                )
                            ), 
                            React.createElement("div", {className: "col-md-5"}, 
                                React.createElement("select", {ref: "combinationField", className: "form-control"}, 
                                    React.createElement("option", {value: "-1"}, this.props.labelCombinationField), 
                                this.props.fields.map(function (field) {
                                    return React.createElement("option", {value: field.field, key: field.field}, field.label)
                                })
                                )
                            )
                        ), 
                    this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        var data = field.value || [];
                        return React.createElement(ConditionsShippingModalField, {label: field.label, url: url, key: field.field, data: data, filter: field.filter, 
                            id: field.field, ref: field.field, removeField: this.removeField, onChange: this.elementChange, opts: ConditionsShippingModalField.opts()})
                    }.bind(this))
                    )
                );
            },
            serialize: function () {
                var data = {}, fields = [];
                for (var field in this.refs) {
                    if (!this.refs.hasOwnProperty(field) || field == 'combinationField') {
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
                if (fields.length) {
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
                            if (fieldValue.compare) { // if field is the compare field, update and return
                                f.filter = fieldValue.value;
                                this.setState({fields: fields});
                            }
                            return;
                        }
                    }
                }
                var field = {label: fieldValue.text, field: fieldValue.id};
                if (fieldValue.value) {
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
                //console.log(this.props.data);
                this.setState({values: this.props.data || {}});
                for (var field in this.props.data) {
                    if (this.props.data.hasOwnProperty(field)) {
                        if (field == 'fields') {
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
                        } else if (field == 'match') {
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

        var ConditionsShippingModalField = React.createClass({displayName: "ConditionsShippingModalField",
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
                if (this.props.data && this.props.data.length) {
                    if ($.isArray(this.props.data)) {
                        value = this.props.data.join(",");
                    } else {
                        value = this.props.data;
                    }
                }
                var input = React.createElement("input", {className: "form-control", type: "hidden", id: fieldId, key: fieldId, ref: fieldId, defaultValue: value});
                var helperBlock = '';
                if (this.props.id == 'postcode') {
                    helperBlock = React.createElement("span", {key: fieldId + '.help', className: "help-block"}, this.props.postHelperText);
                }
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement("div", {className: "col-md-4"}, 
                            React.createElement(Common.Compare, {opts: this.props.opts, id: "fieldCompare." + this.props.id, value: this.props.filter, 
                                ref: "fieldCompare." + this.props.id, onChange: this.onCompareChange})
                        ), 
                        React.createElement("div", {className: "col-md-5"}, [input, helperBlock])
                    )
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
                    postHelperText: "Use .. (e.g. 90000..99999) to add range of post codes"
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
                var promoType = options.promo_type;
                var mc = this.props.modalContainer;
                var rc = this.removeCondition;
                var cu = this.conditionUpdate;

                for (var type in this.state.data.rules) {
                    if (this.state.data.rules.hasOwnProperty(type)) {
                        var rules = this.state.data.rules[type];
                        if ($.isArray(rules)) {
                            rules.map(function (field, idx) {
                                var el;
                                var key = type + '-' + idx;
                                switch (type) {
                                    case 'sku':
                                        el = React.createElement(ConditionsSkuCollection, {onUpdate: cu, data: field, options: options, key: key, id: key, removeCondition: rc});
                                        break;
                                    case 'category':
                                        el = React.createElement(ConditionsCategories, {onUpdate: cu, options: options, key: key, id: key, data: field, removeCondition: rc});
                                        break;
                                    case 'total':
                                        if (promoType == 'catalog') {
                                            el = '';
                                        } else {
                                            el = React.createElement(ConditionTotal, {onUpdate: cu, options: options, key: key, id: key, data: field, removeCondition: rc});
                                        }
                                        break;
                                    case 'combination':
                                        el = React.createElement(ConditionsAttributeCombination, {onUpdate: cu, options: options, data: field, modalContainer: mc, key: key, id: key, removeCondition: rc});
                                        break;
                                    case 'shipping':
                                        if (promoType == 'catalog') {
                                            el = '';
                                        } else {
                                            el = React.createElement(ConditionsShipping, {onUpdate: cu, options: options, data: field, modalContainer: mc, key: key, id: key, removeCondition: rc});
                                        }
                                        break;
                                }
                                if (el) {
                                    children.push(el);
                                }
                            })
                        } else {
                            //console.log(rules, "is not an array");
                        }
                    }
                }
                return (
                    React.createElement("div", {className: "conditions col-md-offset-1", style: {display: this.props.hidden ? "none" : "block"}}, 
                    children
                    )
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
                if (!data.rules[conditionType]) {
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
                if (condArray.length == 2) {
                    var rule = condArray[0], idx = condArray[1];
                    data.rules[rule].splice(idx, 1);
                    if (data.rules[rule].length == 0) {
                        delete data.rules[rule];
                    }
                } else {
                    //console.log("wrong condition id: " + conditionId);
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
                //console.log(data);
                var localData = this.state.data;
                for (var type in data) {
                    if (data.hasOwnProperty(type)) {
                        var condArray = type.split("-"); // to keep track of multiple conditions of same type shipping-0, shipping-1 ...
                        if (condArray.length == 2) {
                            var rule = condArray[0], idx = condArray[1];
                            localData.rules[rule][idx] = data[type];
                        } else {
                            //console.log("wrong condition id: " + type);
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
    }

    function action(React, $, Components, Common, Locale) {
        var divStyle = {float: 'left', marginLeft: 5};
        var Type = React.createClass({displayName: "Type",
            render: function () {
                var cls = this.props.select2 ? "to-select2 " : "";
                if (this.props.className) {
                    cls += this.props.className;
                }
                var promoType = this.props.promoType;
                var types = this.props.totalType.filter(function (type) {
                    return !(promoType == 'catalog' && type.id == 'fixed');

                });
                return (
                    React.createElement("div", {className: this.props.containerClass}, 
                        React.createElement("div", {className: "col-md-10"}, 
                            React.createElement("select", {className: cls, onChange: this.onChange, defaultValue: this.props.value}, 
                        types.map(function (type) {
                            return React.createElement("option", {value: type.id, key: type.id}, type.label)
                        })
                            )
                        ), 
                    this.props.children
                    )
                );
            },
            value: null,
            onChange: function (e) {
                this.value = $('select', this.getDOMNode()).select2('val');
                if (this.props.onChange) {
                    this.props.onChange(e);
                }
            },
            serialize: function () {
                return this.value || $('select', this.getDOMNode()).select2('val');
            },
            getDefaultProps: function () {
                return {
                    totalType: [
                        {id: "-%", label: "% Off"},
                        {id: "-$", label: "$ Amount Off"},
                        {id: "+%", label: "Add % to"},
                        {id: "+$", label: "Add to"},
                        {id: "=$", label: "$ Only"}
                    ],
                    select2: true,
                    containerClass: "col-md-2",
                    className: "form-control"
                };
            },
            componentDidMount: function () {
                $('select.to-select2', this.getDOMNode()).select2({width:'resolve'}).on('change', this.onChange);
            }
        });

        var DiscountDetailsCombination = React.createClass({displayName: "DiscountDetailsCombination",
            render: function () {
                return (
                    React.createElement("div", null, 
                        React.createElement("div", {className: "col-md-8"}, 
                            React.createElement("input", {type: "text", readOnly: "readonly", ref: "attributesResume" + this.props.id, 
                                key: "attributesResume" + this.props.id, id: "attributesResume" + this.props.id, 
                                className: "form-control", value: this.state.valueText})
                        ), 
                        React.createElement("div", {className: "col-md-4"}, 
                            React.createElement(Components.Button, {type: "button", className: "btn-primary", 
                                ref: this.props.configureId + this.props.id, onClick: this.handleConfigure}, "Configure")
                        )
                    )
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
                var modal = React.createElement(Components.Modal, {onConfirm: this.handleConditionsConfirm, onCancel: this.handleConditionsCancel, 
                    id: "modal-" + this.props.id, key: "modal-" + this.props.id, 
                    title: "Product Combination Configuration", onLoad: this.registerModal, onUpdate: this.registerModal}, 
                    React.createElement(DiscountDetailsCombinationsModalContent, {baseUrl: this.props.options.base_url, data: this.state.value, 
                        onLoad: this.registerModalContent, key: "modal-content-" + this.props.id, id: "modal-content-" + this.props.id})
                );

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
                }, function () {
                    if (this.props.onChange) {
                        this.props.onChange();
                    }
                });
                modal.close();
            },
            serialize: function () {
                return this.state.value;
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
                if (['number', 'date', 'time'].indexOf(field.input) != -1) {
                    type = 'numeric';
                } else if (field.input == 'text') {
                    type = 'text';
                } else if (field.input == 'select') {
                    type = 'select';
                } else if (field.input == "yes_no") {
                    type = 'bool';
                }

                var opts = DiscountDetailsCombinationsModalField.opts(type);
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

        var DiscountDetailsCombinationsModalContent = React.createClass({displayName: "DiscountDetailsCombinationsModalContent",
            mixins: [Common.select2QueryMixin],
            render: function () {
                var fieldUrl = this.props.baseUrl + this.props.urlField;
                var paramObj = {};
                var id = this.props.id;
                return (
                    React.createElement("div", {className: "attribute-combinations form-horizontal"}, 
                        React.createElement("div", {className: "form-group"}, 
                            React.createElement("div", {className: "col-md-5"}, 
                                React.createElement("select", {ref: "combinationType" + id, id: "combinationType" + id, 
                                    key: "combinationType" + id, className: "form-control to-select2", defaultValue: this.state.match}, 
                                    React.createElement("option", {value: "all"}, "All Conditions Have to Match"), 
                                    React.createElement("option", {value: "any"}, "Any Condition Has to Match")
                                )
                            ), 
                            React.createElement("div", {className: "col-md-5"}, 
                                React.createElement("input", {ref: "combinationField" + id, key: "combinationField" + id, 
                                    id: "combinationField" + id, className: "form-control"})
                            )
                        ), 
                    this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        var data = field.value || [];
                        return React.createElement(DiscountDetailsCombinationsModalField, {label: field.label, url: url, 
                            key: field.field + id, id: field.field + id, ref: field.field + id, 
                            data: data, filter: field.filter, field: field.field, 
                            input: field.input, removeField: this.removeField, onChange: this.elementChange})
                    }.bind(this))
                    )
                );
            },
            serialize: function () {
                // serialize all values each time when its requested
                var data = {}, fields = [];
                for (var field in this.refs) {
                    if (!this.refs.hasOwnProperty(field) || field == 'combinationField' + this.props.id) { // condition name field is reset after each selection, so we can ignore it
                        continue;
                    }
                    if (field == 'combinationType' + this.props.id) {
                        data.match = $(this.refs[field].getDOMNode()).select2('val'); // all || any
                        continue;
                    }
                    if (this.refs[field]) {
                        var ref = this.refs[field];
                        fields.push(ref.serialize());
                    }
                }
                if (fields.length) {
                    data.fields = fields;
                }
                return data;
            },
            serializeText: function () {
                var text, glue, fieldTexts = [], id = this.props.id;
                var allShouldMatch = $(this.refs['combinationType' + id].getDOMNode()).val(); // && or ||
                if (allShouldMatch == 'any') {
                    glue = " or ";
                } else {
                    glue = " and ";
                }
                for (var field in this.refs) {
                    if (!this.refs.hasOwnProperty(field) || field == 'combinationType' + id || field == 'combinationField' + id) {
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
                var fieldCombination = this.refs['combinationField' + this.props.id].getDOMNode();
                var fieldValue = $(fieldCombination).select2("data");
                if (null == fieldValue || fieldValue == []) {
                    return;
                }
                var fields = this.state.fields;
                var field = {label: fieldValue.text, field: fieldValue.id, input: fieldValue.input};
                if (fieldValue.value) {
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
                var fieldCombination = this.refs['combinationField' + this.props.id];
                var self = this;
                this.url = this.props.baseUrl + this.props.url;
                $(fieldCombination.getDOMNode()).select2({
                    placeholder: self.props.labelCombinationField,
                    multiple: false,
                    closeOnSelect: true,
                    query: self.select2query,
                    dropdownCssClass: "bigdrop",
                    dropdownAutoWidth: true
                }).on('change', this.addField);
                $(this.refs['combinationType' + this.props.id].getDOMNode()).select2();
                if (typeof this.props.onLoad == 'function') {
                    this.props.onLoad(this);
                }
            },
            componentWillMount: function () {
                // load fields from data, they come in form of plain js object
                //console.log(this.props.data);
                var state = {values: this.props.data || {}};
                for (var field in this.props.data) {
                    if (this.props.data.hasOwnProperty(field)) {
                        if (field == 'fields') {
                            var fields = this.props.data[field].map(function (field) {
                                if (!field.label) {
                                    var fieldId = field['field'].split('.');
                                    field.label = fieldId[1] || fieldId[0]; // if label is missing use field code instead
                                }
                                return field;
                            });
                            fields = this.state.fields.concat(fields);
                            state.fields = fields;
                        } else if (field == 'match') {
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

        var DiscountDetailsCombinationsModalField = React.createClass({displayName: "DiscountDetailsCombinationsModalField",
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
                    if (type == 'text') {
                        return opts.concat(opts_text);
                    } else if (type == 'numeric') {
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
                if (this.props.data && this.props.data.length) {
                    if ($.isArray(this.props.data)) {
                        value = this.props.data.join(",");
                    } else {
                        value = this.props.data;
                    }
                }
                var input = React.createElement("input", {className: "form-control required", type: "text", id: fieldId, ref: fieldId, key: fieldId, 
                    onChange: this.onChange, defaultValue: value});
                if (this.props.numeric_inputs.indexOf(inputType) != -1) {
                    if (inputType == 'number') {
                        if (this.state.range === false) {
                            input = React.createElement("input", {className: "form-control required", type: "number", step: "any", id: fieldId, 
                                ref: fieldId, key: fieldId, style: {width: "auto"}, onChange: this.onChange, defaultValue: value});
                        } else {
                            value = this.props.data;
                            var min, max;
                            if (value.length > 0) {
                                min = value[0]
                            }

                            if (value.length > 1) {
                                max = value[1];
                            }
                            input = React.createElement("div", {id: fieldId, ref: fieldId, key: fieldId, className: "input-group"}, 
                                React.createElement("input", {className: "form-control required", type: "number", step: "any", placeholder: "Min", 
                                    style: {width: "50%"}, onChange: this.onChange, defaultValue: min, id: fieldId + ".min"}), 
                                React.createElement("input", {className: "form-control required", type: "number", step: "any", placeholder: "Max", 
                                    style: {width: "50%"}, onChange: this.onChange, defaultValue: max, id: fieldId + ".max"})
                            );
                        }
                    } else if (inputType == 'date' || inputType == 'time') {
                        var singleMode = true;
                        if (this.state.range === true) {
                            singleMode = false;
                        }
                        input = React.createElement("div", {className: "input-group"}, 
                            React.createElement("span", {className: "input-group-addon"}, 
                                React.createElement("i", {className: "glyphicon glyphicon-calendar"})
                            ), 
                            React.createElement("input", {className: "form-control required", type: "text", id: fieldId, ref: fieldId, key: fieldId, 
                                dataMode: singleMode, onChange: this.onChange, defaultValue: value})
                        )
                    }
                } else if (inputType == 'select') {
                    input = React.createElement("input", {className: "form-control required", type: "hidden", id: fieldId, ref: fieldId, key: fieldId, 
                        defaultValue: value});
                } else if (this.props.bool_inputs.indexOf(inputType) != -1) {
                    input = React.createElement(Components.YesNo, {id: fieldId, ref: fieldId, key: fieldId, onChange: this.onChange, defaultValue: value});
                }
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement("div", {className: "col-md-4"}, 
                            React.createElement(Common.Compare, {opts:  opts, id: "fieldCompare." + this.props.id, value: this.props.filter, 
                                ref: "fieldCompare." + this.props.id, onChange: this.onCompareChange})
                        ), 
                        React.createElement("div", {className: "col-md-5"}, input)
                    )
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
                    field: this.props.field || this.props.id
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
                //$('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15}).on('change', this.props.onChange);
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
            onChange: function (e) {
                var type = this.getInputType();

                var $elem = $(e.target);
                // only select2 event has e.val, for dom inputs it must be added
                if (!e.val) { // for native inputs, use blur event to capture value
                    e.value = $elem.val();
                } else {
                    e.value = e.val;
                }
                //console.log(e);
                if (this.state.range && type == 'numeric') {
                    var id = $elem.attr('id');
                    var idArray = id.split('.');
                    if (idArray.length > 1) { // id is like field.min/max
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
            initDateInput: function () {
                var data = this.props.data, s, e;
                var fieldCombination = this.refs["fieldCombination." + this.props.id];
                var mode = fieldCombination.props.dataMode;
                if (!data) {
                    var startDate = new Date();
                    s = startDate.getFullYear() + '-' + (startDate.getMonth() + 1) + '-' + startDate.getDate();
                } else {
                    if (!mode) {
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
                if (e) {
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

        var DiscountSkuCombination = React.createClass({displayName: "DiscountSkuCombination",
            mixins: [Common.select2QueryMixin],
            render: function () {
                var value;
                if (this.props.data) {
                    if ($.isArray(this.props.data)) {
                        value = this.props.data.join(",");
                    } else {
                        value = this.props.data;
                    }
                }
                return (
                    React.createElement("input", {type: "hidden", id: "skuCollectionIds" + this.props.id, ref: "skuCollectionIds" + this.props.id, 
                        key: "skuCollectionIds" + this.props.id, className: "form-control", defaultValue: value})
                );
            },
            getDefaultProps: function () {
                return {
                    url: 'conditions/products'
                };
            },
            url: '',
            componentDidMount: function () {
                this.buildSelect();
            },
            componentWillUnmount: function () {
                var skuCollectionIds = this.refs['skuCollectionIds' + this.props.id];
                $(skuCollectionIds.getDOMNode()).select2('destroy');
            },
            buildSelect: function () {
                var skuCollectionIds = this.refs['skuCollectionIds' + this.props.id];
                this.url = this.props.options.base_url + this.props.url;
                var self = this;
                $(skuCollectionIds.getDOMNode()).select2({
                    placeholder: "Choose products",
                    multiple: true,
                    closeOnSelect: true,
                    dropdownCssClass: "bigdrop",
                    dropdownAutoWidth: true,
                    formatSelection: function (item) {
                        return item.id;
                    },
                    formatResult: function (item) {
                        var markup = '<div class="row-fluid" title="' + item.text + '">' +
                            '<div class="span2">SKU: <em>' + item.id + '</em></div>' +
                            '<div class="span2">Name: ' + item.text.substr(0, 20);
                        if (item.text.length > 20) {
                            markup += '...';
                        }
                        markup += '</div>' +
                        '</div>';

                        return markup;
                    },
                    initSelection: self.initSelection,
                    query: self.select2query
                }).on('change', this.props.onChange);
            },
            getSelectedProducts: function () {
                return $(this.refs['skuCollectionIds' + this.props.id].getDOMNode()).select2('val');
            }
        });

        var DiscountDetails = React.createClass({displayName: "DiscountDetails",
            render: function () {
                var details = React.createElement("span", null);
                if (this.props.type == 'attr_combination') {
                    details = React.createElement(DiscountDetailsCombination, {id: "attrCombination" + this.props.id, 
                        ref: "attrCombination" + this.props.id, key: "attrCombination" + this.props.id, 
                        options: this.props.options, modalContainer: this.props.modalContainer, 
                        data: this.props.data.combination, onChange: this.props.onChange})
                } else if (this.props.type == 'other_prod') {
                    details = React.createElement(DiscountSkuCombination, {id: "skuCombination" + this.props.id, 
                        ref: "skuCombination" + this.props.id, key: "skuCombination" + this.props.id, 
                        options: this.props.options, data: this.props.data.sku, onChange: this.props.onChange});
                }
                return details;
            },
            serialize: function () {
                // todo serialize
                var value = {};
                if (this.props.type == 'other_prod') {
                    value.sku = this.refs['skuCombination' + this.props.id].getSelectedProducts();
                } else if (this.props.type == 'attr_combination') {
                    value.combination = this.refs['attrCombination' + this.props.id].serialize();
                }
                return value;
            }
        });

        var Discount = React.createClass({displayName: "Discount",
            mixins: [Common.removeMixin],
            render: function () {
                var options = this.props.options;
                var promoType = options.promo_type;
                var display = {
                    display: (promoType === 'catalog') ? 'none' : 'inherit'
                };
                var isCatalog = (promoType === 'catalog');

                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement("div", {style: divStyle}, 
                            React.createElement("input", {className: "form-control pull-left", ref: "discountValue" + this.props.id, 
                                   id: "discountValue" + this.props.id, key: "discountValue" + this.props.id, 
                                   type: "text", size: "6", 
                                   defaultValue: this.state.value, onBlur: this.onChange})
                        ), 
                        React.createElement(Type, {ref: "discountType" + this.props.id, id: "discountType" + this.props.id, promoType: promoType, 
                            key: "discountType" + this.props.id, value: this.state.type, onChange: this.onTypeChange}), 
                        React.createElement("div", {style: $.extend({},divStyle, display)}, 
                            React.createElement("select", {className: "to-select2 form-control", disabled: isCatalog, 
                                ref: "discountScope" + this.props.id, defaultValue: this.state.scope, 
                                id: "discountScope" + this.props.id, key: "discountScope" + this.props.id, onChange: this.onChange}, 
                                this.props.scopeOptions.map(function (type) {
                                    return React.createElement("option", {value: type.id, key: type.id}, type.label)
                                })
                            )
                        ), 
                        React.createElement("div", {style: $.extend({},divStyle, display)}, 
                            React.createElement(DiscountDetails, {type: this.state.scope, options: this.props.options, ref: "discountDetails" + this.props.id, 
                                id: "discountDetails" + this.props.id, key: "discountDetails" + this.props.id, 
                                modalContainer: this.props.modalContainer, onChange: this.onChange, 
                                data: {
                                    sku: this.state.sku,
                                    combination: this.state.combination
                                }, promoType: promoType})
                        ), 
                         isCatalog && this.state.type != '=$'?
                            React.createElement("div", {style: divStyle}, 
                                React.createElement("select", {className: "form-control to-select2", ref: "base-field-" + this.props.id, 
                                        defaultValue: this.state.base_field, 
                                        id: "base-field-" + this.props.id, key: "base-field-" + this.props.id}, 
                                    this.props.options['base_fields']['promo'].map(function (p) {

                                        var opt = null;
                                        var t = this.state.type;
                                        if((t == '+%' || t == '+$') && p.value === 'cost') {
                                            opt = React.createElement("option", {key: p.value, value: p.value}, p.label);
                                        } else if((t == '-%' || t == '-$') && p.value !== 'cost'){
                                            opt = React.createElement("option", {key: p.value, value: p.value}, p.label);
                                        }

                                        return opt;
                                    }.bind(this))
                                )
                            ):
                            null
                        
                    )
                );
            },
            componentWillMount: function () {
                var data = this.props.data;
                this.setState(data);
            },
            componentDidMount: function () {
                if (this.props.options.promo_type != 'catalog') {
                    $(this.refs['discountScope' + this.props.id].getDOMNode()).select2().on('change', this.onScopeChange);
                } else {
                    $(this.refs["base-field-" + this.props.id].getDOMNode()).select2().on('change', this.onScopeChange);
                }
                this.onChange();
            },
            componentDidUpdate: function () {
                this.onChange(); // on update set values
            },
            getDefaultProps: function () {
                return {
                    label: Locale._('Discount'),
                    rowClass: 'discount',
                    scopeOptions: [
                        {id: 'whole_order', label: 'Whole Order'},
                        {id: 'cond_prod', label: 'Product from Conditions'},
                        {id: 'other_prod', label: 'Other SKUs'},
                        {id: 'attr_combination', label: 'Combination'}
                    ]
                }
            },
            getInitialState: function () {
                return {
                    value: 0,
                    type: '-%',
                    scope: 'cond_prod',
                    sku: [],
                    combination: {}
                };
            },
            onTypeChange: function (ev) {
                ev.preventDefault();
                var newType = $(ev.target).val();
                this.onChange();
                this.setState({type: newType});
            },
            onScopeChange: function (ev) {
                ev.preventDefault();
                var newScope = $(ev.target).val();
                this.onChange();
                this.setState({scope: newScope});
            },
            onChange: function () {
                var value = {};
                value.value = $(this.refs['discountValue' + this.props.id].getDOMNode()).val();
                value.type = this.refs['discountType' + this.props.id].serialize();

                if (this.props.options.promo_type !== 'catalog') {
                    value.scope = $(this.refs['discountScope' + this.props.id].getDOMNode()).select2('val');

                    var details = this.refs['discountDetails' + this.props.id].serialize();
                    for (var d in details) {
                        if (details.hasOwnProperty(d)) {
                            value[d] = details[d];
                        }
                    }

                    // make sure to remove any invalid data
                    if (value.scope != "attr_combination") {
                        delete value['combination'];
                    }

                    if (value.scope != "other_prod") {
                        delete value['product_ids'];
                    }
                } else {
                    var ref = this.refs["base-field-" + this.props.id];
                    if (ref) {
                        var baseField = $(ref.getDOMNode()).select2().val();
                        value.base_field = baseField;
                    }
                }

                //this.setState(value);

                if (this.props.onUpdate) {
                    var updateData = {};
                    updateData[this.props.id] = value;
                    this.props.onUpdate(updateData);

                }
            }
        });

        var FreeProduct = React.createClass({displayName: "FreeProduct",
            mixins: [Common.select2QueryMixin, Common.removeMixin],
            render: function () {
                var skus;
                if (this.state.sku) {
                    if ($.isArray(this.state.sku)) {
                        skus = this.state.sku.join(",");
                    } else {
                        skus = this.state.sku;
                    }
                }
                var terms;
                if (this.state.terms) {
                    if ($.isArray(this.state.terms)) {
                        terms = this.state.terms;
                    } else {
                        terms = [this.state.terms];//The `defaultValue` prop supplied to <select> must be an array if `multiple` is true
                    }
                }
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                        React.createElement("div", {style: this.props.divStyle}, 
                            React.createElement("input", {type: "hidden", className: "form-control", id: "productSku", ref: "productSku", defaultValue: skus})
                        ), 
                        React.createElement("div", {style: this.props.divStyle}, 
                            React.createElement("div", {style: this.props.divStyle}, 
                                React.createElement(Components.ControlLabel, {input_id: "productQty", 
                                    label_class: ""}, Locale._('Qty'))
                            ), 
                            React.createElement("div", {style: {float: 'left', marginLeft: 5}}, 
                                React.createElement("input", {type: "text", className: "form-control", id: "productQty", ref: "productQty", 
                                    defaultValue: this.state.qty, onChange: this.onChange})
                            )
                        ), 
                        React.createElement("div", {style: this.props.divStyle}, 
                            React.createElement("div", {style: this.props.divStyle}, 
                                React.createElement(Components.ControlLabel, {input_id: "productTerms", label_class: ""}, Locale._('Terms'))
                            ), 
                            React.createElement("div", {style: this.props.divStyle}, 
                                React.createElement("select", {className: "form-control to-select2", id: "productTerms", ref: "productTerms", 
                                    multiple: "multiple", defaultValue: terms}, 
                                    React.createElement("option", {value: "tax"}, Locale._("Charge tax")), 
                                    React.createElement("option", {value: "sah"}, Locale._("Charge S & H"))
                                )
                            )
                        )
                    )
                );
            },
            getInitialState: function () {
                return {
                    sku: [],
                    terms: [],
                    qty: 1
                }
            },
            getDefaultProps: function () {
                return {
                    url: "conditions/products",
                    labelSkuField: Locale._("Select product sku"),
                    divStyle: {float: 'left', marginLeft: 5}
                }
            },
            url: '',
            componentWillMount: function () {
                var state = {
                    qty: this.props.data.qty || this.state.qty,
                    sku: this.props.data.sku,
                    terms: this.props.data.terms
                };

                this.setState(state);
            },
            componentDidMount: function () {
                var productSku = this.refs['productSku'];
                var self = this;
                this.url = this.props.options.base_url + '/' + this.props.url;
                $(productSku.getDOMNode()).select2({
                    multiple: true,
                    placeholder: self.props.labelSkuField,
                    query: self.select2query,
                    dropdownAutoWidth: true,
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
                        markup += '</div>' +
                        '</div>';

                        return markup;
                    },
                    initSelection: self.initSelection
                }).on('change', this.onChange);
                $(this.refs['productTerms'].getDOMNode()).select2().on('change', this.onChange);
            },
            onChange: function () {
                var value = {};
                value.qty = $(this.refs['productQty'].getDOMNode()).val();
                value.sku = $(this.refs['productSku'].getDOMNode()).select2('val');
                value.terms = $(this.refs['productTerms'].getDOMNode()).select2('val');

                //this.setState(value);

                if (this.props.onUpdate) {
                    var updateData = {};
                    updateData[this.props.id] = value;
                    this.props.onUpdate(updateData);

                }
            }
        });

        var Shipping = React.createClass({displayName: "Shipping",
            mixins: [Common.select2QueryMixin, Common.removeMixin],
            render: function () {
                var amount = '';
                if (this.state.type != 'free') {
                    amount = React.createElement("input", {type: "number", defaultValue: this.state.amount, id: "shippingAmount", ref: "shippingAmount", className: "form-control", onChange: this.onChange})
                }
                var type = React.createElement(Type, {ref: "shippingType", id: "shippingType", onChange: this.onTypeChange, value: this.state.type, 
                    totalType: this.props.fields, value: this.state.type});
                var label = React.createElement(Components.ControlLabel, {label_class: "col-md-1", input_id: "shippingMethods"}, Locale._('For'));
                var methods = this.state.methods;
                if ($.isArray(methods)) {
                    methods = methods.join(",");
                }
                var input = React.createElement("input", {type: "hidden", className: "form-control", id: "shippingMethods", ref: "shippingMethods", defaultValue: methods});
                return (
                    React.createElement(Common.Row, {rowClass: this.props.rowClass, label: this.props.label, onDelete: this.remove}, 
                    type, 
                        React.createElement("div", {className: "col-md-7"}, 
                            React.createElement("div", {className: amount ? "col-md-2" : ""}, amount), 
                        label, 
                            React.createElement("div", {className: amount ? "col-md-9" : "col-md-11"}, input)
                        /*if no amount field, make this wider*/
                        )
                    )
                );
            },
            onTypeChange: function (ev) {
                ev.preventDefault();
                var newType = $(ev.target).val();
                this.setState({type: newType});
                this.onChange();
            },
            getDefaultProps: function () {
                return {
                    fields: [
                        {id: "-%", label: "% Off"},
                        {id: "-$", label: "$ Amount Off"},
                        {id: "=$", label: "$ Only"},
                        {id: "=0", label: "Free"}
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
            componentWillMount: function () {
                var state = {
                    amount: this.props.data.value,
                    methods: this.props.data.methods,
                    type: this.props.data.type
                };

                this.setState(state);
            },
            componentDidMount: function () {
                var shippingMethods = this.refs['shippingMethods'];
                var self = this;
                this.url = this.props.options.base_url + '/' + this.props.url + '?' + $.param({field: 'methods'});
                $(shippingMethods.getDOMNode()).select2({
                    placeholder: self.props.labelMethodsField,
                    multiple: true,
                    query: self.select2query,
                    dropdownAutoWidth: true,
                    initSelection: function (el, callback) {
                        //var data = [];
                        var val = el.val();

                        $.get(self.url, {methods: val}).done(function (result) {
                            //console.log(result);
                            callback(result.items);
                        });

                        //var val = el.val().split(",");
                        //for (var i in val) {
                        //    var val2 = val[i];
                        //    data.push({id: val2, text: val2});
                        //}
                        //callback(data);
                    }
                }).on('change', this.onChange);
            },
            onChange: function () {
                var value = {};
                value.type = this.refs['shippingType'].serialize();
                value.methods = $(this.refs['shippingMethods'].getDOMNode()).select2('val');
                if (this.refs['shippingAmount']) {
                    value.value = $(this.refs['shippingAmount'].getDOMNode()).val();
                }

                //this.setState(value);

                if (this.props.onUpdate) {
                    var updateData = {};
                    updateData[this.props.id] = value;
                    this.props.onUpdate(updateData);

                }
            }
        });

        var ActionsApp = React.createClass({
            displayName: 'ActionsApp',
            render: function () {
                var children = [];
                var options = this.props.options;
                var promoType = options.promo_type;
                var mc = this.props.modalContainer;
                var ra = this.removeAction;
                var au = this.actionUpdate;
                for (var action in this.state.data.rules) {
                    if (this.state.data.rules.hasOwnProperty(action)) {
                        var actions = this.state.data.rules[action];
                        if ($.isArray(actions)) {
                            actions.map(function (field, idx) {
                                //todo make a field based on field
                                var el;
                                var key = action + '-' + idx;
                                switch (action) {
                                    case 'discount':
                                        el = React.createElement(Discount, {label: Locale._("Discount"), options: options, 
                                            key: key, id: key, removeAction: ra, data: field, 
                                            modalContainer: mc, onUpdate: au});
                                        break;
                                    case 'free_product':
                                        if (promoType == 'catalog') {
                                            el = '';
                                        } else {
                                            el = React.createElement(FreeProduct, {label: Locale._("Auto Add Product To Cart"), options: options, 
                                                key: key, id: key, removeAction: ra, onUpdate: au, data: field});
                                        }
                                        break;
                                    case 'shipping':
                                        if (promoType == 'catalog') {
                                            el = '';
                                        } else {
                                            el = React.createElement(Shipping, {label: Locale._("Shipping"), options: options, 
                                                key: key, id: key, removeAction: ra, onUpdate: au, data: field});
                                        }
                                        break;

                                }
                                if (el) {
                                    children.push(el);
                                }
                            })
                        } else {
                            //console.log(actions, "is not an array");
                        }
                    }
                }
                return (
                    React.createElement("div", {className: "actions col-md-offset-1"}, 
                    children
                    )
                );
            },
            componentWillMount: function () {
                var data = this.state.data;

                if (this.props.actions.rules) {
                    data = this.props.actions;
                    this.setState({data: data});
                }
            },
            componentDidMount: function () {
                var $conditionsSerialized = $('#' + this.props.options.conditions_serialized);
                var data = this.state.data;

                if ($conditionsSerialized.length > 0) {
                    try {
                        data = JSON.parse($conditionsSerialized.val());
                        this.setProps({data: data});
                    } catch (e) {
                        //console.log(e);
                    }
                }

                if (this.props.actionType.length) {
                    this.props.actionType.on('change', this.addAction);
                }

                $('select.to-select2', this.getDOMNode()).select2();
            },
            addAction: function () {
                // add condition data to state
                var $actionTypes = this.props.actionType;
                if ($actionTypes.length == 0) {
                    return;
                }

                var actionType = $actionTypes.val();
                if (actionType == "-1") {
                    return;
                }
                $actionTypes.select2('val', "-1", false);// reset to placeholder value and do NOT trigger change event
                var data = this.state.data;
                if (!data.rules[actionType]) {
                    data.rules[actionType] = [];
                }
                data.rules[actionType].push({}); // push new empty rule
                this.setState({data: data, lastActionId: (this.state.lastActionId + 1)}, function () {
                    this.props.onUpdate(this.state.data);
                });
            },
            removeAction: function (actionId) {
                var data = this.state.data;
                var actionArray = actionId.split("-");
                if (actionArray.length == 2) {
                    var rule = actionArray[0], idx = actionArray[1];
                    data.rules[rule].splice(idx, 1);
                    if (data.rules[rule].length == 0) {
                        delete data.rules[rule];
                    }
                } else {
                    //console.log("wrong condition id: " + actionId);
                }
                this.setState({data: data}, function () {
                    this.props.onUpdate(this.state.data);
                });
            },
            actionUpdate: function (data) {
                //console.log(data);
                var localData = this.state.data;
                for (var type in data) {
                    if (data.hasOwnProperty(type)) {
                        var actionArray = type.split("-"); // to keep track of multiple conditions of same type shipping-0, shipping-1 ...
                        if (actionArray.length == 2) {
                            var rule = actionArray[0], idx = actionArray[1];
                            localData.rules[rule][idx] = data[type];
                        } else {
                            //console.log("wrong condition id: " + type);
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
                    lastActionId: 0
                };
            }
        });
        return ActionsApp;
    }

    function common(React, Components) {
        var Common = {
            DelBtn: React.createClass({displayName: "DelBtn",
                render: function () {
                    return (
                        React.createElement(Components.Button, {className: "btn-link btn-delete", onClick: this.props.onClick, 
                            type: "button", style:  {paddingRight: 10, paddingLeft: 10} }, 
                            React.createElement("span", {className: "icon-trash"})
                        )
                    );
                }
            }),
            Row: React.createClass({displayName: "Row",
                render: function () {
                    var cls = "form-group condition";
                    if (this.props.rowClass) {
                        cls += " " + this.props.rowClass;
                    }
                    return (React.createElement("div", {className: cls}, 
                        React.createElement("div", {className: "col-md-3"}, 
                            React.createElement(Components.ControlLabel, {label_class: "pull-right"}, this.props.label, 
                                React.createElement(Common.DelBtn, {onClick: this.props.onDelete})
                            )
                        ), 
                this.props.children
                    ));
                }
            }),
            Compare: React.createClass({displayName: "Compare",
                render: function () {
                    return (
                        React.createElement("select", {className: "to-select2", onChange: this.props.onChange, id: this.props.id, 
                            defaultValue: this.props.value, disabled: this.props.disabled, style: this.props.style}, 
                    this.props.opts.map(function (type) {
                        return React.createElement("option", {value: type.id, key: type.id}, type.label)
                    })
                        )
                    );
                },
                getDefaultProps: function () {
                    return {
                        opts: [
                            {id: "gt", label: "is greater than"},
                            {id: "gte", label: "is greater than or equal to"},
                            {id: "lt", label: "is less than"},
                            {id: "lte", label: "is less than or equal to"},
                            {id: "eq", label: "is equal to"},
                            {id: "neq", label: "is not equal to"}
                        ]
                    };
                },
                componentDidMount: function () {
                    $(this.getDOMNode()).select2().on('change', this.props.onChange);
                }
            }),
            AddFieldButton: React.createClass({displayName: "AddFieldButton",
                render: function () {
                    return (
                        React.createElement(Components.Button, {onClick: this.props.onClick, className: "btn-link pull-left", type: "button", style:  {
                            paddingRight: 10,
                            paddingLeft: 10
                        } }, 
                            React.createElement("span", {"aria-hidden": "true", className: "glyphicon glyphicon glyphicon-plus-sign"})
                        )
                    );
                }
            }),
            select2QueryMixin: {
                select2query: function (options) {
                    var self = this;
                    var $el = $(options.element);
                    var values = $el.data('searches') || [];
                    var flags = $el.data('flags') || {};
                    var term = options.term || '*';
                    var page = options.page;
                    //console.log(page);
                    var data;
                    if (flags[term] != undefined && flags[term].loaded == 2) {
                        data = {
                            results: self.searchLocal(term, values, page, 100),
                            more: (flags[term].page > page)
                        };
                        options.callback(data);
                    } else {
                        this.search({
                            term: term,
                            page: page,
                            searchedTerms: flags
                        }, this.url, function (result, params) {
                            var more;
                            if (result == 'local') {
                                more = (params.searchedTerms[term].page > params.page) || (params.searchedTerms[term].loaded == 1);
                                data = {
                                    results: self.searchLocal(params.term, values, params.page, params.o),
                                    more: more
                                };
                                options.callback(data);
                            } else if (result.items !== undefined) {
                                more = params.searchedTerms[term].loaded === 1;
                                data = {results: result.items, more: more};
                                flags[term] = params.searchedTerms[term];
                                values = self.mergeResults(values, data.results, function (item, bitSet) {
                                    var inSet = true;
                                    if (!bitSet[item.id]) {
                                        inSet = false;
                                        bitSet[item.id] = 1;
                                    }
                                    return inSet;
                                });
                                $el.data({searches: values, flags: flags});

                                options.callback(data);
                            }
                        })
                    }
                },
                mergeResults: function () {
                    var result = [], bitSet = {}, arr, len;
                    var checker = arguments[arguments.length - 1]; // function to check if item is in set
                    if (!$.isFunction(checker)) {
                        throw "Last argument must be a function.";
                    }
                    for (var i = 0; i < (arguments.length - 1); i++) {
                        arr = arguments[i];
                        if (!arr instanceof Array) {
                            continue;
                        }
                        len = arr.length;
                        while (len--) {
                            var itm = arr[len];
                            if (!checker(itm, bitSet)) {
                                result.unshift(itm);
                            }
                        }
                    }
                    return result;
                },
                search: function (params, url, callback) {
                    params.q = params.term || '*'; // '*' means default search
                    params.page = params.page || 1;
                    params.o = params.limit || 100;

                    params.searchedTerms = params.searchedTerms || {};
                    if (params.searchedTerms['*'] && params.searchedTerms['*'].loaded == 2) {
                        // if default search already returned all results, no need to go back to server
                        params.searchedTerms[params.term] = params.searchedTerms['*'];
                    }
                    var termStatus = params.searchedTerms[params.term];
                    if (termStatus == undefined || (termStatus.loaded == 1 && termStatus.page < params.page)) { // if this is first load, or there are more pages and we're looking for next page
                        if (termStatus == undefined) {
                            params.searchedTerms[params.term] = {};
                        }
                        $.get(url, {page: params.page, q: params.q, o: params.o})
                            .done(function (result) {
                                if (result.hasOwnProperty('total_count')) {
                                    //console.log(result['total_count']);
                                    var more = params.page * params.o < result['total_count'];
                                    params.searchedTerms[params.term].loaded = (more) ? 1 : 2; // 1 means more results to be fetched, 2 means all fetched
                                    params.searchedTerms[params.term].page = params.page; // 1 means more results to be fetched, 2 means all fetched
                                }
                                callback(result, params);
                            })
                            .fail(function (result) {
                                callback(result, params);
                            });
                    } else if (termStatus.loaded == 2 || (termStatus.page >= params.page)) {
                        callback('local', params); // find results from local storage
                    } else {
                        console.error("UNKNOWN search status.")
                    }
                },
                searchLocal: function (term, values, page, limit) {
                    page = page || 1;
                    limit = limit || 100;
                    var counted = 0;
                    var offset = (page - 1) * limit; // offset from which to start fetching results
                    var max = offset + limit;
                    var regex;
                    if (term != '*') { // * is match all, don't try to search
                        regex = new RegExp(term, 'i');
                    }
                    var matches = $.grep(values, function (val) {
                        if (counted >= max) { // if already reached goal, don't add any more matches
                            return false;
                        }

                        var test;
                        if (regex) {
                            test = regex.test(val['text']); // if regex and it matches a term
                            if (!test && val.hasOwnProperty('sku')) {
                                test = regex.test(val['sku']);
                            }
                            if (test) {
                                //console.log(term + ' matches ' + val.text);
                                counted++; // up the counter
                            }
                        } else {
                            counted++; // no regex, just return matching items by position
                            test = true;
                        }
                        return test && counted >= offset && counted < max;// if term is not for this page, skip it
                    });
                    return matches;
                },
                initSelection: function (el, callback) {
                    var data = [];
                    var val = el.val().split(",");
                    for (var i in val) {
                        var val2 = val[i];
                        data.push({id: val2, text: val2});
                    }
                    callback(data);
                }
            },
            removeMixin: {
                remove: function () {
                    if (this.props.removeAction) {
                        this.props.removeAction(this.props.id);
                    } else if (this.props.removeCondition) {
                        this.props.removeCondition(this.props.id);
                    }
                }
            }
        };
        return Common;
    }

    function couponApp(React, $, Locale, Components) {
        var labelClass = "col-md-3";
        var SingleCoupon = React.createClass({displayName: "SingleCoupon",
            render: function () {
                return (
                    React.createElement("div", {className: "single-coupon form-group"}, 
                        React.createElement(Components.ControlLabel, {input_id: this.props.id, label_class: this.props.labelClass}, 
                        this.props.labelText, 
                            React.createElement(Components.HelpIcon, {id: "help-" + this.props.id, content: this.props.helpText})
                        ), 
                        React.createElement("div", {className: "col-md-5"}, 
                            React.createElement("input", {id: this.props.id, ref: this.props.name, name: this.props.name, className: "form-control", defaultValue: this.props.value}), 
                            React.createElement("span", {className: "help-block"}, this.props.helpText)
                        )
                    )
                );
            },
            getDefaultProps: function () {
                // component default properties
                return {
                    id: "model-single_coupon_code",
                    name: "single_coupon_code",
                    helpText: Locale._("(Leave empty for auto-generate)"),
                    labelText: Locale._("Coupon Code")
                };
            },
            getInitialState: function () {
                // component default properties
                return {
                    value: ''
                };
            }
        });
        var GenerateForm = React.createClass({displayName: "GenerateForm",
            render: function () {
                return (
                    React.createElement("div", {className: "f-section", id: "coupon-generate-container"}, 
                        React.createElement("div", {className: "well well-sm help-block", style: {fontSize: 12}}, 
                            React.createElement("p", null, Locale._("You can have unique coupon codes generated for you automatically if you input simple patterns.")), 
                            React.createElement("p", null, Locale._("Pattern examples:")), 
                            React.createElement("p", null, 
                                React.createElement("code", null, "{U8}"), Locale._(" - 8 alpha chars - will result to something like "), 
                                React.createElement("code", null, "DKABWJKQ")
                            ), 
                            React.createElement("p", null, 
                                React.createElement("code", null, "{D4}"), Locale._(" - 4 digits - will result to something like "), 
                                React.createElement("code", null, "5640")
                            ), 
                            React.createElement("p", null, 
                                React.createElement("code", null, "{UD5}"), Locale._(" - 5 alphanumeric - will result to something like "), 
                                React.createElement("code", null, "GHG76")
                            ), 
                            React.createElement("p", null, 
                                React.createElement("code", null, "CODE-{U4}-{UD6}"), 
                                "-", 
                                React.createElement("code", null, "CODE-HQNB-8A1NO3")
                            ), 
                            React.createElement("p", null, "Locale._(\"Note: dynamic parts of the code MUST be enclosed in {}\")")
                        ), 
                        React.createElement("div", {id: "coupon-generate-container", ref: "formContainer", className: "form-horizontal"}, 
                            React.createElement(Components.Input, {field: "code_pattern", label: Locale._("Code Pattern"), 
                                helpBlockText: Locale._("(Leave empty to auto-generate)"), 
                                inputDivClass: "col-md-8", label_class: "col-md-4"}), 
                            React.createElement(Components.Input, {field: "code_length", label: Locale._("Coupon Code Length"), 
                                helpBlockText: Locale._("(Will be used only if auto-generating codes)"), 
                                inputDivClass: "col-md-8", label_class: "col-md-4"}), 
                            React.createElement(Components.Input, {field: "coupon_count", label: Locale._("How many to generate"), 
                                inputDivClass: "col-md-8", label_class: "col-md-4", inputValue: "1", required: true}), 
                            React.createElement("div", {className: this.props.groupClass}, 
                                React.createElement("div", {className: "col-md-offset-4"}, 
                                    React.createElement("span", {style: {
                                        display: 'none',
                                        marginLeft: 20
                                    }, className: "loading"}, "Loading ... "), 
                                    React.createElement("span", {style: {display: 'none', marginLeft: 20}, className: "result"})
                                )
                            )
                        )
                    )
                );
            },
            handleGenerateClick: function (e) {
                this.props.onSubmit(e);
            },
            getDefaultProps: function () {
                // component default properties
                return {
                    groupClass: "form-group"
                }
            }
        });

        var MultiCoupon = React.createClass({displayName: "MultiCoupon",
            render: function () {
                return (
                    React.createElement("div", {className: "multi-coupon form-group", style: {margin: "15px 0"}}, 
                        React.createElement("div", {className: "form-group"}, 
                            React.createElement(Components.ControlLabel, {input_id: "limit_per_coupon", label_class: "col-md-3"}, 
                            Locale._("Limit Per Coupon Code"), 
                                React.createElement(Components.HelpIcon, {id: "help-limit_per_coupon", 
                                    content: Locale._("Maximum number of uses per coupon code")})
                            ), 

                            React.createElement("div", {className: "col-md-1"}, 
                                React.createElement("input", {type: "text", id: "limit_per_coupon", ref: "limit_per_coupon", 
                                    name: "model[limit_per_coupon]", className: "form-control", 
                                    defaultValue: this.props.options['limit_per_coupon']})
                            )
                        ), 
                        React.createElement("div", {className: "btn-group col-md-offset-3"}, 
                            React.createElement(Components.Button, {onClick: this.props.onShowCodes, className: "btn-primary", 
                                type: "button"}, this.state.buttonViewLabel ? this.state.buttonViewLabel : this.props.buttonViewLabel), 
                            React.createElement(Components.Button, {onClick: this.props.onGenerateCodes, className: "btn-primary", 
                                type: "button"}, this.props.buttonGenerateLabel), 
                            React.createElement(Components.Button, {onClick: this.props.onImportCodes, className: "btn-primary", 
                                type: "button"}, this.props.buttonImportLabel)
                        )
                    )
                );
            },
            componentDidMount: function () {
                var self = this;
                $(document).on("grid_count_update", function (ev) {
                    var count = ev.numCodes;
                    if (count) {
                        var newLabel = self.props.buttonViewLabelTemplate.replace('%d%', count);
                        self.setState({buttonViewLabel: newLabel});
                    }
                });
            },
            getDefaultProps: function () {
                // component default properties
                return {
                    buttonViewLabel: Locale._("View Codes"),
                    buttonGenerateLabel: Locale._("Generate New Codes"),
                    buttonImportLabel: Locale._("Import Existing Codes"),
                    buttonViewLabelTemplate: Locale._("View (%d%) Codes")
                }
            },
            getInitialState: function () {
                return {};
            }
        });

        var UsesBlock = React.createClass({displayName: "UsesBlock",
            render: function () {
                return (
                    React.createElement("div", {className: "uses-block form-group", style: {clear: 'both'}}, 
                        React.createElement(Components.ControlLabel, {input_id: this.props.idUpc, label_class: this.props.labelClass}, 
                        this.props.labelUpc, 
                            React.createElement(Components.HelpIcon, {id: "help-" + this.props.idUpc, content: this.props.helpTextUpc})
                        ), 
                        React.createElement("div", {className: "col-md-1"}, 
                            React.createElement("input", {type: "text", id: this.props.idUpc, ref: this.props.idUpc, 
                                name: "model[" + this.props.idUpc + "]", className: "form-control", 
                                defaultValue: this.state.valueUpc})
                        ), 

                        React.createElement(Components.ControlLabel, {input_id: this.props.idUt, label_class: this.props.labelClass}, 
                        this.props.labelUt, 
                            React.createElement(Components.HelpIcon, {id: "help-" + this.props.idUt, content: this.props.helpTextUt})
                        ), 

                        React.createElement("div", {className: "col-md-1"}, 
                            React.createElement("input", {type: "text", id: this.props.idUt, ref: this.props.idUt, 
                                name: "model[" + this.props.idUt + "]", className: "form-control", 
                                defaultValue: this.state.valueUt})
                        )
                    )
                );
            },
            getDefaultProps: function () {
                // component default properties
                return {
                    labelUpc: Locale._("Limit Per Customer"),
                    labelUt: Locale._("Limit Per Promo"),
                    idUpc: "limit_per_customer",
                    idUt: "limit_per_promo",
                    helpTextUpc: Locale._("How many times a user can use a coupon?"),
                    helpTextUt: Locale._("How many total times a coupon can be used?")
                };
            },
            getInitialState: function () {
                // component default properties
                return {
                    valueUpc: '',
                    valueUt: ''
                };
            }, componentWillMount: function () {
                if (this.props.options.valueUpc) {
                    this.setState({valueUpc: this.props.options.valueUpc});
                }
                if (this.props.options.valueUt) {
                    this.setState({valueUt: this.props.options.valueUt});
                }
            }
        });

        var App = React.createClass({
            displayName: 'CouponsApp',
            render: function () {
                //noinspection BadExpressionStatementJS
                var child = "";
                var viewLabel = this.props.options.buttonViewLabel || this.props.buttonViewLabel;

                if (this.state.mode == 1) {
                    child = [React.createElement(UsesBlock, {options: this.props.options, key: "uses-block", labelClass: this.props.labelClass}),
                        React.createElement(SingleCoupon, {key: "single-coupon", options: this.props.options, labelClass: this.props.labelClass, 
                            name: this.props.options['single_coupon_name'], value: this.props.options['single_coupon_code']})];
                } else if (this.state.mode == 2) {
                    var onShowCodes = this.onShowCodes || '',
                        onGenerateCodes = this.onGenerateCodes || '',
                        onImportCodes = this.onImportCodes || '';
                    child = React.createElement(MultiCoupon, {key: "multi-coupon", options: this.props.options, onImportCodes: onImportCodes, 
                        onGenerateCodes: onGenerateCodes, onShowCodes: onShowCodes, labelClass: this.props.labelClass, 
                        buttonViewLabel: viewLabel});
                }
                return (
                    React.createElement("div", {className: "coupon-app"}, 
                        React.createElement("div", {className: "coupon-group"}, 
                        child
                        )
                    )
                );
            },
            getDefaultProps: function () {
                // component default properties
                return {
                    labelClass: labelClass,
                    buttonViewDefaultLabel: Locale._("View Codes")
                }
            },
            getInitialState: function () {
                return {mode: 0};
            },
            componentWillReceiveProps: function (nextProps) {
                this.setState({mode: nextProps.mode});
            },
            componentWillMount: function () {
                this.setState({mode: this.props.mode});
            },
            onShowCodes: function () {
                return this.props.showCodes();
            },
            onGenerateCodes: function () {
                return this.props.generateCodes();
            },
            onImportCodes: function () {
                return this.props.importCodes();
            }
        });

        return {App: App, GenerateForm: GenerateForm};
    }

    var Common = common(React, Components);
    var CouponApp = couponApp(React, $, Locale, Components);
    var Actions = action(React, $, Components, Common, Locale);
    var ConditionsApp = conditions(React, $, Components, Locale, Common);

    $.fn.select2.defaults = $.extend($.fn.select2.defaults, {minimumResultsForSearch: 15, dropdownAutoWidth: true});
    var RulesWidget = {
        $modalContainerCoupons: null,
        $modalContainerConditions: null,
        $modalContainerActions: null,
        init: function (options) {
            $.extend(this.options, options);

            var $promoOptions = $('#' + this.options.promo_serialized);
            if ($promoOptions.length) {
                var val = $promoOptions.val();
                if (val) {
                    try {
                        this.options.promoOptions = JSON.parse(val);
                    } catch (e) {
                        //console.log(e);
                    }
                }
                this.options.promoOptionsEl = $promoOptions;
            }

            this.options.promoOptionsRemoveEl = $('#' + this.options.removed_serialized);

            if (this.options['promo_type_id']) {
                var $promoType = $("#" + this.options['promo_type_id']);
                if ($promoType.length) {
                    this.options.promo_type = $promoType.val();
                    var promo = this;
                    $promoType.on('change', function (e) {
                        // on promo type element change, set resulting promo type to options and render form
                        promo.options.promo_type = $(e.target).val();
                        promo.initAll();
                    });
                }
            }


            this.$modalContainerCoupons = $('<div/>').appendTo(document.body);
            this.$modalContainerConditions = $('<div/>').appendTo(document.body);
            this.$modalContainerActions = $('<div/>').appendTo(document.body);
            this.initAll()
        },
        initAll: function () {
            this.initCouponApp(this.options.coupon_select_id, this.$modalContainerCoupons);
            this.initConditionsApp(this.options.condition_select_id, this.$modalContainerConditions);
            this.initActionsApp(this.options.actions_select_id, this.$modalContainerActions);

            var $conditionsMatch = $('#' + this.options.condition_match_id);
            if ($conditionsMatch.length) {
                $conditionsMatch.on("change", function (e) {
                    this.initConditionsApp(this.options.condition_select_id, this.$modalContainerConditions);
                }.bind(this))
            }
        },
        initActionsApp: function (selector, $modalContainer) {
            var $actionsSelector = $('#' + selector);
            if ($actionsSelector.length == 0) {
                RulesWidget.log("Actions drop-down not found");
                return;
            }
            var $container = $("#" + this.options.actions_container_id);
            var promoActions = this.options.promoOptions['actions'] || {};
            React.render(React.createElement(Actions, {actionType: $actionsSelector, actions: promoActions, onUpdate: this.onActionsUpdate.bind(this), 
                options: this.options, modalContainer: $modalContainer}), $container.get(0));
        },
        initConditionsApp: function (selector, $modalContainer) {
            var $conditionSelector = $('#' + selector);
            if ($conditionSelector.length == 0) {
                this.log("Conditions drop-down not found");
            } else {
                var match = true;
                var $conditionsMatch = $('#' + this.options.condition_match_id);
                var $container = $("#" + this.options.condition_container_id);
                var promoConditions = this.options.promoOptions['conditions'] || {};

                if ($conditionsMatch.length) {
                    match = $conditionsMatch.val();
                }
                var hidden = (match === 'always') || false;

                if (hidden) {
                    $conditionSelector.attr('disabled', true);
                } else {
                    $conditionSelector.attr('disabled', false);
                }
                React.render(React.createElement(ConditionsApp, {conditionType: $conditionSelector, conditions: promoConditions, onUpdate: this.onConditionsUpdate.bind(this), 
                    options: this.options, modalContainer: $modalContainer, hidden: hidden}), $container.get(0));
            }
        },
        initCouponApp: function (selector, $modalContainer) {
            var $couponSelector = $('#' + selector);
            if ($couponSelector.length == 0) {
                this.log("Use coupon drop-down not found");
            } else {
                var $container = $("#" + this.options.coupon_container_id);
                var selected = $couponSelector.val();

                var self = this;
                var callBacks = {
                    showCodes: this.showCodes.bind(self),
                    generateCodes: this.generateCodes.bind(self),
                    importCodes: this.importCodes.bind(self)
                };

                if (selected != 0) {
                    this.createCouponApp($container.get(0), $modalContainer.get(0), callBacks, selected, self.options);
                }

                $couponSelector.on('change', function () {
                    selected = $couponSelector.val();
                    self.createCouponApp($container.get(0), $modalContainer.get(0), callBacks, selected, self.options);
                });
            }
        },
        createCouponApp: function (appContainer, modalContainer, callBacks, mode, options) {
            React.render(React.createElement(CouponApp.App, React.__spread({},  callBacks, {mode: mode, options: options, onUpdate: this.onCouponsUpdate})), appContainer);
            React.render(
                React.createElement("div", {className: "modals-container"}, 
                    React.createElement(Components.Modal, {id: "coupons_grid", title: "Coupon grid", onLoad: this.addShowCodes.bind(this), onConfirm: this.onModalSaveChange.bind(this)}), 
                    React.createElement(Components.Modal, {id: "generate_coupon_grid", title: "Generate coupons", onLoad: this.addGenerateCodes.bind(this), 
                                      onConfirm: this.postGenerate.bind(this), 
                                      confirmClass: "ladda-button", 
                                      confirmAttrs: { 'data-style': 'expand-left'}}, 
                        React.createElement(CouponApp.GenerateForm, {onSubmit: this.postGenerate.bind(this)})
                    ), 
                    React.createElement(Components.Modal, {id: "import_coupon_grid", title: "Import coupons", onLoad: this.addImportCodes.bind(this)})
                ), modalContainer);
        },
        options: {
            coupon_select_id: "model-use_coupon",
            coupon_container_id: "coupon-options",
            actions_select_id: "model-actions",
            condition_select_id: 'model-conditions_type',
            actions_container_id: "actions-options",
            condition_container_id: 'conditions-options',
            actions_add_id: 'action_add',
            condition_add_id: 'condition_action_add',
            promo_serialized: '',
            promoOptions: {},
            debug: false,
            promo_type: 'cart' // default promotion type
        },
        showCodesModal: null,
        generateCodesModal: null,
        importCodesModal: null,
        addShowCodes: function (modal) {
            this.showCodesModal = modal;
        },
        addGenerateCodes: function (modal) {
            this.generateCodesModal = modal;
        },
        addImportCodes: function (modal) {
            this.importCodesModal = modal;
        },
        loadModalContent: function ($modalBody, url, success) {
            if ($modalBody.length > 0 && $modalBody.data('content-loaded') == undefined) {
                $.get(url).done(function (result) {
                    if (result.hasOwnProperty('html')) {
                        $modalBody.html(result.html);
                        $modalBody.data('content-loaded', true);
                        if (typeof success == 'function') {
                            success($modalBody);
                        }
                    }
                }).fail(function (result) {
                    if (!result.hasOwnProperty('responseJSON')) {
                        this.log(result);
                    }
                    var jsonResult = result['responseJSON'];
                    if (jsonResult.hasOwnProperty('html')) {
                        $modalBody.html(jsonResult.html);
                    }
                });
            }
        },
        showCodes: function () {
            var modal = this.showCodesModal;
            if (null == modal) {
                this.log("Modal not loaded");
                return;
            }
            //this.log("showCodes");
            var $modalBody = $('.modal-body', modal.getDOMNode());
            this.loadModalContent($modalBody, this.options.showCouponsUrl);
            modal.open();
        },
        generateCodes: function () {
            var modal = this.generateCodesModal;
            if (null == modal) {
                this.log("Modal not loaded");
                return;
            }
            // component default properties
            //this.log("generateCodes");
            //this.refs.generateModal.open();
            modal.open();
            var $formContainer = $('#coupon-generate-container');
            var $codeLength = $('#model-code_length');
            var $codePattern = $('#model-code_pattern');
            if ($.trim($codePattern.val()) == '') { // code length should be settable only if no pattern is provided
                $codeLength.prop('disabled', false);
            }
            $codePattern.change(function (e) {
                RulesWidget.log(e);
                var val = $.trim($codePattern.val());
                if (val == '') {
                    $codeLength.prop('disabled', false);
                } else {
                    $codeLength.prop('disabled', true);
                    $codePattern.val(val);
                }
            });
        },
        postGenerate: function (e) {
            var $formContainer = $('#coupon-generate-container');
            //Promo.log(e, $formContainer);
            var url = this.options['generateCouponsUrl'];
            var $progress = $formContainer.find('.loading');
            var $result = $formContainer.find('.result').hide();
            var loader = Ladda.create(e.getDOMNode().querySelector('.ladda-button'));
            $progress.show();

            var $meta = $('meta[name="csrf-token"]');
            var data = {};
            if ($meta.length) {
                data["X-CSRF-TOKEN"] = $meta.attr('content');
            }

            $formContainer.find('input').each(function () {
                var $self = $(this);
                var name = $self.attr('name');
                data[name] = $self.val();
            });

            // show indication that something happens?
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: data,
                beforeSend: function () {
                    loader.start();
                }
            })
            .done(function (result) {
                var status = result.status;
                var message = result.message;
                $.bootstrapGrowl(message, {type: 'success', align: 'center', width: 'auto'});
                $result.text(message);
                if (status != 'error') {
                    var newRows = result['codes'].map(function (e, i) {
                        //console.log(e, i);
                        return {
                            code: e,
                            total_used: 0
                        }
                    });
                    //console.log(newRows);
                    var grid_id = result['grid_id'];
                    RulesWidget.updateGrid(grid_id, newRows);
                }
            })
            .always(function (r) {
                $progress.hide();
                $result.show();
                if ($.isFunction(e.close)) {
                    // e is the modal object
                    var modal = e;
                    setTimeout(function () {
                        modal.close();
                        loader.stop();
                    }, 2000);
                    //e.close();//close it
                }
                // hide notification
                RulesWidget.log(r);
            });

            if ($.isFunction(e.preventDefault)) {
                e.preventDefault();
            }
        },
        importCodes: function () {
            var modal = this.importCodesModal;
            if (null == modal) {
                this.log("Modal not loaded");
                return;
            }
            // component default properties
            this.log("importCodes");
            modal.open();
            //this.refs.importModal.open();
            var $modalBody = $('.modal-body', modal.getDOMNode());
            this.loadModalContent($modalBody, this.options['importCouponsUrl']);
            $(document).on("coupon_import", function (event) {
                //console.log(event.codes);
                RulesWidget.updateGrid(event.grid_id, event.codes);
            });
        },
        log: function (msg) {
            if (this.options.debug) {
                console.log(msg);
            }
        },
        mergeResults: function () {
            var result = [], bitSet = {}, arr, len;
            var checker = arguments[arguments.length - 1]; // function to check if item is in set
            if (!$.isFunction(checker)) {
                throw "Last argument must be a function.";
            }
            for (var i = 0; i < (arguments.length - 1); i++) {
                arr = arguments[i];
                if (!arr instanceof Array) {
                    continue;
                }
                len = arr.length;
                while (len--) {
                    var itm = arr[len];
                    if (!checker(itm, bitSet)) {
                        result.unshift(itm);
                    }
                }
            }
            return result;
        },
        search: function (params, url, callback) {
            params.q = params.term || '*'; // '*' means default search
            params.page = params.page || 1;
            params.o = params.limit || 100;

            params.searchedTerms = params.searchedTerms || {};
            if (params.searchedTerms['*'] && params.searchedTerms['*'].loaded == 2) {
                // if default search already returned all results, no need to go back to server
                params.searchedTerms[params.term] = params.searchedTerms['*'];
            }
            var termStatus = params.searchedTerms[params.term];
            if (termStatus == undefined || (termStatus.loaded == 1 && termStatus.page < params.page)) { // if this is first load, or there are more pages and we're looking for next page
                if (termStatus == undefined) {
                    params.searchedTerms[params.term] = {};
                }
                $.get(url, {page: params.page, q: params.q, o: params.o})
                    .done(function (result) {
                        if (result.hasOwnProperty('total_count')) {
                            //console.log(result['total_count']);
                            var more = params.page * params.o < result['total_count'];
                            params.searchedTerms[params.term].loaded = (more) ? 1 : 2; // 1 means more results to be fetched, 2 means all fetched
                            params.searchedTerms[params.term].page = params.page; // 1 means more results to be fetched, 2 means all fetched
                        }
                        callback(result, params);
                    })
                    .fail(function (result) {
                        callback(result, params);
                    });
            } else if (termStatus.loaded == 2 || (termStatus.page >= params.page)) {
                callback('local', params); // find results from local storage
            } else {
                console.error("UNKNOWN search status.")
            }
        },
        updateGrid: function (grid_id, newRows) {
            var grid = window[grid_id];
            this.onCouponsUpdate(newRows);
            if (grid) {
                //console.log("grid found, adding to grid");
                RulesWidget.addGridRows(grid, newRows);
            } else {
                //console.log("grid not loaded yet, adding to store");
                var codes = store.get('promo.coupons'); // check of there are other codes stored and if yes, merge them
                if (codes) {
                    codes = JSON.parse(codes);
                    newRows = codes.concat(newRows);
                }
                store.set('promo.coupons', JSON.stringify(newRows));
            }
        },
        addGridRows: function (grid, rows) {
            var gridRows = grid.getRows();
            var newRows = rows.filter(function (row, idx) {
                row.id = row.code; // instead of worrying for duplicate codes, make the code the id and effectively update the duplicates instead of detecting them
                return row;
            });
            //gridRows.add(newRows, {merge: true}).trigger('build');
            grid.addRows(newRows);
            $(document).trigger({ // trigger event which will upgrade the grid
                type: "grid_count_update",
                numCodes: grid.getRows().length
            });
        },
        removeGridRows: function (grid, removedRows) {
            var addedCoupons = this.options.promoOptions['coupons'];
            _(removedRows).each(function (row) {
                var index = _.findIndex(addedCoupons, {code: row.id});
                if (index != -1) {
                    this.options.promoOptions['coupons'].splice(index, 1);
                } else {
                    if (!this.options.promoOptions['coupons_removed']) {
                        this.options.promoOptions['coupons_removed'] = [];
                    }
                    this.options.promoOptions['coupons_removed'].push(row.id);
                }
            }.bind(this));

            $(document).trigger({ // trigger event which will upgrade the grid
                type: "grid_count_update",
                numCodes: grid.getRows().length
            });
        },
        onModalSaveChange: function (modal) {
            this.updatePromoOptions();
            modal.close();
        },
        onActionsUpdate: function (e) {
            //console.log(e);
            if (this.options.promoOptions['actions'] == undefined) {
                this.options.promoOptions['actions'] = {};
            }
            this.options.promoOptions['actions']['rules'] = e['rules'];
            this.updatePromoOptions();
        },
        onConditionsUpdate: function (e) {
            //console.log(e);
            if (this.options.promoOptions['conditions'] == undefined) {
                this.options.promoOptions['conditions'] = {};
            }
            this.options.promoOptions['conditions']['rules'] = e['rules'];
            this.updatePromoOptions();
        },
        onCouponsUpdate: function (newRows) {
            //console.log(newRows);
            if (this.options.promoOptions['coupons'] == undefined) {
                this.options.promoOptions['coupons'] = [];
            }
            this.options.promoOptions['coupons'] = this.options.promoOptions['coupons'].concat(newRows);
            this.updatePromoOptions();
        },
        updatePromoOptions: function () {
            var values = this.options.promoOptions;
            if (!this.options.promoOptionsEl) return;
            this.options.promoOptionsEl.val(JSON.stringify(values));
        }
    };

    window.couponsGridRegister = function (grid) {
        window[grid.getConfig('id')] = grid;
        var newRows = store.get('promo.coupons');
        store.remove('promo.coupons');
        if (newRows) {
            newRows = JSON.parse(newRows);
            RulesWidget.addGridRows(grid, newRows);
        }

        $(grid.getDOMNode())
            .on('removedRows.griddle', function (e, removedRows) {
                // Removed rows on coupon grid
                RulesWidget.removeGridRows(grid, removedRows);
            })
            .on('addedRows.griddle', function (e, addedRows) {
                // Added rows on coupon grid
            });
    };

    return RulesWidget;
});
