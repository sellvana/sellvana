/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'fcom.griddle', 'fcom.components', 'griddle.fcomSelect2', 'fcom.locale'], function (_, React, $, FComGriddleComponent, Components, FComSelect2, Locale) {

    var LANGAPP = {};
    var langChoosen = '';

    var LangFields = React.createClass({
        mixins: [FCom.Mixin],
        componentDidMount: function () {

        },
        removeLangField: function (e) {

        },
        render: function () {
            return (
                <div>
                    {_.map(this.props.langs, function (lang, key) {
                        return (
                            <div key={key} className="form-group">
                                <div className="col-md-3 control-label">
                                    <span className="badge badge-default">{lang.lang_code}</span>
                                </div>
                                <div className="col-md-6">
                                    <input type="text" className="form-control" data-rule-required="true" name={guid()} defaultValue={lang.value} />
                                </div>
                                <div className="col-md-3">
                                    <button type="button" onClick={this.removeLangField} className="btn btn-default btn-sm field-remove"><i className="icon-remove"></i></button>
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
                availLangs: this.getAvalLangs(),
                defaultLangs: this.props.defaultLangs
            };
        },
        getDefaultProps: function () {
            return {
                availLangs: {},
                select2Config: {},
                modalConfig: {},
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

        },
        componentDidMount: function () {
            //console.log(this.props);
        },
        getModalConfig: function () {
            var config = $.extend({}, {
                title: Locale._('Multi Languages'),
                confirm: Locale._('Save Change'),
                cancel: Locale._('Close'),
                show: false,
                id: this.props.type + '-modal-' + this.props.id,
                onLoad: this.updateModalWidth,
                onConfirm: null,
                onCancel: null,
                ref: 'modal'
            }, this.props.modalConfig);

            if (typeof config.onConfirm === 'string') {
                config.onConfirm = window[config.onConfirm];
            }

            return config;
        },
        getModalNode: function () {
            return $('#' + this.props.type + '-modal-' + this.props.id);
        },
        getSelect2Config: function () {
            return $.extend({}, {
                id: 'multilang-' + this.props.id,
                name: 'multilang-' + this.props.id,
                className: '',
                'data-col': 'multilang'
            }, this.props.select2Config);
        },
        handleSelect2Change: function (event, callback, selections) {
            if (typeof window[callback] === 'function') {
                return window[callback](event, selections);
            }
        },
        getDefaultLangs: function () {
            var that = this;
            var defaultLangs = [];

            var diff = _.difference(_.pluck(this.state.defaultLangs, 'id'), _.pluck(this.state.availLangs, 'lang_code'));

            _(this.state.defaultLangs).each(function (lang) {
                if (_.contains(diff, lang.id)) defaultLangs.push(lang);
            });

            return defaultLangs;
        },
        getAvalLangs: function () {
            if (this.props.availLangs && typeof this.props.availLangs === 'string') {
                this.props.availLangs = JSON.parse(this.props.availLangs);
            }

            return this.props.availLangs;
        },
        AddLocaleField: function(e) {
            this.state.availLangs.push({
                lang_code: langChoosen.id,
                value: ''
            });

            this.state.defaultLangs = this.getDefaultLangs();

            this.forceUpdate();
        },
        showModal: function () {
            var $modal = this.getModalNode();
            $modal.modal('show');
        },
        render: function () {
            var modalConfig = this.getModalConfig();
            var inlineProps = this.getSelect2Config();
            var defaultLangs = this.getDefaultLangs();

            if (!langChoosen) {
                langChoosen = defaultLangs[0];
            }

            return (
                <div className="row multilang-field">
                    <div className="col-md-2"></div>
                    <div className="col-md-5">
                        <button type="button" style={{marginTop: '5px', marginBottom: '10px'}} onClick={this.showModal}
                                className="btn btn-xs multilang">Set Locale...
                        </button>
                    </div>
                    <Components.Modal {...modalConfig}>
                        <div className="well">
                            <table>
                                <tbody>
                                <tr>
                                    <td><FComSelect2 {...inlineProps} options={defaultLangs}
                                                                      multiple={this.props.multiple || false}
                                                                      placeholder={this.props.placeholder || "Select some options"}
                                                                      onChange={this.handleSelect2Change}
                                                                      defaultValue={['de_DE']}
                                                                      callback={this.props.callback}/></td>
                                    <td>
                                        <button className='btn btn-primary' onClick={this.AddLocaleField} type="button">{Locale._('Add Locale')}</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div className={this.props.type + '-container'}>
                            <LangFields langs={this.state.availLangs} />
                        </div>
                    </Components.Modal>
                </div>
            );
        }
    });

    window.addLangFieldCallback = function (e, selections) {
        console.log('selections', selections);
        langChoosen = selections;
    };

    return FComMultiLanguage;

});