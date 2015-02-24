/** @jsx React.DOM */

define(['react', 'jquery', 'jsx!griddle', 'jsx!fcom.components', 'jsx!fcom.promo.actions', 'jsx!fcom.promo.coupon', 'jsx!fcom.promo.conditions', 'store', 'select2', 'jquery.bootstrap-growl'],
    function (React, $, Griddle, Components, Actions, CouponApp, ConditionsApp, store) {
    $.fn.select2.defaults = $.extend($.fn.select2.defaults, {minimumResultsForSearch: 15, dropdownAutoWidth: true});
    var Promo = {
        $modalContainerCoupons: null,
        $modalContainerConditions: null,
        $modalContainerActions: null,
        init: function (options) {
            $.extend(this.options, options);

            var $promoOptions = $('#' + this.options.promo_serialized);
            if($promoOptions.length) {
                var val = $promoOptions.val();
                if (val) {
                    try {
                        this.options.promoOptions = JSON.parse(val);
                    } catch (e) {
                        console.log(e);
                    }
                }
                this.options.promoOptionsEl = $promoOptions;
            }

            if(this.options['promo_type_id']) {
                var $promoType = $("#" + this.options['promo_type_id']);
                if($promoType.length) {
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
                Promo.log("Actions drop-down not found");
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
                    options: this.options, modalContainer: $modalContainer, hidden: hidden}),$container.get(0));
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
                    React.createElement(Components.Modal, {title: "Coupon grid", onLoad: this.addShowCodes.bind(this)}), 
                    React.createElement(Components.Modal, {title: "Generate coupons", onLoad: this.addGenerateCodes.bind(this), onConfirm: this.postGenerate.bind(this)}, 
                        React.createElement(CouponApp.GenerateForm, {onSubmit: this.postGenerate.bind(this)})
                    ), 
                    React.createElement(Components.Modal, {title: "Import coupons", onLoad: this.addImportCodes.bind(this)})
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
            //Promo.log(e, $formContainer);
            var url = this.options['generateCouponsUrl'];
            var $progress = $formContainer.find('.loading');
            var $result = $formContainer.find('.result').hide();
            $progress.show();
            //$button.click(function (e) {

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
                    $.bootstrapGrowl(message, {type: 'success', align: 'center', width: 'auto'});
                    $result.text(message);
                    if (status != 'error') {
                        var newRows = result['codes'].map(function (e, i) {
                            console.log(e, i);
                            return {
                                code: e,
                                total_used: 0
                            }
                        });
                        console.log(newRows);
                        var grid_id = result['grid_id'];
                        Promo.updateGrid(grid_id, newRows);
                    }
                })
                .always(function (r) {
                    $progress.hide();
                    $result.show();
                    if ($.isFunction(e.close)) {
                        // e is the modal object
                        setTimeout(e.close, 2000);
                        //e.close();//close it
                    }
                    // hide notification
                    Promo.log(r);
                });
            //});
            if ($.isFunction(e.preventDefault)) {
                e.preventDefault();
            }
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
            this.loadModalContent($modalBody, this.options['importCouponsUrl']);
            $(document).on("coupon_import", function (event) {
                console.log(event.codes);
                Promo.updateGrid(event.grid_id, event.codes);
            });
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
        },
        updateGrid: function (grid_id, newRows) {
            var grid = window[grid_id];
            this.onCouponsUpdate(newRows);
            if (grid) {
                console.log("grid found, adding to grid");
                Promo.addGridRows(grid, newRows)
            } else {
                console.log("grid not loaded yet, adding to store");
                var codes = store.get('promo.coupons'); // check of there are other codes stored and if yes, merge them
                if(codes) {
                    codes = JSON.parse(codes);
                    newRows = codes.concat(newRows);
                }
                store.set('promo.coupons', JSON.stringify(newRows));
            }
        },
        addGridRows: function (grid, rows) {
            /** @type Backbone.Collection */
            var gridRows = grid.getRows();
            //var lastId = 0;
            //if(gridRows.size()) {
            //    lastId = gridRows.at(gridRows.size() - 1).get('id') - 0;
            //}
            //lastId++;
            var newRows = rows.map(function (row, idx) {
                //row._new = true;
                //row.id = lastId + idx;
                row.id = row.code; // instead of worrying for duplicate codes, make the code the id and effectively update the duplicates instead of detecting them
                return row;
            });
            gridRows.add(newRows, {merge: true}).trigger('build');
            $(document).trigger({ // trigger event which will upgrade the grid
                type: "grid_count_update",
                numCodes: gridRows.size()
            });
        },
        onActionsUpdate: function (e) {
            console.log(e);
            if (this.options.promoOptions['actions'] == undefined) {
                this.options.promoOptions['actions'] = {};
            }
            this.options.promoOptions['actions']['rules'] = e['rules'];
            this.updatePromoOptions();
        },
        onConditionsUpdate: function (e) {
            console.log(e);
            if (this.options.promoOptions['conditions'] == undefined) {
                this.options.promoOptions['conditions'] = {};
            }
            this.options.promoOptions['conditions']['rules'] = e['rules'];
            this.updatePromoOptions();
        },
        onCouponsUpdate: function (newRows) {
            console.log(newRows);
            if (this.options.promoOptions['coupons'] == undefined) {
                this.options.promoOptions['coupons'] = [];
            }
            this.options.promoOptions['coupons'] = this.options.promoOptions['coupons'].concat(newRows);
            this.updatePromoOptions();
        },
        updatePromoOptions: function () {
            if(this.options.promoOptionsEl) {
                var values = this.options.promoOptions;
                this.options.promoOptionsEl.val(JSON.stringify(values));
            } else {
                console.error("Cannot find serialized options element");
            }
        }
    };
    window.couponsGridRegister = function (grid) {
        //console.log(grid);
        window[grid.id] = grid;
        var newRows = store.get('promo.coupons');
        store.remove('promo.coupons');
        //console.log(newRows);
        if(newRows) {
            newRows = JSON.parse(newRows);
            Promo.addGridRows(grid, newRows);
        }
    };
    return Promo;
});
