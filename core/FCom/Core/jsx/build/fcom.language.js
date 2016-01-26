/**
 * @jsx React.DOM
 *
 * FCom Multi Languages Component
 */
define(['underscore', 'react', 'jquery', 'fcom.griddle', 'fcom.components', 'griddle.fcomSelect2', 'fcom.locale', 'ckeditor', 'jquery.validate'], function (_, React, $, FComGriddleComponent, Components, FComSelect2, Locale) {

    var LangFields = React.createClass({displayName: "LangFields",
        componentWillUnmount: function () {
            _(this.props.langs).map(function (lang, key) {
                if (this.refs['lang_field_' + lang.lang_code])
                    React.unmountComponentAtNode(this.refs['lang_field_' + lang.lang_code].getDOMNode());
            }.bind(this));
        },
        componentDidMount: function () {
            this.renderLangFields();
        },
        componentDidUpdate: function () {
            this.renderLangFields();
        },
        handleWysiwygChange: function (editor, data) {
            this.props.setLangVal(editor.element.$.dataset.code, data);
        },
        renderLangFields: function () {
            _(this.props.langs).map(function (lang, key) {
                var node = null,
                    dataAttrs = {
                        'data-code': lang.lang_code,
                        'data-type': this.props.id
                    },
                    validation = {
                        required: true
                    };

                switch (lang.input_type) {
                    case 'wysiwyg':
                        dataAttrs['rows'] = 5;
                        node = React.createElement(Components.SpecialInput, {type: "wysiwyg", 
                                                        id: this.props.id + '_' + lang.lang_code + '_' + key, 
                                                        name: this.props.id + '_' + lang.lang_code, 
                                                        value: lang.value, 
                                                        className: "ckeditor lang-field", 
                                                        onChange: this.handleWysiwygChange, 
                                                        attrs: dataAttrs});
                        break;
                    case 'textarea':
                        node = React.createElement(Components.ControlInput, {type: "textarea", 
                                                        name: this.props.id + '_' + lang.lang_code, 
                                                        value: lang.value, 
                                                        className: "form-control lang-field", 
                                                        callback: this.handleChange, 
                                                        validation: validation, 
                                                        attrs: dataAttrs});
                        break;
                    default:
                        node = React.createElement(Components.ControlInput, {type: "text", 
                                                        name: this.props.id + '_' + lang.lang_code, 
                                                        value: lang.value, 
                                                        className: "form-control lang-field", 
                                                        callback: this.handleChange, 
                                                        validation: validation, 
                                                        attrs: dataAttrs});
                        break;
                }

                React.render(node, this.refs['lang_field_' + key].getDOMNode());
            }.bind(this));
        },
        removeLangField: function (e) {
            this.props.removeField($(e.currentTarget).data('code'));
        },
        handleChange: function (e) {
            var $input = $(e.currentTarget);
            this.props.setLangVal($input.data('code'), $input.val());
        },
        render: function () {
            var that = this;
            return (
                React.createElement("div", null, 
                    _(this.props.langs).map(function (lang, key) {
                        return (
                            React.createElement("div", {key: that.props.id + lang.lang_code, className: "form-group"}, 
                                React.createElement("div", {className: "col-md-3 control-label"}, 
                                    React.createElement("span", {className: "badge badge-default"}, lang.lang_code)
                                ), 
                                React.createElement("div", {className: "col-md-6", ref: 'lang_field_' + key}), 
                                React.createElement("div", {className: "col-md-3"}, 
                                    React.createElement(Components.Button, {type: "button", className: "btn-sm btn-danger field-remove", 
                                                       "data-code": lang.lang_code, 
                                                       onClick: this.removeLangField}, 
                                        React.createElement("i", {className: "icon-remove"})
                                    )
                                )
                            )
                        );
                    }.bind(this))
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
                defaultLangs: []
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
                confirm: Locale._('Save Changes'),
                cancel: Locale._('Cancel'),
                show: false,
                id: this.props.id + '-modal',
                onLoad: null,
                onConfirm: this.confirmEditLangs,
                ref: this.props.id + '-modal'
            }, this.props.modalConfig);
        },
        confirmEditLangs: function (modal) {
            var modalConfig = this.props.modalConfig;
            var $container = $(this.refs.container.getDOMNode());
            var valid = true;

            if (this.props.inputType == 'wysiwyg') {
                $container.find('textarea.ckeditor').each(function (index, ele) {
                    var content = $('#cke_' + ele.id + ' iframe').contents().find("body").text();
                    if (!content.length) {
                        valid = false;
                        $(this).next().next('label.error')
                            .html(Locale._('This field is required.'))
                            .show();
                    } else {
                        $(this).next().next('label.error').empty().hide();
                    }
                });
            } else {
                if (!$container.parent('form').length) {
                    $container.wrap('<form />');
                }

                if (!$container.parent('form').valid())
                    valid = false;
                $container.unwrap();
            }

            if (valid && modalConfig.onSaved && typeof modalConfig.onSaved === 'string') {
                window[modalConfig.onSaved](modal, this.state.availLangs);
            } else if (valid) {
                modal.close();
            }
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
                if (lang && lang.lang_code == code) availLangs.splice(i, 1);
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

            this.state.availLangs.push({
                lang_code: this.state.selection,
                input_type: this.props.inputType || 'text',
                value: ''
            });

            this.state.defaultLangs = this.getDefaultLangs();

            this.forceUpdate();
        },
        setLangVal: function (code, value) {
            var that = this;
            var langs = this.state.availLangs;
            _(langs).each(function (lang, i) {
                if (lang && lang.lang_code == code)
                    that.state.availLangs[i].value = value;
            });
        },
        showModal: function () {
            this.getModalNode().modal('show');
        },
        render: function () {
            var inlineProps = this.getSelect2Config(),
                defaultLangs = this.getDefaultLangs(),
                langIds = _.pluck(this.state.availLangs, 'lang_code'),
                langLabel = langIds ? langIds.length < 6 ? langIds.filter(function (item) {
                    return item != undefined
                }).join(',') : Locale._('Multi Languages ...') : null;

            return (
                React.createElement("div", {className: this.props.cClass || ''}, 
                    React.createElement(Components.Button, {type: "button", style: {marginBottom: '10px'}, 
                                       className: 'btn btn-xs multilang ' + (langLabel ? 'btn-info' : ''), 
                                       onClick: this.showModal}, 
                        !langLabel ? React.createElement("i", {className: "icon icon-globe"}) : '', " ", langLabel || Locale._('Translate')
                    ), 
                    React.createElement(Components.Modal, React.__spread({},  this.getModalConfig(), {cancel: null}), 
                        React.createElement("div", {className: "well"}, 
                            React.createElement("table", null, 
                                React.createElement("tbody", null, 
                                React.createElement("tr", null, 
                                    React.createElement("td", null, 
                                        React.createElement(FComSelect2, React.__spread({},  inlineProps, {options: defaultLangs, 
                                                                      onChange: this.handleSelect2Change, 
                                                                      defaultValue: []}))
                                    ), 
                                    React.createElement("td", null, 
                                        React.createElement(Components.Button, {type: "button", className: "btn-sm btn-primary", 
                                                           onClick: this.addLocaleField}, 
                                            Locale._('Add Locale')
                                        )
                                    )
                                )
                                )
                            )
                        ), 
                        React.createElement("div", {id: this.props.id + '-container', ref: "container"}, 
                            React.createElement(LangFields, {id: this.props.id, langs: this.state.availLangs || [], 
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