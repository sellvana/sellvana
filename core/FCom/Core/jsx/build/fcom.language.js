/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'fcom.griddle', 'fcom.components', 'griddle.fcomSelect2', 'fcom.locale'], function (_, React, $, FComGriddleComponent, Components, FComSelect2, Locale) {

    var LANGAPP = {};

    var LangFields = React.createClass({displayName: "LangFields",
        mixins: [FCom.Mixin],
        componentDidMount: function () {

        },
        removeLangField: function (e) {
            var code = $(e.currentTarget).data('code');
            $(LANGAPP).trigger('removeLangField', [code]);
        },
        render: function () {
            var that = this;
            return (
                React.createElement("div", null, 
                    _.map(this.props.langs, function (lang, key) {
                        return (
                            React.createElement("div", {key: key, className: "form-group"}, 
                                React.createElement("div", {className: "col-md-3 control-label"}, 
                                    React.createElement("span", {className: "badge badge-default"}, lang.lang_code)
                                ), 
                                React.createElement("div", {className: "col-md-6"}, 
                                    React.createElement("input", {type: "text", className: "form-control", "data-type": "lang", "data-code": lang.lang_code, "data-rule-required": "true", name: guid(), defaultValue: lang.value})
                                ), 
                                React.createElement("div", {className: "col-md-3"}, 
                                    React.createElement("button", {type: "button", onClick: that.removeLangField, "data-code": lang.lang_code, className: "btn btn-default btn-sm field-remove"}, 
                                        React.createElement("i", {className: "icon-remove"})
                                    )
                                )
                            )
                        );
                    })
                )
            );
        }
    });

    var FComMultiLanguage = React.createClass({
        displayName: "FComMultiLanguage",
        mixins: [FCom.Mixin],
        getInitialState: function () {
            return {
                availLangs: [],
                defaultLangs: [],
                selection: null
            };
        },
        getDefaultProps: function () {
            return {
                availLangs: [],
                select2Config: {},
                modalConfig: {},
                btnLangLabel: '',
                defaultLangs: [
                    {id: 'en_US', text: 'en_US'},
                    {id: 'de_DE', text: 'de_DE'},
                    {id: 'zh-CN', text: 'zh-CN'},
                    {id: 'fr-FR', text: 'fr-FR'},
                    {id: 'nl_NL', text: 'nl_NL'}
                ]
            };
        },
        componentWillMount: function () {
            this.props.modalConfig = this.getModalConfig();

            this.setState({
                availLangs: this.props.availLangs,
                defaultLangs: this.props.defaultLangs
            });
        },
        componentDidMount: function () {
            var that = this;
            var modalConfig = this.props.modalConfig;

            $(LANGAPP).on('setSelection', function (e, selection) {
                that.setSelection(selection);
            });

            $(LANGAPP).on('removeLangField', function (e, code) {
                that.removeLangField(code);
            });

            if (this.props.select2Callback) {
                window[this.props.select2Callback] = function (e, selection) {
                    $(LANGAPP).trigger('setSelection', [selection]);
                };
            }
        },
        componentWillUnmount: function () {
            $(LANGAPP).off('setSelection');
            $(LANGAPP).off('removeLangField');
        },
        componentDidUpdate: function (prevProps, prevState) {
            // Reset selection
            this.state.selection = null;
        },
        getModalConfig: function () {
            var config = $.extend({}, {
                title: Locale._('Multi Languages'),
                confirm: Locale._('Save Change'),
                cancel: Locale._('Close'),
                show: false,
                id: this.props.id + '-modal',
                onLoad: null,
                onConfirm: null,
                onCancel: null,
                ref: this.props.id + '-modal'
            }, this.props.modalConfig);

            if (config.onConfirm && typeof config.onConfirm === 'string') {
                config.onConfirm = window[config.onConfirm];
            }

            return config;
        },
        getModalNode: function () {
            return $('#'+ this.props.id + '-modal');
        },
        getSelect2Config: function () {
            return $.extend({}, {
                id: 'multilang-' + this.props.id,
                name: 'multilang-' + this.props.id,
                className: ''
            }, this.props.select2Config);
        },
        handleSelect2Change: function (event, callback, selections) {
            if (typeof window[callback] === 'function') {
                return window[callback](event, selections);
            }
        },
        setSelection: function(selection) {
            this.state.selection = selection.text;
        },
        getDefaultLangs: function () {
            var that = this;
            var defaultLangs = [];

            var diff = _.difference(_.pluck(this.state.defaultLangs, 'id'), _.pluck(this.state.availLangs, 'lang_code'));

            _(this.state.defaultLangs).each(function (lang) {
                if (_.contains(diff, lang.id)) defaultLangs.push(lang);
            });

            return _.sortBy(defaultLangs, 'id');
        },
        removeLangField: function (code) {
            var defaultLangs = this.getDefaultLangs();
            var defaultIds = _.pluck(defaultLangs, 'id');
            if (!_.contains(defaultIds, code)) {
                defaultLangs.push({
                    id: code, text: code
                });
            }

            var availLangs = this.state.availLangs;
            _(availLangs).each(function(lang, i) {
                if (lang.lang_code == code) delete availLangs[i];
            });

            this.setState({
                defaultLangs: _.sortBy(defaultLangs, 'id'),
                availLangs: _.sortBy(availLangs, 'lang_code')
            });
        },
        AddLocaleField: function(e) {
            if (null === this.state.selection) {
                $.bootstrapGrowl(Locale._("Please choose language."), { type: 'warning', align: 'center', width: 'auto', delay: 3000 });
                return;
            }

            this.state.defaultLangs = this.getDefaultLangs();

            this.state.availLangs.push({
                lang_code: this.state.selection,
                value: ''
            });

            this.forceUpdate();
        },
        showModal: function () {
            this.getModalNode().modal('show');
        },
        render: function () {
            var inlineProps = this.getSelect2Config();
            var defaultLangs = this.getDefaultLangs();

            if (this.state.availLangs.length) {
                var langIds = _.pluck(this.state.availLangs, 'lang_code');
                this.props.btnLangLabel = langIds.filter(function (item) { return item != undefined }).join(',');
            }

            return (
                React.createElement("div", {key: this.props.id, className: "row multilang-field"}, 
                    React.createElement("div", {className: "col-md-2"}), 
                    React.createElement("div", {className: "col-md-5"}, 
                        React.createElement("button", {type: "button", style: {marginTop: '5px', marginBottom: '10px'}, onClick: this.showModal, 
                                className: "btn btn-xs multilang " + (this.props.btnLangLabel ? 'btn-info' : '')}, !this.props.btnLangLabel ? React.createElement("i", {className: "icon icon-globe"}) : '', " ", this.props.btnLangLabel || Locale._('Translate')
                        )
                    ), 
                    React.createElement(Components.Modal, React.__spread({},  this.props.modalConfig), 
                        React.createElement("div", {className: "well"}, 
                            React.createElement("table", null, 
                                React.createElement("tbody", null, 
                                React.createElement("tr", null, 
                                    React.createElement("td", null, React.createElement(FComSelect2, React.__spread({},  inlineProps, {options: defaultLangs, 
                                                                      multiple: this.props.multiple || false, 
                                                                      placeholder: this.props.placeholder || Locale._("Select languages"), 
                                                                      onChange: this.handleSelect2Change, 
                                                                      defaultValue: [], 
                                                                      callback: this.props.select2Callback}))), 
                                    React.createElement("td", null, 
                                        React.createElement("button", {className: "btn btn-primary", onClick: this.AddLocaleField, type: "button"}, Locale._('Add Locale'))
                                    )
                                )
                                )
                            )
                        ), 
                        React.createElement("div", {className: this.props.id + '-container'}, 
                            React.createElement(LangFields, {langs: this.state.availLangs})
                        )
                    )
                )
            );
        }
    });

    return FComMultiLanguage;
});