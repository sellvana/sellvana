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
                <div className="box-content">
                    <div className="row fileupload-buttonbar">
                        <div className="col-sm-12">
                            <span className="btn btn-success fileinput-button">
                                <i className="icon-plus icon-white"></i>
                                <span>Add files...</span>
                                <input data-bfi-disabled="" multiple="multiple" name="upload[]" type="file" />
                            </span>
                            <button className="btn btn-primary start" type="submit">
                                <i className="icon-upload icon-white"></i>
                                <span>Start upload</span>
                            </button>
                            <button className="btn btn-warning cancel" type="reset">
                                <i className="icon-ban-circle icon-white"></i>
                                <span>Cancel upload</span>
                            </button>
                        </div>
                        <div className="col-sm-5 fileupload-progress fade">
                            <div className="progress progress-success progress-striped active" role="progressbar" aria-valuemax="100" aria-valuemin="0">
                                <div className="bar" style={{width: '0%'}}></div>
                            </div>
                            <div className="progress-extended2"></div>
                        </div>
                    </div>
                    <div className="fileupload-loading"></div>
                    <br />
                    <table className="table table-striped" role="presentation">
                        <tbody className="files" data-target="#modal-gallery" data-toggle="modal-gallery"></tbody>
                    </table>
                </div>
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
        renderModal: function(modalConfig) {
            return (
                <Components.Modal {...modalConfig}>
                    <div className="row">
                        <div className="tabbable">
                            <ul className="nav nav-tabs prod-type f-horiz-nav-tabs">
                                <li className="active">
                                    <a data-toggle="tab" href={'#' + this.props.mediaConfig.id + '-attach_library'}>Library</a>
                                </li>
                                {this.props.uploadConfig.can_upload ? <li><a data-toggle="tab" href={'#' + this.props.mediaConfig.id + '-media-upload'}>Upload</a></li> : null}
                            </ul>
                            <div className="tab-content">
                                <div className="tab-pane active" id={this.props.mediaConfig.id + '-attach_library'}>{this.getMainGridEle()}</div>
                                {this.props.uploadConfig.can_upload ? <div className="tab-pane" id={this.props.mediaConfig.id + '-media-upload'}>{this.mediaUploadElement()}</div> : null}
                            </div>
                        </div>
                    </div>
                </Components.Modal>
            );
        },
        renderView: function() {
            return (
                <div className="tabbable">
                    <ul className="nav nav-tabs prod-type f-horiz-nav-tabs">
                        <li className="active">
                            <a data-toggle="tab" href={'#' + this.props.mediaConfig.id + '-attach_library'}>Library</a>
                        </li>
                        {this.props.uploadConfig.can_upload ? <li><a data-toggle="tab" href={'#' + this.props.mediaConfig.id + '-media-upload'}>Upload</a></li> : null}
                    </ul>
                    <div className="tab-content">
                        <div className="tab-pane active" id={this.props.mediaConfig.id + '-attach_library'}>{this.getMainGridEle()}</div>
                        {this.props.uploadConfig.can_upload ? <div className="tab-pane" id={this.props.mediaConfig.id + '-media-upload'}>{this.mediaUploadElement()}</div> : null}
                    </div>
                </div>
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
