/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!griddle', 'jsx!fcom.components', 'fcom.locale', 'select2', 'bootstrap'], function (React, $, Griddle, Components, Locale) {
    var labelClass = "col-md-3";
    var SingleCoupon = React.createClass({
        render: function () {
            return (
                <div className="single-coupon form-group">
                    <Components.ControlLabel input_id={this.props.id} label_class={this.props.labelClass}>
                        {this.props.labelText}<Components.HelpIcon id={"help-" + this.props.id} content={this.props.helpText}/>
                    </Components.ControlLabel>
                    <div className="col-md-5">
                        <input id={this.props.id} ref={this.props.name} className="form-control"/>
                        <span className="help-block">{this.props.helpText}</span>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                id: "model-use_coupon_code_single",
                name: "use_coupon_code_single",
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
    var GenerateForm = React.createClass({
        render: function () {
            return (
                <div className="f-section" id="coupon-generate-container">
                    <div className="well well-sm help-block" style={{fontSize:12}}>
                        <p>{Locale._("You can have unique coupon codes generated for you automatically if you input simple patterns.")}</p>
                        <p>{Locale._("Pattern examples:")}</p>
                        <p><code>&#123;U8&#125;</code>{Locale._(" - 8 upper case alpha chars - will result to something like ")}<code>DKABWJKQ</code></p>
                        <p><code>&#123;l6&#125;</code>{Locale._(" - 6 lower case alpha chars - will result to something like ")}<code>dkabkq</code></p>
                        <p><code>&#123;D4&#125;</code>{Locale._(" - 4 digits - will result to something like ")}<code>5640</code></p>
                        <p><code>&#123;UD5&#125;</code>{Locale._(" - 5 alphanumeric (upper case) - will result to something like ")}<code>GHG76</code></p>
                        <p><code>&#123;ULD5&#125;</code>{Locale._(" - 5 alphanumeric (mixed case) - will result to something like ")}<code>GhG76</code></p>
                        <p><code>CODE-&#123;U4&#125;-&#123;UD6&#125;</code> - <code>CODE-HQNB-8A1NO3</code></p>
                        <p>Locale._("Note: dynamic parts of the code MUST be enclosed in &#123;&#125;")</p>
                    </div>
                    <div id="coupon-generate-container" ref="formContainer" className="form-horizontal">
                        <Components.Input field="code_pattern" label={Locale._("Code Pattern")}
                            helpBlockText={Locale._("(Leave empty to auto-generate)")}
                            inputDivClass='col-md-8' label_class='col-md-4'/>
                        <Components.Input field="code_length" label={Locale._("Coupon Code Length")}
                            helpBlockText={Locale._("(Will be used only if auto-generating codes)")}
                            inputDivClass='col-md-8' label_class='col-md-4'/>
                        <Components.Input field="coupon_count" label={Locale._("How many to generate")}
                            inputDivClass='col-md-8' label_class='col-md-4' inputValue="1" required/>
                        <div className={this.props.groupClass}>
                            <div className="col-md-offset-4">
                                <Components.Button type="button" id="coupon-generate-btn" onClick={this.handleGenerateClick}
                                    className="btn-danger btn-post">{Locale._("Generate")}</Components.Button>
                                <span style={{display: 'none', marginLeft: 20}} className="loading">Loading ... </span>
                                <span style={{display: 'none', marginLeft: 20}} className="result"></span>
                            </div>
                        </div>
                    </div>
                </div>
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

    var MultiCoupon = React.createClass({
        render: function () {
            return (
                <div className="multi-coupon form-group" style={{margin: "15px 0"}}>
                    <div className="btn-group col-md-offset-3">
                        <Components.Button onClick={this.props.onShowCodes} className="btn-primary" type="button">{this.props.buttonViewLabel}</Components.Button>
                        <Components.Button onClick={this.props.onGenerateCodes} className="btn-primary" type="button">{this.props.buttonGenerateLabel}</Components.Button>
                        <Components.Button onClick={this.props.onImportCodes} className="btn-primary" type="button">{this.props.buttonImportLabel}</Components.Button>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                buttonViewLabel: Locale._("View (100) codes"),
                buttonGenerateLabel: Locale._("Generate New Codes"),
                buttonImportLabel: Locale._("Import Existing Codes")
            }
        }
    });

    var UsesBlock = React.createClass({
        render: function () {
            return (
                <div className="uses-block form-group" style={{clear: 'both'}}>
                    <Components.ControlLabel input_id={this.props.idUpc} label_class={this.props.labelClass}>
                        {this.props.labelUpc}<Components.HelpIcon id={"help-" + this.props.idUpc} content={this.props.helpTextUpc}/>
                    </Components.ControlLabel>
                    <div className="col-md-2">
                        <input type="text" id={this.props.idUpc} ref="uses_pc" className="form-control"
                            value={this.state.valueUpc}/>
                    </div>

                    <Components.ControlLabel input_id={this.props.idUt}>
                        {this.props.labelUt}<Components.HelpIcon id={"help-" + this.props.idUt} content={this.props.helpTextUt}/>
                    </Components.ControlLabel>

                    <div className="col-md-2">
                        <input type="text" id={this.props.idUt} ref="uses_pc" className="form-control"
                            value={this.state.valueUt}/>
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                labelUpc: Locale._("Uses Per Customer"),
                labelUt: Locale._("Total Uses"),
                idUpc: "coupon_uses_per_customer",
                idUt: "coupon_uses_total",
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
            if(this.props.options.valueUpc) {
                this.setState({valueUpc: this.props.options.valueUpc});
            }
            if(this.props.options.valueUt) {
                this.setState({valueUt: this.props.options.valueUt});
            }
        }
    });

    var CouponApp = React.createClass({
        displayName: 'CouponApp',
        render: function () {
            //noinspection BadExpressionStatementJS
            var child = "";

            if (this.state.mode == 1) {
                child = [<UsesBlock options={this.props.options} key="uses-block" labelClass={this.props.labelClass}/>,
                    <SingleCoupon key="single-coupon" options={this.props.options} labelClass={this.props.labelClass}/>];
            } else if(this.state.mode == 2) {
                var onShowCodes = this.onShowCodes ||'',
                    onGenerateCodes = this.onGenerateCodes ||'',
                    onImportCodes = this.onImportCodes ||'';
                child = [<UsesBlock options={this.props.options} key="uses-block" labelClass={this.props.labelClass}/>,
                    <MultiCoupon key="multi-coupon" options={this.props.options} onImportCodes={onImportCodes}
                    onGenerateCodes={onGenerateCodes} onShowCodes={onShowCodes} labelClass={this.props.labelClass}/>]
            }
            return (
                <div className="coupon-app">
                    <div className="coupon-group">
                        {child}
                    </div>
                </div>
            );
        },
        getDefaultProps: function () {
            // component default properties
            return {
                labelClass: labelClass
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

    var DelBtn = React.createClass({
        render: function () {
            return (
                <Components.Button className="btn-link btn-delete" type="button" style={ {paddingRight:10, paddingLeft:10} }>
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
                    <Components.ControlLabel label_class="pull-right">{this.props.label}<DelBtn/></Components.ControlLabel>
                </div>
                {this.props.children}
            </div>);
        }
    });

    var ConditionsCompare = React.createClass({
        render: function () {
            return (
                <select className="to-select2 form-control">
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

    // condition to apply to the selection of products
    var ConditionSkuCollection = React.createClass({
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label}>
                    <ConditionsType ref="skuCollectionType" id="skuCollectionType"> of </ConditionsType>
                    <div className="col-md-2"><input type="hidden" id="skuCollectionIds" ref="skuCollectionIds" className="form-control"/></div>
                    <div className="col-md-2"><ConditionsCompare ref="skuCollectionCond" id="skuCollectionCond" /></div>
                    <div className="col-md-1"><input className="form-control" ref="skuCollectionValue" id="skuCollectionValue" type="text"/></div>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                label: "Sku Collection",
                rowClass: "sku-collection",
                url: 'conditions/products'
            };
        },
        componentDidMount: function () {
            var skuCollectionIds = this.refs.skuCollectionIds;
            var url = this.props.options.base_url + this.props.url;
            $(skuCollectionIds.getDOMNode()).select2({
                placeholder: "Choose products",
                minimumInputLength: 3,
                multiple: true,
                closeOnSelect: false,
                ajax: {
                    url: url,
                    dataType: 'json',
                    quietMillis: 250,
                    data: function (term, page) {
                        return {
                            q: term,
                            page: page,
                            offset: 30
                        };
                    },
                    results: function (data, page) {
                        var more = (page * 30) < data.total_count;
                        return {results: data.items, more: more};
                    },
                    cache: true
                },
                initSelection: function (element, callback) {
                    var ids = this.state.productIds;
                    if (ids) {
                        $.ajax(url + "?ids=" + ids.join(','), {
                            dataType: "json"
                        }).done(function (data) {
                            callback(data);
                        });
                    }
                },
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: true,
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
                }
            });
        }
    });

    // condition to apply to products which match the attributes condition configured here
    var ConditionAttributeCombination = React.createClass({
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label}>
                    <div className="col-md-5"><input type="text" readOnly="readonly" ref="attributesResume" id="attributesResume" className="form-control"/></div>
                    <Components.Button type="button" className="btn-primary"
                       ref={this.props.configureId} onClick={this.handleConfigure}>Configure</Components.Button>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                rowClass: "attr-combination",
                label: "Combination",
                configureId: "attributeCombinationConfigure"
            };
        },
        modal: null,
        handleConfigure: function (e) {
            Promo.log("Clicked conditions");
            var modal = <Components.Modal onConfirm={this.handleConditionsConfirm}
                title="Product Combination Configuration" onLoad={this.registerModal} onUpdate={this.registerModal}>
                <ConditionsAttributesModalContent  baseUrl={this.props.options.base_url} idVar={this.props.options.id_var}
                    entityId={this.props.options.entity_id}/>
            </Components.Modal>;

            React.render(modal, this.props.modalContainer.get(0));
        },
        handleConditionsConfirm: function (modal) {
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

    // content of the modal used to configure attribute combination
    var ConditionsAttributesModalContent = React.createClass({
        render: function () {
            var baseUrl = this.props.baseUrl;
            var urlQuery = '/?' + this.props.idVar + this.props.entityId;
            return (
                <div className="attribute-combinations">
                    <div className="form-group">
                        <div className="col-md-5">
                            <select ref="combinationType" className="form-control to-select2">
                                <option value="0">All Conditions Have to Match</option>
                                <option value="1">Any Condition Has to Match</option>
                            </select>
                        </div>
                        <div className="col-md-5">
                            <input ref="combinationField" className="form-control"/>
                        </div>
                        <div className="col-md-2">
                            <button className="btn btn-small btn-default" type="button" ref="fieldAdd">{Locale._("Add Field")}</button>
                        </div>
                    </div>
                    <ConditionsAttributesModalField label={this.props.labelTest} url={baseUrl + this.props.urlTest + urlQuery } />
                </div>
            );
        },
        getDefaultProps: function () {
            return {
                labelTest: Locale._("Test"),
                labelCombinationField: Locale._("Add a Field to Condition..."),
                urlTest: "conditions/attributes_test", // this must be dynamically created when condition is added
                url: 'conditions/attributes_list'
            };
        },
        componentDidMount: function () {
            var fieldCombination = this.refs.combinationField;
            var self = this;
            var url = this.props.baseUrl + this.props.url;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.labelCombinationField,
                minimumInputLength: 2,
                multiple: false,
                closeOnSelect: false,
                ajax: {
                    url: url,
                    dataType: 'json',
                    quietMillis: 300,
                    data: function (term, page) {
                        return {
                            q: term,
                            page: page
                        };
                    },
                    results: function (data, page) {
                        var more = (page * 30) < data.total_count;
                        return {results: data.items, more: more};
                    },
                    cache: true
                },
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: true
            });
            $('.to-select2', this.getDOMNode()).select2({minimumResultsForSearch: 15});
        }
    });

    var ConditionsAttributesModalField = React.createClass({
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label}>
                    <div className="col-md-4">
                        <ConditionsCompare opts={this.props.opts} id="fieldCompare" ref="fieldCompare"/>
                    </div>
                    <div className="col-md-5"><input className="form-control" type="hidden" id="fieldCombination"
                        ref="fieldCombination"/></div>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                label: Locale._("Unknown"),
                url: "",
                fcLabel: "",
                opts: [
                    {id:"is", label: "is"},
                    {id:"is_not", label: "is not"},
                    {id:"contains", label: "contains"}
                ]
            };
        },
        componentDidMount: function () {
            var fieldCombination = this.refs.fieldCombination;
            var self = this;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.fcLabel,
                minimumInputLength: 3,
                maximumSelectionSize: 4,
                multiple: true,
                closeOnSelect: false,
                ajax: {
                    url: self.props.url,
                    dataType: 'json',
                    quietMillis: 300,
                    data: function (term, page) {
                        return {
                            q: term,
                            page: page,
                            offset: 30
                        };
                    },
                    results: function (data, page) {
                        var more = (page * 30) < data.total_count;
                        return {results: data.items, more: more};
                    },
                    cache: true
                },
                initSelection: function (element, callback) {
                    var ids = this.state.ids;
                    if (ids) {
                        $.ajax(self.props.url + "?ids=" + ids.join(','), {
                            dataType: "json"
                        }).done(function (data) {
                            callback(data);
                        });
                    }
                },
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: true,
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.text + '">' +
                        '<div class="span2">ID: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: <strong>' + item.text.substr(0, 20);
                    if (item.text.length > 20) {
                        markup += '...';
                    }
                    markup += '</strong></div></div>';

                    return markup;
                }
            });
        }
    });

    var ConditionCategories = React.createClass({
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label}>
                    <ConditionsType ref="catProductsType" id="catProductsType" > of products in </ConditionsType>
                    <div className="col-md-2"><input type="hidden" id="catProductsIds" ref="catProductsIds" className="form-control"/></div>
                    <div className="col-md-2"><ConditionsCompare ref="catProductsCond" id="catProductsCond" /></div>
                    <div className="col-md-1"><input ref="catProductsValue" id="catProductsValue" type="text" className="form-control"/></div>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                rowClass: "category-products",
                label: "Categories",
                url: 'conditions/categories'
            };
        },
        componentDidMount: function () {
            var catProductsIds = this.refs.catProductsIds;
            var url = this.props.options.base_url + this.props.url;
            $(catProductsIds.getDOMNode()).select2({
                placeholder: "Select categories",
                minimumInputLength: 3,
                maximumSelectionSize: 4,
                multiple: true,
                closeOnSelect: false,
                ajax: {
                    url: url,
                    dataType: 'json',
                    quietMillis: 250,
                    data: function (term, page) {
                        return {
                            q: term,
                            page: page,
                            offset: 30
                        };
                    },
                    results: function (data, page) {
                        var more = (page * 30) < data.total_count;
                        return {results: data.items, more: more};
                    },
                    cache: true
                },
                initSelection: function (element, callback) {
                    var ids = this.state.categoryIds;
                    if (ids) {
                        $.ajax(url + "?ids=" + ids.join(','), {
                            dataType: "json"
                        }).done(function (data) {
                            callback(data);
                        });
                    }
                },
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: true,
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
        }
    });

    var ConditionTotal = React.createClass({
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label}>
                    <ConditionsType ref="cartTotalType" id="cartTotalType" totalType={this.props.totalType}/>
                    <div className="col-md-2"><ConditionsCompare ref="cartTotalCond" id="cartTotalCond" /></div>
                    <div className="col-md-1"><input ref="cartTotalValue" id="cartTotalValue" type="text" className="form-control"/></div>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                rowClass: "cart-total",
                totalType: [{id:"qty", label:"QTY OF ITEMS"}, {id:"amt", label:"$Value/Amount OF ITEMS"}],
                label: "Cart Total"
            };
        }
    });

    var ConditionShipping = React.createClass({
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label}>
                    <div className="col-md-5"><textarea ref="shippingResume" id="shippingResume"
                            readOnly="readonly" value={this.state.value} className="form-control"/></div>
                    <Components.Button type="button" className="btn-primary" ref={this.props.configureId}
                        onClick={this.handleConfigure}>Configure</Components.Button>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                label: "Destination",
                modalTitle: "Shipping Reward Configuration",
                configureId: "shippingCombinationConfigure"
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
            var baseUrl = this.props.baseUrl;
            var urlQuery = '/?' + this.props.idVar + this.props.entityId;
            return (
                <div className="shipping-combinations">
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
                                <option value="shipping_method">{this.props.labelMethod}</option>
                                <option value="country">{this.props.labelCountry}</option>
                                <option value="state">{this.props.labelState}</option>
                                <option value="city">{this.props.labelCity}</option>
                            </select>
                        </div>
                        <div className="col-md-2">
                            <button className="btn btn-small btn-default" type="button" ref="fieldAdd">{Locale._("Add Field")}</button>
                        </div>
                    </div>
                    <ConditionsShippingModalField label={this.props.labelMethod} url={baseUrl + this.props.urlMethod + urlQuery } />
                    <ConditionsShippingModalField label={this.props.labelCountry} url={baseUrl + this.props.urlCountry + urlQuery} />
                    <ConditionsShippingModalField label={this.props.labelState} url={baseUrl + this.props.urlState + urlQuery} />
                    <ConditionsShippingModalField label={this.props.labelCity} url={baseUrl + this.props.urlCity + urlQuery} />
                </div>
            );
        },
        getDefaultProps: function () {
            return {
                labelMethod: Locale._("Method"),
                labelCountry: Locale._("Country"),
                labelState: Locale._("State/Province"),
                labelCity: Locale._("City"),
                labelCombinationField: Locale._("Add a Field to Condition..."),
                urlMethod: "conditions/shipping_methods",
                urlCountry: "conditions/shipping_country",
                urlState: "conditions/shipping_state",
                urlCity: "conditions/shipping_city"
            };
        },
        componentDidMount: function () {
            $('.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15});
        }
    });

    var ConditionsShippingModalField = React.createClass({
        render: function () {
            return (
                <ConditionsRow rowClass={this.props.rowClass} label={this.props.label}>
                    <div className="col-md-4">
                        <ConditionsCompare opts={this.props.opts} id="fieldCompare" ref="fieldCompare"/>
                    </div>
                    <div className="col-md-5"><input className="form-control" type="hidden" id="fieldCombination"
                         ref="fieldCombination"/></div>
                </ConditionsRow>
            );
        },
        getDefaultProps: function () {
            return {
                label: Locale._("Unknown"),
                url: "",
                fcLabel: "",
                opts: [
                    {id:"in", label: "is one of"},
                    {id:"not_in", label: "is not one of"},
                ]
            };
        },
        componentDidMount: function () {
            var fieldCombination = this.refs.fieldCombination;
            var self = this;
            $(fieldCombination.getDOMNode()).select2({
                placeholder: self.props.fcLabel,
                minimumInputLength: 3,
                maximumSelectionSize: 4,
                multiple: true,
                closeOnSelect: false,
                ajax: {
                    url: self.props.url,
                    dataType: 'json',
                    quietMillis: 300,
                    data: function (term, page) {
                        return {
                            q: term,
                            page: page,
                            offset: 30
                        };
                    },
                    results: function (data, page) {
                        var more = (page * 30) < data.total_count;
                        return {results: data.items, more: more};
                    },
                    cache: true
                },
                initSelection: function (element, callback) {
                    var ids = this.state.categoryIds;
                    if (ids) {
                        $.ajax(self.props.url + "?ids=" + ids.join(','), {
                            dataType: "json"
                        }).done(function (data) {
                            callback(data);
                        });
                    }
                },
                dropdownCssClass: "bigdrop",
                dropdownAutoWidth: true,
                selectOnBlur: true,
                formatResult: function (item) {
                    var markup = '<div class="row-fluid" title="' + item.text + '">' +
                        '<div class="span2">ID: <em>' + item.id + '</em></div>' +
                        '<div class="span2">Name: <strong>' + item.text.substr(0, 20);
                    if (item.text.length > 20) {
                        markup += '...';
                    }
                    markup += '</strong></div></div>';

                    return markup;
                }
            });
        }
    });

    var ConditionsApp = React.createClass({
        render: function () {
            return (<div className="conditions panel panel-primary">
                    <ConditionSkuCollection options={this.props.options}/>
                    <ConditionAttributeCombination options={this.props.options} modalContainer={this.props.modalContainer}/>
                    <ConditionCategories options={this.props.options}/>
                    <ConditionTotal options={this.props.options}/>
                    <ConditionShipping options={this.props.options} modalContainer={this.props.modalContainer}/>
                </div> );
        },
        componentDidMount: function () {
            var $conditionsSerialized = $('#'+this.props.options.conditions_serialized);
            var data;
            try {
                data = JSON.parse($conditionsSerialized.val());
                this.setProps({data: data});
            } catch (e) {
                this.setProps({data: {}});
            }

            $('.to-select2', this.getDOMNode()).select2({minimumResultsForSearch:15});
        },
        getInitialState: function () {
            return {
                data: this.props.data
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
        },
        initConditionsApp: function (selector, $modalContainer) {
            var $conditionSelector = $('#' + selector);
            if ($conditionSelector.length == 0) {
                this.log("Use coupon drop-down not found");
            } else {
                var $container = $("#" + this.options.condition_container_id);
                React.render(<ConditionsApp conditionType={$conditionSelector} newCondition={this.options.condition_add_id}
                    options={this.options} modalContainer={$modalContainer}/>,$container.get(0));
                /*
                todo: initiate interface, load data from JSON (either from validator or as json string loaded via ajax)
                interface consists of form groups
                    form group has:
                    remove btn - label - actual config part
                    app listens for conditions_add button click, takes condition_type value and creates appropriate iface
                 */
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
                    this.createCouponApp($container.get(0), $modalContainer.get(0), callBacks, selected, options);
                }

                $couponSelector.on('change', function () {
                    selected = $couponSelector.val();
                    self.createCouponApp($container.get(0), $modalContainer.get(0), callBacks, selected, options);
                });
            }
        },
        createCouponApp: function (appContainer, modalContainer, callBacks, mode, options) {
            React.render(<CouponApp {...callBacks} mode={mode} options={options}/>, appContainer);
            React.render(
                <div className="modals-container">
                    <Components.Modal onConfirm={this.handleShowConfirm} title="Coupon grid" onLoad={this.addShowCodes.bind(this)}/>
                    <Components.Modal onConfirm={this.handleGenerateConfirm} title="Generate coupons" onLoad={this.addGenerateCodes.bind(this)}>
                        <GenerateForm onSubmit={this.postGenerate.bind(this)}/>
                    </Components.Modal>
                    <Components.Modal onConfirm={this.handleImportConfirm} title="Import coupons" onLoad={this.addImportCodes.bind(this)}/>
                </div>, modalContainer);
        },
        options: {
            coupon_select_id: "model-use_coupon",
            coupon_container_id: "coupon-options",
            condition_select_id: 'model-conditions_type',
            condition_container_id: 'conditions-options',
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
                this.log(e);
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
            this.log(e, $formContainer);
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
                    this.log(r);
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
        }
    };
    return Promo;
});
