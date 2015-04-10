/** @jsx React.DOM */

define(['underscore', 'react', 'jquery', 'fcom.griddle', 'fcom.components'], function(_, React, $, FComGriddleComponent, Components) {

    var FComMediaLib = React.createClass({
        displayName: "FComMediaLib",
        mixins: [FCom.Mixin],
        getDefaultProps: function() {
            //todo: validate received props
            return {
                "mediaConfig": {},
                "modalConfig": {},
                "uploadConfig": {
                    "can_upload": false,
                    "filetype_regex": "",
                    "folder": ""
                }
            }
        },
        getModalConfig: function() {
            var mediaGridId = this.props.mediaConfig.id;
            var modalConfig = this.props.modalConfig;

            if (modalConfig == null || modalConfig == '') {
                modalConfig = {};
            }

            var config = $.extend({}, {
                title: 'Media',
                confirm: null,
                cancel: 'Close',
                show: false,
                id: mediaGridId + '-media-modal',
                onLoad: this.updateModalWidth,
                onConfirm: null,
                onCancel: null,
                ref: 'modal'
            }, modalConfig);

            return config;
        },
        mediaUploadElement: function() {
            return (
                React.createElement('div', { className: 'box-content' },
                    React.createElement('div', { className: 'row fileupload-buttonbar' },
                        React.createElement('div', { className: 'col-sm-12' },
                            React.createElement('span', { className: 'btn btn-success fileinput-button' },
                                React.createElement('i', { className: 'icon-plus icon-white' }),
                                React.createElement('span', null, 'Add files...'),
                                React.createElement('input', { 'data-bfi-disabled': '', 'multiple': 'multiple', name: 'upload[]', type: 'file' })
                            ),
                            React.createElement('button', { className: 'btn btn-primary start', type: 'submit' },
                                React.createElement('i', { className: 'icon-upload icon-white' }),
                                React.createElement('span', null, 'Start upload')
                            ),
                            React.createElement('button', { className: 'btn btn-warning cancel', type: 'reset' },
                                React.createElement('i', { className: 'icon-ban-circle icon-white' }),
                                React.createElement('span', null, 'Cancel upload')
                            )
                        ),
                        React.createElement('div', { className: 'col-sm-5 fileupload-progress fade' },
                            React.createElement('div', { className: 'progress progress-success progress-striped active', role: 'progressbar', 'aria-valuemax': '100', 'aria-valuemin': '0' },
                                React.createElement('div', { className: 'bar', style: { 'width': '0%' } })
                            ),
                            React.createElement('div', { className: 'progress-extended2' })
                        )
                    ),
                    React.createElement('div', { className: 'fileupload-loading' }),
                    React.createElement('br', null),
                    React.createElement('table', { className: 'table table-striped', role: 'presentation' },
                        React.createElement('tbody', { className: 'files', 'data-target': '#modal-gallery', 'data-toggle': 'modal-gallery'})
                    )
                )
            );
        },
        getMainGridComponent: function() {
            if (this.isMounted() && typeof this.refs['fcomGriddleComponent'].refs[this.props.mediaConfig.id] != 'undefined') {
                return this.refs['fcomGriddleComponent'].refs[this.props.mediaConfig.id];
            }
            return null;
        },
        getModalComponent: function() {
            if (this.isMounted() && typeof this.refs['modal'] != 'undefined') {
                return this.refs['modal'];
            }
            return null
        },
        render: function() {
            console.log('render fcom mediablib');
            var modalConfig = this.getModalConfig();
            var uploadConfig = this.props.uploadConfig;
            var mediaGridId = this.props.mediaConfig.id;

            /*console.log('modalConfig', modalConfig);
            console.log('propsmodalConfig', this.props.modalConfig);
            console.log('uploadConfig', this.props.uploadConfig);
            console.log('mediaConfig', this.props.mediaConfig);*/

            var mainGridElement = React.createElement(FComGriddleComponent, { config: this.props.mediaConfig, ref: 'fcomGriddleComponent' });

            return (
                React.createElement(Components.Modal, modalConfig,
                    React.createElement('div', { className: 'row' },
                        React.createElement('div', { className: 'tabbable' },
                            React.createElement("ul", { className: "nav nav-tabs prod-type f-horiz-nav-tabs" },
                                React.createElement("li", { className: "active" },
                                    React.createElement("a", { "data-toggle": "tab", href: '#' + mediaGridId + '-attach_library' }, "Library")
                                ),
                                uploadConfig.can_upload
                                    ? React.createElement("li", null,
                                        React.createElement("a", { "data-toggle": "tab", href: '#' + mediaGridId + '-media-upload' }, "Upload")
                                    ) : null
                            ),
                            React.createElement("div", { className: "tab-content" },
                                React.createElement('div', { className: 'tab-pane active', id: mediaGridId + '-attach_library' }, mainGridElement),
                                (uploadConfig.can_upload
                                    ? React.createElement('div', { className: 'tab-pane', id: mediaGridId + '-media-upload' }, this.mediaUploadElement())
                                    : null)
                            )
                        )
                    )
                )
            )
        }
    });

    return FComMediaLib;
});