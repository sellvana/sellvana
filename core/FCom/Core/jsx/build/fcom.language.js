/**
 * @jsx React.DOM
 *
 * FCom Multi Languages Component
 */
define(['underscore', 'react', 'jquery', 'fcom.griddle', 'fcom.components', 'griddle.fcomSelect2', 'fcom.locale'], function (_, React, $, FComGriddleComponent, Components, FComSelect2, Locale) {

    var LangFields = React.createClass({displayName: "LangFields",
        mixins: [FCom.Mixin],
        componentDidMount: function () {

        },
        removeLangField: function (e) {
            this.props.removeField($(e.currentTarget).data('code'));
        },
        handleChange: function (e) {
            $input = $(e.currentTarget);
            this.props.setLangVal($input.data('code'), $input.val());
        },
        render: function () {
            var that = this, node;
            return (
                React.createElement("div", null, 
                    _.map(this.props.langs, function (lang, key) {
                        switch (lang.input_type) {
                            case 'textarea':
                                node = React.createElement("textarea", {name: that.props.id, "data-type": "lang", "data-code": lang.lang_code, className: "form-control", 
                                                 "data-rule-required": "true"}, lang.value);
                                break;
                            case 'wysiwyg':
                                node = React.createElement("textarea", {name: that.props.id, "data-type": "lang", className: "form-control ckeditor", 
                                                 rows: "20"}, lang.value);
                                break;
                            default:
                                node = React.createElement("input", {type: "text", className: "form-control", "data-type": "lang", 
                                              onBlur: that.handleChange, 
                                              "data-code": lang.lang_code, "data-rule-required": "true", name: that.props.id, 
                                              defaultValue: lang.value});
                                break;
                        }
                        return (
                            React.createElement("div", {key: key, className: "form-group"}, 
                                React.createElement("div", {className: "col-md-3 control-label"}, 
                                    React.createElement("span", {className: "badge badge-default"}, lang.lang_code)
                                ), 
                                React.createElement("div", {className: "col-md-6"}, node), 
                                React.createElement("div", {className: "col-md-3"}, 
                                    React.createElement("button", {type: "button", onClick: that.removeLangField, "data-code": lang.lang_code, 
                                            className: "btn btn-danger btn-sm field-remove"}, 
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
            this.props.tmpAvailLangs = _.clone(this.state.availLangs);
            this.props.tmpDefaultLangs = _.clone(this.state.defaultLangs);
        },
        componentWillUnmount: function () {
            //
        },
        componentDidUpdate: function (prevProps, prevState) {
            // Reset selection
            this.state.selection = null;
        },
        getModalConfig: function () {
            return $.extend({}, {
                title: Locale._('Multi Languages'),
                confirm: Locale._('Save Change'),
                cancel: Locale._('Close'),
                show: false,
                id: this.props.id + '-modal',
                onLoad: null,
                onConfirm: this.confirmEditLangs,
                onCancel: this.cancelEditLangs,
                ref: this.props.id + '-modal'
            }, this.props.modalConfig);
        },
        confirmEditLangs: function (modal) {
            var modalConfig = this.props.modalConfig;

            this.props.tmpAvailLangs = _.clone(this.state.availLangs);
            this.props.tmpDefaultLangs = _.clone(this.state.defaultLangs);

            if (modalConfig.onSaved && typeof modalConfig.onSaved === 'string') {
                window[modalConfig.onSaved](modal);
            }
        },
        cancelEditLangs: function (modal) {
            this.setState({
                availLangs: this.props.tmpAvailLangs,
                defaultLangs: this.props.tmpDefaultLangs
            });

            var modalConfig = this.props.modalConfig;
            if (modalConfig.onCanceled && typeof modalConfig.onCanceled === 'string') {
                window[modalConfig.onCanceled](modal);
            }

            modal.close();
        },
        getModalNode: function () {
            return $('#' + this.props.id + '-modal');
        },
        getSelect2Config: function () {
            return $.extend({}, {
                id: 'multilang-' + this.props.id,
                name: 'multilang-' + this.props.id,
                className: '',
                placeholder: Locale._("Select languages"),
                multiple: false
            }, this.props.select2Config);
        },
        handleSelect2Change: function (event, callback, selection) {
            if (typeof window[callback] === 'function') {
                window[callback](e, selection);
            }

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
            _(availLangs).each(function (lang, i) {
                if (lang.lang_code == code) delete availLangs[i];
            });

            this.setState({
                defaultLangs: defaultLangs,
                availLangs: availLangs
            });
        },
        addLocaleField: function (e) {
            if (null === this.state.selection) {
                $.bootstrapGrowl(Locale._("Please choose language."), {
                    type: 'warning',
                    align: 'center',
                    width: 'auto',
                    delay: 3000
                });
                return;
            }

            this.state.defaultLangs = this.getDefaultLangs();

            this.state.availLangs.push({
                lang_code: this.state.selection,
                value: ''
            });

            this.forceUpdate();
        },
        setLangVal: function (code, value) {
            var langs = this.state.availLangs;
            _(langs).each(function (lang, i) {
                if (lang.lang_code == code) lang.value = value;
            });
        },
        showModal: function () {
            this.getModalNode().modal('show');
        },
        render: function () {
            var inlineProps = this.getSelect2Config();
            var defaultLangs = this.getDefaultLangs();

            if (this.state.availLangs.length) {
                var langIds = _.pluck(this.state.availLangs, 'lang_code');
                this.props.btnLangLabel = langIds.filter(function (item) {
                    return item != undefined
                }).join(',');
            }

            return (
                React.createElement("div", null, 
                    React.createElement("button", {type: "button", style: {marginBottom: '10px'}, onClick: this.showModal, 
                            className: "btn btn-xs multilang " + (this.props.btnLangLabel ? 'btn-info' : '')}, !this.props.btnLangLabel ?
                        React.createElement("i", {className: "icon icon-globe"}) : '', " ", this.props.btnLangLabel || Locale._('Translate')
                    ), 
                    React.createElement(Components.Modal, React.__spread({},  this.props.modalConfig), 
                        React.createElement("div", {className: "well"}, 
                            React.createElement("table", null, 
                                React.createElement("tbody", null, 
                                React.createElement("tr", null, 
                                    React.createElement("td", null, React.createElement(FComSelect2, React.__spread({},  inlineProps, {options: defaultLangs, 
                                                                      onChange: this.handleSelect2Change, 
                                                                      defaultValue: []}))
                                    ), 
                                    React.createElement("td", null, 
                                        React.createElement("button", {className: "btn btn-primary", onClick: this.addLocaleField, 
                                                type: "button"}, Locale._('Add Locale'))
                                    )
                                )
                                )
                            )
                        ), 
                        React.createElement("div", {className: this.props.id + '-container'}, 
                            React.createElement(LangFields, {id: this.props.id, langs: this.state.availLangs, 
                                        removeField: this.removeLangField, 
                                        setLangVal: this.setLangVal})
                        )
                    )
                )
            );
        }
    });

    return FComMultiLanguage;
});