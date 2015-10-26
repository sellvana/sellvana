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
                },
                "showModal": true
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
                React.createElement("div", {className: "box-content"}, 
                    React.createElement("div", {className: "row fileupload-buttonbar"}, 
                        React.createElement("div", {className: "col-sm-12"}, 
                            React.createElement("span", {className: "btn btn-success fileinput-button"}, 
                                React.createElement("i", {className: "icon-plus icon-white"}), 
                                React.createElement("span", null, "Add files..."), 
                                React.createElement("input", {"data-bfi-disabled": "", multiple: "multiple", name: "upload[]", type: "file"})
                            ), 
                            React.createElement("button", {className: "btn btn-primary start", type: "submit"}, 
                                React.createElement("i", {className: "icon-upload icon-white"}), 
                                React.createElement("span", null, "Start upload")
                            ), 
                            React.createElement("button", {className: "btn btn-warning cancel", type: "reset"}, 
                                React.createElement("i", {className: "icon-ban-circle icon-white"}), 
                                React.createElement("span", null, "Cancel upload")
                            )
                        ), 
                        React.createElement("div", {className: "col-sm-5 fileupload-progress fade"}, 
                            React.createElement("div", {className: "progress progress-success progress-striped active", role: "progressbar", "aria-valuemax": "100", "aria-valuemin": "0"}, 
                                React.createElement("div", {className: "bar", style: {width: '0%'}})
                            ), 
                            React.createElement("div", {className: "progress-extended2"})
                        )
                    ), 
                    React.createElement("div", {className: "fileupload-loading"}), 
                    React.createElement("br", null), 
                    React.createElement("table", {className: "table table-striped", role: "presentation"}, 
                        React.createElement("tbody", {className: "files", "data-target": "#modal-gallery", "data-toggle": "modal-gallery"})
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
        getMainGridEle: function() {
            return React.createElement(FComGriddleComponent, { config: this.props.mediaConfig, ref: 'fcomGriddleComponent' });
        },
        getEmbedGrid: function () {
            return (
                React.createElement("div", {className: "video-tab-container"}, 
                    React.createElement("div", {className: "col-sm-12"}, 
                        React.createElement("div", {className: "control-label col-sm-1"}, 
                            React.createElement("label", {htmlFor: "oembed_url"}, "URL: ")
                        ), 
                        React.createElement("div", {className: "controls col-sm-7"}, 
                            React.createElement("input", {type: "text", id: "oembed_url", name: "oembed_url", className: "form-control oembed_url"})
                        ), 
                        React.createElement("div", {className: "controls col-sm-1"}, 
                            React.createElement("button", {type: "button", className: "btn btn-primary btn-sm btn-preview", "data-loading-text": "Processing..."}, "Preview")
                        ), 
                        React.createElement("div", {className: "controls col-sm-2"}, 
                            React.createElement("button", {type: "button", className: "btn btn-success btn-sm btn-embed", "data-loading-text": "Processing..."}, "Add to Library")
                        )
                    ), 
                    React.createElement("div", {className: "col-sm-12"}, 
                        React.createElement("div", {className: "control-label col-sm-1"}, 
                            React.createElement("label", {htmlFor: ""}, "Provider: ")
                        ), 
                        React.createElement("div", {className: "col-sm-11"}, 
                            React.createElement("label", {className: "radio-inline"}, React.createElement("input", {type: "radio", name: "provider", value: "youtube"}), "Youtube"), 
                            React.createElement("label", {className: "radio-inline"}, React.createElement("input", {type: "radio", name: "provider", value: "vimeo"}), "Vimeo"), 
                            React.createElement("label", {className: "radio-inline"}, React.createElement("input", {type: "radio", name: "provider", value: "other"}), "Other Providers")
                        )
                    ), 
                    React.createElement("div", {className: "col-sm-12 oembed_container"})
                )
            );
        },
        renderModal: function(modalConfig) {
            return (
                React.createElement(Components.Modal, React.__spread({},  modalConfig), 
                    React.createElement("ul", {className: "nav nav-tabs f-horiz-nav-tabs", role: "tablist"}, 
                        React.createElement("li", {role: "presentation", className: "active"}, 
                            React.createElement("a", {href: '#' + this.props.mediaConfig.id + '-attach_library', role: "tab", "data-toggle": "tab", "aria-controls": this.props.mediaConfig.id + '-attach_library'}, "Library")
                        ), 
                        this.props.uploadConfig.can_upload ? React.createElement("li", {role: "presentation"}, React.createElement("a", {href: '#' + this.props.mediaConfig.id + '-media-upload', role: "tab", "data-toggle": "tab", "aria-controls": this.props.mediaConfig.id + '-media-upload'}, "Upload")) : null, 
                        React.createElement("li", {role: "presentation"}, React.createElement("a", {role: "tab", "data-toggle": "tab", href: '#' + this.props.mediaConfig.id + '-media-embed'}, "Media Embed"))
                    ), 
                    React.createElement("div", {className: "tab-content"}, 
                        React.createElement("div", {role: "tabpanel", className: "tab-pane active", id: this.props.mediaConfig.id + '-attach_library'}, this.getMainGridEle()), 
                        this.props.uploadConfig.can_upload ? React.createElement("div", {role: "tabpanel", style: { width: '870px', 'padding': '20px'}, className: "tab-pane", id: this.props.mediaConfig.id + '-media-upload'}, this.mediaUploadElement()) : null, 
                        this.props.uploadConfig.can_upload ? React.createElement("div", {role: "tabpanel", className: "tab-pane", style: { width: '870px', 'padding': '20px'}, id: this.props.mediaConfig.id + '-media-embed'}, this.getEmbedGrid()) : null
                    )
                )
            );
        },
        renderView: function() {
            return (
                React.createElement("div", {className: "tabbable"}, 
                    React.createElement("ul", {className: "nav nav-tabs prod-type f-horiz-nav-tabs"}, 
                        React.createElement("li", {className: "active"}, 
                            React.createElement("a", {"data-toggle": "tab", href: '#' + this.props.mediaConfig.id + '-attach_library'}, "Library")
                        ), 
                        this.props.uploadConfig.can_upload ? React.createElement("li", null, React.createElement("a", {"data-toggle": "tab", href: '#' + this.props.mediaConfig.id + '-media-upload'}, "Upload")) : null
                    ), 
                    React.createElement("div", {className: "tab-content"}, 
                        React.createElement("div", {className: "tab-pane active", id: this.props.mediaConfig.id + '-attach_library'}, this.getMainGridEle()), 
                        this.props.uploadConfig.can_upload ? React.createElement("div", {className: "tab-pane", id: this.props.mediaConfig.id + '-media-upload'}, this.mediaUploadElement()) : null
                    )
                )
            );
        },
        render: function() {
            /*console.log('modalConfig', this.getModalConfig());
            console.log('propsmodalConfig', this.props.modalConfig);
            console.log('uploadConfig', this.props.uploadConfig);
            console.log('mediaConfig', this.props.mediaConfig);*/
            if (this.props.showModal === true) {
                var modalConfig = this.getModalConfig();
                return this.renderModal(modalConfig);
            } else {
                return this.renderView();
            }
        }
    });

    return FComMediaLib;
});
