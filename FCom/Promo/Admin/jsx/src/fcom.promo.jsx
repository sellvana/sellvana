/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!griddle', 'jsx!fcom.components', 'jsx!fcom.promo.actions', 'jsx!fcom.promo.coupon', 'fcom.locale',
    'select2', 'bootstrap', 'moment', 'daterangepicker'], function (React, $, Griddle, Components, Actions, CouponApp, Locale) {
    var DelBtn = React.createClass({
        render: function () {
            return (
                <Components.Button className="btn-link btn-delete" onClick={this.props.onClick}
                        type="button" style={ {paddingRight:10, paddingLeft:10} }>
                    <span className="icon-trash"></span>
                </Components.Button>
            );
        }
    });

    var ConditionsRow = React.createClass({
        render: function () {
            var cls = "form-group condition";
            if(this.props.rowClass) {
                cls += " " + this.props.rowClass;
            }
            return (<div className={cls}>
                <div className="col-md-3">
                    <Components.ControlLabel label_class="pull-right">{this.props.label}<DelBtn onClick={this.props.onDelete}/></Components.ControlLabel>
                </div>
                {this.props.children}
            </div>);
        }
    });

    var ConditionsCompare = React.createClass({
        render: function () {
            return (
                <select className="to-select2 form-control" onChange={this.props.onChange} id={this.props.id}>
                    {this.props.opts.map(function(type){
                        return <option value={type.id} key={type.id}>{type.label}</option>
                    })}
                </select>
            );
        },
        getDefaultProps: function () {
            return {
                opts: [
                    {id:"gt", label: "is greater than"},
                    {id:"gte", label: "is greater than or equal to"},
                    {id:"lt", label: "is less than"},
                    {id:"lte", label: "is less than or equal to"},
                    {id:"eq", label: "is equal to"},
                    {id:"neq", label: "is not equal to"}
                ]
            };
        },
        componentDidMount: function () {
            $(this.getDOMNode()).select2({minimumResultsForSearch: 15}).on('change', this.props.onChange);
        }
    });

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
                totalType: [{id:"qty", label:"TOTAL QTY"}, {id:"amt", label:"TOTAL $Amount"}],
                select2: true,
                containerClass: "col-md-2"
            };
        }
    });

    var AddFieldButton = React.createClass({
        render: function () {
            return (
                <Components.Button onClick={this.props.onClick} className="btn-link pull-left" type="button" style={ {paddingRight:10, paddingLeft:10} }>
                    <span aria-hidden="true" className="glyphicon glyphicon glyphicon-plus-sign"></span>
                </Components.Button>
            );
        }
    });

    var removeConditionMixin = {
        remove: function () {
            if (this.props.removeCondition) {
                this.props.removeCondition(this.props.id);
            }
        }
    };

    var select2QueryMixin = {
        select2query: function (options) {
            var self = this;
            var $el = $(options.element);
            var values = $el.data('searches') || [];
            var flags = $el.data('flags') || {};
            var term = options.term || '*';
            var page = options.page;
            console.log(page);
            var data;
            if (flags[term] != undefined && flags[term].loaded == 2) {
                data = {results: self.searchLocal(term, values, page, 100), more: (flags[term].page > page)};
                options.callback(data);
            } else {
                Promo.search({term: term, page: page, searchedTerms: flags}, this.url, function (result, params) {
                    var more;
                    if (result == 'local') {
                        more = (params.searchedTerms[term].page > params.page) || (params.searchedTerms[term].loaded == 1);
                        data = {results: self.searchLocal(params.term, values, params.page, params.o), more: more};
                        options.callback(data);
                    } else if (result.items !== undefined) {
                        more = params.searchedTerms[term].loaded === 1;
                        data = {results: result.items, more: more};
                        flags[term] = params.searchedTerms[term];
                        values = Promo.mergeResults(values, data.results, function (item, bitSet) {
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
                    if(!test && val.hasOwnProperty('sku')) {
                        test = regex.test(val['sku']);
                    }
                    if (test) {
//                                    console.log(term + ' matches ' + val.text);
                        counted++; // up the counter
                    }
                } else {
                    counted++; // no regex, just return matching items by position
                    test = true;
                }
                return test && counted >= offset && counted < max;// if term is not for this page, skip it
            });
            return matches;
        }
    };

    // condition to apply to the selection of products
    var ConditionSkuCollection = React.createClass({
        mixins: [removeConditionMixin, select2QueryMixin],
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="skuCollectionType" id="skuCollectionType"> of </ConditionsType>
                    <div className="col-md-2"><input type="hidden" id="skuCollectionIds" ref="skuCollectionIds" className="form-control"/></div>
                    <div className="col-md-2"><ConditionsCompare ref="skuCollectionCond" id="skuCollectionCond" /></div>
                    <div className="col-md-1"><input className="form-control pull-left" ref="skuCollectionValue" id="skuCollectionValue" type="text"/></div>
                </ConditionsRow>
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
                    return item.sku;
                },
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.text + '">' +
                        '<div class="span2">ID: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: ' + item.text.substr(0, 20);
                        if(item.text.length > 20) {
                            markup += '...';
                        }
                        markup += '</div>' +
                        '<div class="span2">SKU: <strong>' + item.sku + '</strong></div>' +
                        '</div>';

                    return markup;
                },
                query: self.select2query
            });
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15});
        }
    });

    // condition to apply to products which match the attributes condition configured here
    var ConditionAttributeCombination = React.createClass({
        mixins: [removeConditionMixin],
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-5"><input type="text" readOnly="readonly" ref="attributesResume" id="attributesResume" className="form-control" value={this.state.valueText}/></div>
                    <div className="col-md-4"><Components.Button type="button" className="btn-primary"
                       ref={this.props.configureId} onClick={this.handleConfigure}>Configure</Components.Button></div>
                </ConditionsRow>
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
            Promo.log("Clicked conditions");
            var modal = <Components.Modal onConfirm={this.handleConditionsConfirm}
                title="Product Combination Configuration" onLoad={this.registerModal} onUpdate={this.registerModal}>
                <ConditionsAttributesModalContent  baseUrl={this.props.options.base_url} idVar={this.props.options.id_var}
                    entityId={this.props.options.entity_id} onLoad={this.registerModalContent} />
            </Components.Modal>;

            React.render(modal, this.props.modalContainer.get(0));
        },
        handleConditionsConfirm: function (modal) {
            Promo.log('handling');
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

    // content of the modal used to configure attribute combination
    var ConditionsAttributesModalContent = React.createClass({
        mixins: [select2QueryMixin],
        render: function () {
            var fieldUrl = this.props.baseUrl + this.props.urlField;
            var paramObj = {};
            paramObj[this.props.idVar] = this.props.entityId;
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
                            <AddFieldButton onClick={this.addField}/>
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
            var text = '', glue, fieldTexts = [];
            var allShouldMatch = $(this.refs['combinationType'].getDOMNode()).val(); // && or ||
            if(allShouldMatch == 1) {
                glue = " and ";
            } else {
                glue = " or ";
            }

            for(var field in this.refs) {
                if(field == 'combinationType' || field == 'combinationField') {
                    continue;
                }
                if(this.refs[field]) {
                    var ref = this.refs[field];
                    var refValue = this.state.values["fieldCombination." + field];
                    var refText = ref.serializeText();
                    fieldTexts.push(refText);
                }
            }

            text = fieldTexts.join(glue);

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

    var ConditionsAttributesModalField = React.createClass({
        mixins:[select2QueryMixin],
        render: function () {
            var inputType = this.props.input;
            var opts = this.getOpts();
            var fieldId = "fieldCombination." + this.props.id;
            var input = <input className="form-control required" type="text" id={fieldId} ref={fieldId} onChange={this.onChange}/>;
            if(this.props.numeric_inputs.indexOf(inputType) != -1) {
                if (inputType == 'number') {
                    if(this.state.range === false) {
                        input = <input className="form-control required" type="number" step="any" id={fieldId} ref={fieldId} style={{width: "auto"}} onChange={this.onChange}/>;
                    } else {
                        input = <div id={fieldId} ref={fieldId} className="input-group">
                            <input className="form-control required" type="number" step="any" placeholder="Min" style={{width: "50%"}} onChange={this.onChange}/>
                            <input className="form-control required" type="number" step="any" placeholder="Max" style={{width: "50%"}} onChange={this.onChange}/>
                        </div>;
                    }
                } else if (inputType == 'date' || inputType == 'time') {
                    var singleMode = true;
                    if (this.state.range === true) {
                        singleMode = false;
                    }
                    input = <div className="input-group">
                            <span className="input-group-addon"><i className="glyphicon glyphicon-calendar"></i></span>
                            <input className="form-control required" type="text" id={fieldId} ref={fieldId} dataMode={singleMode} onChange={this.onChange}/>
                        </div>
                }
            } else if(inputType == 'select'){
                input = <input className="form-control required" type="hidden" id={fieldId} ref={fieldId}/>;
            } else if(this.props.bool_inputs.indexOf(inputType) != -1) {
                input = <Components.YesNo  id={fieldId} ref={fieldId} onChange={this.onChange}/>;
            }
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <ConditionsCompare opts={ opts } id={"fieldCompare." + this.props.id} ref={"fieldCompare." + this.props.id} onChange={this.onCompareChange}/>
                    </div>
                    <div className="col-md-5">{input}</div>
                </ConditionsRow>
            );
        },
        values: {},
        getOpts: function () {
            var opts = this.props.opts;
            if(this.props.input == 'text') {
                opts = opts.concat(this.props.opts_text);
            }

            return opts;
        },
        serialize: function () {
            return this.values;
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

            var value = this.values['fieldCombination'];
            if(value) {
                if($.isArray(value)) {
                    value = value.join(", ");
                }

                text += " " + value;
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
            //$('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15}).on('change', this.onCompareChange);
        },
        componentDidUpdate: function () {
            this.componentDidMount();
        },
        onChange: function (e) {
            // only select2 event has e.val, for dom inputs it must be added
            if(!e.val) { // for native inputs, use blur event to capture value
                var $elem = $(e.target);
                e.val = $elem.val();
            }
            //console.log(e);
            this.values['fieldCombination'] = e.val;
            if(this.props.onChange) {
                this.props.onChange(e);
            }
        },
        onCompareChange: function (e) {
            this.values['fieldCompare'] = e.val;
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
            }).on('change', this.onChange);
        }
    });

    var ConditionCategories = React.createClass({
        mixins: [removeConditionMixin, select2QueryMixin],
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="catProductsType" id="catProductsType" > of products in </ConditionsType>
                    <div className="col-md-2"><input type="hidden" id="catProductsIds" ref="catProductsIds" className="form-control"/></div>
                    <div className="col-md-2"><ConditionsCompare ref="catProductsCond" id="catProductsCond" /></div>
                    <div className="col-md-1"><input ref="catProductsValue" id="catProductsValue" type="text" className="form-control pull-left"/></div>
                </ConditionsRow>
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
        url:'',
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
            $('.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15});
        }
    });

    var ConditionTotal = React.createClass({
        mixins: [removeConditionMixin],
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <ConditionsType ref="cartTotalType" id="cartTotalType" totalType={this.props.totalType}/>
                    <div className="col-md-2"><ConditionsCompare ref="cartTotalCond" id="cartTotalCond" /></div>
                    <div className="col-md-1"><input ref="cartTotalValue" id="cartTotalValue" type="text" className="form-control pull-left"/></div>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                rowClass: "cart-total",
                totalType: [{id:"qty", label:"QTY OF ITEMS"}, {id:"amt", label:"$Value/Amount OF ITEMS"}],
                label: "Cart Total",
                type: 'total'
            };
        },
        componentDidMount: function() {
            $('.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15});
        }
    });

    var ConditionShipping = React.createClass({
        mixins: [removeConditionMixin],
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-5"><textarea ref="shippingResume" id="shippingResume"
                            readOnly="readonly" value={this.state.value} className="form-control"/></div>
                    <div className="col-md-4"> <Components.Button type="button" className="btn-primary pull-left" ref={this.props.configureId}
                        onClick={this.handleConfigure}>Configure</Components.Button></div>
                </ConditionsRow>
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
            return {value: ""};
        },
        modal: null,
        handleConfigure: function (e) {
            Promo.log("Clicked");
            var modal = <Components.Modal onConfirm={this.handleShippingConfirm}
                title={this.props.modalTitle} onLoad={this.openModal} onUpdate={this.openModal}>
                <ConditionsShippingModalContent baseUrl={this.props.options.base_url} idVar={this.props.options.id_var}
                    entityId={this.props.options.entity_id}/>
            </Components.Modal>;

            React.render(modal, this.props.modalContainer.get(0));
        },
        handleShippingConfirm: function (modal) {
            Promo.log('handling');
            modal.close();
        },
        registerModal: function (modal) {
            this.modal = modal;
            this.openModal(modal);
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
                            <select ref="combinationField" className="form-control to-select2">
                                <option value="">{this.props.labelCombinationField}</option>
                                {this.props.fields.map(function (field) {
                                    return <option value={field.field} key={field.field}>{field.label}</option>
                                })}
                            </select>
                        </div>
                        <div className="col-md-2">
                            <AddFieldButton onClick={this.addField}/>
                        </div>
                    </div>
                    {this.state.fields.map(function (field) {
                        paramObj['field'] = field.field;
                        var url = fieldUrl + '/?' + $.param(paramObj);
                        return <ConditionsShippingModalField label={field.label} url={url} key={field.field}
                            id={field.field} removeField={this.removeField} />
                    }.bind(this))}
                </div>
            );
        },
        addField: function () {
            var fieldCombination = this.refs.combinationField.getDOMNode();
            var fieldValue = $(fieldCombination).select2("data");
            if(null == fieldValue || fieldValue == [] || fieldValue.id == "") {
                return;
            }
            var fields = this.state.fields;
            for(var i in fields) {
                if (fields.hasOwnProperty(i)) {
                    var f = fields[i];
                    if(f.field == fieldValue.id) {
                        return;
                    }
                }
            }
            var field = {label: fieldValue.text, field: fieldValue.id};
            console.log(fields.indexOf(field));
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
            return {fields: []};
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
            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15});
        }
    });

    var ConditionsShippingModalField = React.createClass({
        mixins:[select2QueryMixin],
        render: function () {
            var fieldId = "fieldCombination." + this.props.id;
            var input = <input className="form-control" type="hidden" id={fieldId} key={fieldId} ref={fieldId}/>;
            var helperBlock = '';
            if(this.props.id == 'zipcode') {
                helperBlock = <span key={fieldId + '.help'} className="help-block">{this.props.zipHelperText }</span>;
            }
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label} onDelete={this.remove}>
                    <div className="col-md-4">
                        <ConditionsCompare opts={this.props.opts} id="fieldCompare" ref="fieldCompare"/>
                    </div>
                    <div className="col-md-5">{[input, helperBlock]}</div>
                </ConditionsRow>
            );
        },
        remove: function () {
            if(this.props.removeField) {
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
                    {id:"in", label: "is one of"},
                    {id:"not_in", label: "is not one of"}
                ]
            };
        },
        url:'',
        componentDidMount: function () {
            var fieldCombination = this.refs['fieldCombination.' + this.props.id];
            var self = this;
            this.url = this.props.url;
            if (this.props.id != 'zipcode') {
                $(fieldCombination.getDOMNode()).select2({
                    placeholder: self.props.fcLabel,
                    maximumSelectionSize: 4,
                    multiple: true,
                    selectOnBlur: true,
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
                });
            } else {
                $(fieldCombination.getDOMNode()).select2({
                    tags: [],
                    tokenSeparators: [',']
                });
            }
        }
    });

    var ConditionsApp = React.createClass({
        render: function () {
            return (<div className="conditions panel panel-default">
                    {this.state.data.map(function (field, i) {
                        //todo make a field based on field
                        var el;
                        var key = field.id;
                        switch(field.type){
                            case 'skus':
                                el = <ConditionSkuCollection options={this.props.options} key={key} id={key} removeCondition={this.removeCondition}/>;
                                break;
                            case 'cats':
                                el = <ConditionCategories options={this.props.options} key={key} id={key} removeCondition={this.removeCondition}/>;
                                break;
                            case 'total':
                                el = <ConditionTotal options={this.props.options} key={key} id={key} removeCondition={this.removeCondition}/>;
                                break;
                            case 'comb':
                                el = <ConditionAttributeCombination options={this.props.options}
                                    modalContainer={this.props.modalContainer} key={key} id={key} removeCondition={this.removeCondition}/>;
                                break;
                            case 'shipping':
                                el = <ConditionShipping options={this.props.options}
                                    modalContainer={this.props.modalContainer} key={key} id={key} removeCondition={this.removeCondition}/>;
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
                    Promo.log(e);
                }
            }

            $('#' + this.props.newCondition).on('click', this.addCondition);

            $('select.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15});
        },
        addCondition: function () {
            // add condition data to state
            var $conditionTypes = this.props.conditionType;
            if($conditionTypes.length == 0) {
                return;
            }

            var conditionType = $conditionTypes.val();
            var data = this.state.data;
            var condition = {type: conditionType, id: conditionType + '-' + this.state.lastConditionId};
            data.push(condition);
            this.setState({data: data, lastConditionId: (this.state.lastConditionId + 1)});
        },
        removeCondition: function (conditionId) {
            var data = this.state.data;
            data = data.filter(function (field) {
                return field.id != conditionId;
            });
            this.setState({data: data});
        },
        getInitialState: function () {
            return {
                data: [],
                lastConditionId: 0
            };
        }
    });

    var Promo = {
        createButton: function () {
            React.render(<Button label="Hello button"/>, document.getElementById('testbed'));
        },
        createGrid: function() {
            React.render(<Griddle/>, document.getElementById('testbed'));
        },
        init: function (options) {
            $.extend(this.options, options);
            var $modalContainer = $('<div/>').appendTo(document.body);
            this.initCouponApp(this.options.coupon_select_id, $modalContainer);
            this.initConditionsApp(this.options.condition_select_id, $modalContainer);
            this.initActionsApp(this.options.actions_select_id, $modalContainer);
        },
        initActionsApp: function (selector, $modalContainer) {
            var $actionsSelector = $('#' + selector);
            if ($actionsSelector.length == 0) {
                Promo.log("Actions drop-down not found");
                return;
            }
            var $container = $("#" + this.options.actions_container_id);
            React.render(<Actions actionType={$actionsSelector} newAction={this.options.actions_add_id}
                            options={this.options} modalContainer={$modalContainer}/>, $container.get(0));
        },
        initConditionsApp: function (selector, $modalContainer) {
            var $conditionSelector = $('#' + selector);
            if ($conditionSelector.length == 0) {
                this.log("Conditions drop-down not found");
            } else {
                var $container = $("#" + this.options.condition_container_id);
                React.render(<ConditionsApp conditionType={$conditionSelector} newCondition={this.options.condition_add_id}
                    options={this.options} modalContainer={$modalContainer}/>,$container.get(0));
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
            React.render(<CouponApp.App {...callBacks} mode={mode} options={options}/>, appContainer);
            React.render(
                <div className="modals-container">
                    <Components.Modal title="Coupon grid" onLoad={this.addShowCodes.bind(this)}/>
                    <Components.Modal title="Generate coupons" onLoad={this.addGenerateCodes.bind(this)}>
                        <CouponApp.GenerateForm onSubmit={this.postGenerate.bind(this)}/>
                    </Components.Modal>
                    <Components.Modal title="Import coupons" onLoad={this.addImportCodes.bind(this)}/>
                </div>, modalContainer);
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
            conditions_serialized: 'conditions_serialized',
            debug: false
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
            if(null == modal) {
                this.log("Modal not loaded");
                return;
            }
            this.log("showCodes");
            modal.open();
            var $modalBody = $('.modal-body', modal.getDOMNode());
            this.loadModalContent($modalBody, this.options.showCouponsUrl)
        },
        generateCodes: function () {
            var modal = this.generateCodesModal;
            if(null == modal) {
                this.log("Modal not loaded");
                return;
            }
            // component default properties
            this.log("generateCodes");
            //this.refs.generateModal.open();
            modal.open();
            var $formContainer = $('#coupon-generate-container');
            var $codeLength = $('#model-code_length');
            var $codePattern = $('#model-code_pattern');
            if ($.trim($codePattern.val()) == '') { // code length should be settable only if no pattern is provided
                $codeLength.prop('disabled', false);
            }
            $codePattern.change(function (e) {
                Promo.log(e);
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
            Promo.log(e, $formContainer);
            var url = this.options.generateCouponsUrl;
            var $progress = $formContainer.find('.loading');
            var $result = $formContainer.find('.result').hide();
            $progress.show();
            //$button.click(function (e) {
            e.preventDefault();
            var $meta = $('meta[name="csrf-token"]');
            var data = {};
            if($meta.length) {
                data["X-CSRF-TOKEN"] = $meta.attr('content');
            }
            $formContainer.find('input').each(function () {
                var $self = $(this);
                var name = $self.attr('name');
                data[name] = $self.val();
            });
            // show indication that something happens?
            $.post(url, data)
                .done(function (result) {
                    var status = result.status;
                    var message = result.message;
                    $result.text(message);
                })
                .always(function (r) {
                    $progress.hide();
                    $result.show();
                    // hide notification
                    Promo.log(r);
                });
            //});
        },
        importCodes: function () {
            var modal = this.importCodesModal;
            if(null == modal) {
                this.log("Modal not loaded");
                return;
            }
            // component default properties
            this.log("importCodes");
            modal.open();
            //this.refs.importModal.open();
            var $modalBody = $('.modal-body', modal.getDOMNode());
            this.loadModalContent($modalBody, this.options.importCouponsUrl);
        },
        log: function (msg) {
            if(this.options.debug) {
                console.log(msg);
            }
        },
        mergeResults: function () {
            var result = [], bitSet = {}, arr, len;
            var checker = arguments[arguments.length - 1]; // function to check if item is in set
            if(!$.isFunction(checker)) {
                throw "Last argument must be a function.";
            }
            for(var i = 0; i < (arguments.length - 1); i++){
                arr = arguments[i];
                if(!arr instanceof Array) {
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
            if(params.searchedTerms['*'] && params.searchedTerms['*'].loaded == 2) {
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
                            console.log(result['total_count']);
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
        }
    };
    return Promo;
});
