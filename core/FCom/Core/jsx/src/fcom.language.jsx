/**
 * @jsx React.DOM
 *
 * FCom Multi Languages Component
 */
define(['underscore', 'react', 'jquery', 'fcom.griddle', 'fcom.components', 'griddle.fcomSelect2', 'fcom.locale', 'ckeditor'], function (_, React, $, FComGriddleComponent, Components, FComSelect2, Locale) {

    var LangFields = React.createClass({
        mixins: [FCom.Mixin],
        getDefaultProps: function () {
            return {
                editors: {}
            };
        },
        getInitialState: function () {
            return {
                inputTypes: {}
            };
        },
        componentWillUnmount: function () {
            _(this.props.langs).map(function (lang, key) {
                React.unmountComponentAtNode(this.refs['lang_field_' + ++key].getDOMNode());
            }.bind(this));
        },
        componentDidMount: function () {
            this.renderLangFields();
            this.initSpecialInput(this.state.inputTypes);
        },
        componentWillUpdate: function () {
            this.clearCKEDITORIntances();
        },
        clearCKEDITORIntances: function () {
            var that = this;
            var editors = this.props.editors;
            if (editors) {
                _(editors).each(function (editor, id) {
                    if (CKEDITOR.instances[id]) {
                        CKEDITOR.instances[id].destroy(true);
                        delete that.props.editors[id];
                    }
                });
            }
        },
        componentDidUpdate: function () {
            this.renderLangFields();
            this.initSpecialInput(this.state.inputTypes);
        },
        initSpecialInput: function (types) {
            var that = this;
            _(types).each(function (type, code) {
                switch (type) {
                    case 'wysiwyg':
                        var id = $('textarea.lang-ckeditor[data-code="' + code + '"]').prop('id');
                        if (id && CKEDITOR !== undefined && !CKEDITOR.instances[id]) {
                            that.props.editors[id] = true;

                            CKEDITOR.replace(id, {
                                startupMode: 'wysiwyg'
                            });

                            CKEDITOR.instances[id].on('blur', function (e) {
                                e.editor.updateElement();
                                var data = e.editor.getData();
                                that.props.setLangVal(code, data);
                            });
                        }
                        break;
                    default:
                        break;
                }
            });
        },
        renderLangFields: function () {
            _(this.props.langs).map(function (lang, key) {
                var extNode =null, node = null;
                switch (lang.input_type) {
                    case 'textarea':
                        node = <textarea id={guid()} name={this.props.id + '_' + lang.lang_code}
                                         data-type={this.props.id}
                                         data-code={lang.lang_code} className="form-control lang-field"
                                         data-rule-required="true" defaultValue={lang.value}
                                         onBlur={this.handleChange}/>;
                        break;
                    case 'wysiwyg':
                        node = <textarea id={guid()} name={this.props.id + '_' + lang.lang_code}
                                         data-type={this.props.id}
                                         data-code={lang.lang_code}
                                         className="form-control lang-ckeditor lang-field"
                                         rows="5" defaultValue={lang.value}/>;
                        this.state.inputTypes[lang.lang_code] = lang.input_type;
                        break;
                    default:
                        node = <input type="text" id={guid()} className="form-control lang-field"
                                      data-type={this.props.id}
                                      onBlur={this.handleChange}
                                      data-code={lang.lang_code} data-rule-required="true"
                                      name={this.props.id + '_' + lang.lang_code}
                                      defaultValue={lang.value}/>;
                        break;
                }
                React.render(node, this.refs['lang_field_' + ++key].getDOMNode());
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
                <div>
                    {_(this.props.langs).map(function (lang, key) {
                        return (
                            <div key={that.props.id + lang.lang_code} className="form-group">
                                <div className="col-md-3 control-label">
                                    <span className="badge badge-default">{lang.lang_code}</span>
                                </div>
                                <div className="col-md-6" ref={'lang_field_' + ++key}></div>
                                <div className="col-md-3">
                                    <button type="button" onClick={that.removeLangField} data-code={lang.lang_code}
                                            className="btn btn-danger btn-sm field-remove">
                                        <i className="icon-remove"/>
                                    </button>
                                </div>
                            </div>
                        );
                    })}
                </div>
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
                confirm: Locale._('Save Change'),
                cancel: Locale._('Close'),
                show: false,
                id: this.props.id + '-modal',
                onLoad: null,
                onConfirm: this.confirmEditLangs,
                ref: this.props.id + '-modal'
            }, this.props.modalConfig);
        },
        confirmEditLangs: function (modal) {
            var modalConfig = this.props.modalConfig;

            if (modalConfig.onSaved && typeof modalConfig.onSaved === 'string') {
                window[modalConfig.onSaved](modal, this.state.availLangs);
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
                <div className={this.props.cClass || ''}>
                    <button type="button" style={{marginBottom: '10px'}} onClick={this.showModal}
                            className={"btn btn-xs multilang " + (langLabel ? 'btn-info' : '')}>{!langLabel ?
                        <i className="icon icon-globe"/> : ''} {langLabel || Locale._('Translate')}
                    </button>
                    <Components.Modal {...this.getModalConfig()} cancel={null}>
                        <div className="well">
                            <table>
                                <tbody>
                                <tr>
                                    <td><FComSelect2 {...inlineProps} options={defaultLangs}
                                                                      onChange={this.handleSelect2Change}
                                                                      defaultValue={[]}/>
                                    </td>
                                    <td>
                                        <button className='btn btn-sm btn-primary' onClick={this.addLocaleField}
                                                type="button">{Locale._('Add Locale')}</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id={this.props.id + '-container'}>
                            <LangFields id={this.props.id} langs={this.state.availLangs || []}
                                        removeField={this.removeLangField}
                                        setLangVal={this.setLangVal}/>
                        </div>
                    </Components.Modal>
                </div>
            );
        }
    });

    return FComMultiLanguage;
});