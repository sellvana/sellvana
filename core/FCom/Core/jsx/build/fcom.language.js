/**
 * @jsx React.DOM
 *
 * FCom Multi Languages Component
 */
define(['underscore', 'react', 'jquery', 'fcom.griddle', 'fcom.components', 'griddle.fcomSelect2', 'fcom.locale', 'store', 'ckeditor', 'jquery.validate'], function (_, React, $, FComGriddleComponent, Components, FComSelect2, Locale, Store) {

    var LangFields = React.createClass({displayName: "LangFields",
        componentWillUnmount: function () {
            _(this.props.langs).map(function (lang, key) {
                if (this.refs['lang_field_' + lang.lang_code])
                    React.unmountComponentAtNode(this.refs['lang_field_' + lang.lang_code].getDOMNode());
            }.bind(this));
        },
        componentDidMount: function () {
            this.createField();
        },
        componentDidUpdate: function () {
            this.createField();
        },
        _handleWysiwygChange: function (editor, data) {
            this.props.setLangVal(editor.element.$.dataset.code, data);
        },
        _clearCKEDITORIntance: function (code) {
            // Clear  ckeditor instance
            _(this.props.langs).map(function (lang, key) {
                if (lang.lang_code == code) {
                    adminForm.wysiwygDestroy(this.props.id + '_' + lang.lang_code);
                }
            }.bind(this));
        },
        _handleRemoveField: function (e) {
            var code = e.currentTarget.dataset.code;

            if (this.props.inputType === 'wysiwyg') {
                this._clearCKEDITORIntance(code);
            }

            this.props.removeField(code);
        },
        _handleChange: function (e) {
            var $input = $(e.currentTarget);
            this.props.setLangVal($input.data('code'), $input.val());
        },
        createField: function () {
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
                                                        id: this.props.id + '_' + lang.lang_code, 
                                                        name: this.props.id + '_' + lang.lang_code, 
                                                        value: lang.value, 
                                                        className: "ckeditor lang-field", 
                                                        onChange: this._handleWysiwygChange, 
                                                        attrs: dataAttrs});
                        break;
                    case 'textarea':
                        node = React.createElement(Components.ControlInput, {type: "textarea", 
                                                        name: this.props.id + '_' + lang.lang_code, 
                                                        value: lang.value, 
                                                        className: "form-control lang-field", 
                                                        callback: this._handleChange, 
                                                        validation: validation, 
                                                        attrs: dataAttrs});
                        break;
                    default:
                        node = React.createElement(Components.ControlInput, {type: "text", 
                                                        name: this.props.id + '_' + lang.lang_code, 
                                                        value: lang.value, 
                                                        className: "form-control lang-field", 
                                                        callback: this._handleChange, 
                                                        validation: validation, 
                                                        attrs: dataAttrs});
                        break;
                }

                React.render(node, this.refs['lang_field_' + key].getDOMNode());
            }.bind(this));
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
                                                       onClick: this._handleRemoveField}, 
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
        getDefaultProps: function () {
            return {
                data: [],
                locales: [],
                select2Config: {},
                modalConfig: {}
            };
        },
        getInitialState: function () {
            return {
                data: this.props.data,
                locales: this.props.locales,
                selection: null
            };
        },
        componentWillMount: function () {
            this.props.modalConfig = this.getModalConfig();

            this.setStoreData(this.getKey('data'), this.state.data);
            this.setStoreData(this.getKey('locales'), this.state.locales);

            this.setState({
                data: this.props.data,
                locales: this.props.locales
            });
        },
        shouldComponentUpdate: function (nextProps, nextState) {
            return nextState.data != this.state.data || nextState.locales != this.state.locales;
        },
        componentDidUpdate: function (prevProps, prevState) {
            // Reset selection
            this.state.selection = null;
        },
        getStoreData: function (key) {
            return Store.get(key);
        },
        setStoreData: function (key, value) {
            if (value === undefined) return false;
            Store.set(key, value);
        },
        getKey: function (key) {
            if (!_.isString(key))
                return this.props.id;
            return this.props.id + '.' + key;
        },
        getModalConfig: function () {
            return $.extend({}, {
                title: Locale._('Multi Languages'),
                confirm: Locale._('Save Changes'),
                cancel: Locale._('Cancel'),
                show: false,
                id: this.props.id + '-modal',
                onLoad: null,
                onConfirm: this._handleModalConfirm,
                onCancel: this._handleModalCancel,
                ref: this.props.id + '-modal'
            }, this.props.modalConfig);
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
        getLocales: function () {
            var that = this;
            var locales = [];

            var diff = _.difference(_.pluck(this.state.locales, 'id'), _.pluck(this.state.data, 'lang_code'));

            _(this.state.locales).each(function (lang) {
                if (_.contains(diff, lang.id)) locales.push(lang);
            });

            return _.sortBy(locales, 'id');
        },
        _handleSelect2Change: function (event, callback, selection) {
            if (typeof window[callback] === 'function') {
                window[callback](e, selection);
            }

            this.state.selection = selection.text;
        },
        _handleAddField: function (e) {
            if (null === this.state.selection) {
                $.bootstrapGrowl(Locale._("Please choose language."), {
                    type: 'warning',
                    align: 'center',
                    width: 'auto',
                    delay: 3000
                });
                return;
            }

            this.state.data.push({
                lang_code: this.state.selection,
                input_type: this.props.inputType || 'text',
                value: ''
            });

            this.state.locales = this.getLocales();

            this.forceUpdate();
        },
        _handleModalConfirm: function (modal) {
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
                window[modalConfig.onSaved](modal, this.state.data);

                // Update storage data
                this.setStoreData(this.getKey('data'), this.state.data);
                this.setStoreData(this.getKey('locales'), this.state.locales);

            } else if (valid) {
                modal.close();
            }
        },
        _handleModalCancel: function (modal) {
            setTimeout(function () {
                this.setState({
                    data: this.getStoreData(this.getKey('data')),
                    locales: this.getStoreData(this.getKey('locales'))
                });
            }.bind(this), 300);
            modal.close();
        },
        handleRemoveField: function (code) {
            var locales = this.getLocales();
            var defaultIds = _.pluck(locales, 'id');
            if (!_.contains(defaultIds, code)) {
                locales.push({
                    id: code, text: code
                });
            }

            var data = this.state.data;
            _(data).each(function (lang, i) {
                if (lang && lang.lang_code == code) data.splice(i, 1);
            });

            this.setState({
                data: data,
                locales: locales
            });
        },
        setLangVal: function (code, value) {
            var that = this;
            var langs = this.state.data;
            _(langs).each(function (lang, i) {
                if (lang && lang.lang_code == code)
                    that.state.data[i].value = value;
            });
        },
        showModal: function () {
            this.getModalNode().modal('show');
        },
        log: function (value) {
            console.log(value);
        },
        warn: function (value) {
            console.warn(value);
        },
        render: function () {
            var inlineProps = this.getSelect2Config(),
                locales = this.getLocales(),
                langIds = _.pluck(this.state.data, 'lang_code'),
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
                    React.createElement(Components.Modal, React.__spread({},  this.getModalConfig()), 
                        React.createElement("div", {className: "well"}, 
                            React.createElement("table", null, 
                                React.createElement("tbody", null, 
                                React.createElement("tr", null, 
                                    React.createElement("td", null, 
                                        React.createElement(FComSelect2, React.__spread({options: locales, 
                                                     onChange: this._handleSelect2Change, 
                                                     defaultValue: []},  inlineProps))
                                    ), 
                                    React.createElement("td", null, 
                                        React.createElement(Components.Button, {type: "button", className: "btn-sm btn-primary", 
                                                           onClick: this._handleAddField}, 
                                            Locale._('Add Locale')
                                        )
                                    )
                                )
                                )
                            )
                        ), 
                        React.createElement("div", {id: this.props.id + '-container', ref: "container"}, 
                            React.createElement(LangFields, {id: this.props.id, related: true, 
                                        inputType: this.props.inputType, 
                                        langs: this.state.data || [], 
                                        removeField: this.handleRemoveField, 
                                        setLangVal: this.setLangVal})
                        )
                    )
                )
            );
        }
    });

    return FComMultiLanguage;
});