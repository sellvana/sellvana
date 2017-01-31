/**
 * @see original: https://github.com/dangvanthanh/vue-ckeditor
 * @license MIT (C) Dang Van Tranh
 */
define(['jquery', 'vue', 'ckeditor'], function ($, Vue) {
    var VueCkeditor = {
        template: '<div class="ckeditor"><textarea :id="id" :value="value"></textarea></div>',
        props: {
            value: {
                type: String
            },
            id: {
                type: String,
                default: 'editor'
            },
            height: {
                type: String,
                default: '200px'
            },
            toolbar: {
                type: Array,
                default: function () {
                    return [['Source', 'Format'], ['Bold', 'Italic'], ['Undo', 'Redo']];
                }
            },
            toolbarGroups: {
                type: Array,
                default: function () {
                    return [
                        { name: 'document', groups: [ 'mode', 'document', 'doctools' ] },
                        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
                        { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi', 'paragraph' ] },
                        { name: 'styles', groups: [ 'styles' ] },
                        { name: 'links', groups: [ 'links' ] },
                        { name: 'insert', groups: [ 'insert' ] },
                        { name: 'clipboard', groups: [ 'clipboard', 'undo' ] },
                        { name: 'editing', groups: [ 'find', 'selection', 'spellchecker', 'editing' ] },
                        { name: 'forms', groups: [ 'forms' ] },
                        { name: 'others', groups: [ 'others' ] },
                        { name: 'tools', groups: [ 'tools' ] },
                        { name: 'colors', groups: [ 'colors' ] },
                        { name: 'about', groups: [ 'about' ] }
                    ];
                }
            },
            language: {
                type: String,
                default: 'en'
            },
            extraPlugins: {
                type: String,
                default: ''
            }
        },
        beforeUpdate: function () {
            const ckeditorId = this.id;
            if (this.value !== CKEDITOR.instances[ckeditorId].getData()) {
                CKEDITOR.instances[ckeditorId].setData(this.value);
            }
        },
        mounted: function () {
            var vm = this;
            var ckeditorId = this.id;
            var ckeditorConfig = {
                //toolbar: this.toolbar,
                //toolbarGroups: this.toolbarGroups,
                language: this.language,
                height: this.height,
                extraPlugins: this.extraPlugins,
                toolbarCanCollapse: true,
                toolbarStartupExpanded: false
            };
            CKEDITOR.replace(ckeditorId, ckeditorConfig);
            CKEDITOR.instances[ckeditorId].setData(this.value);
            CKEDITOR.instances[ckeditorId].on('change', function () {
                var ckeditorData = CKEDITOR.instances[ckeditorId].getData();
                if (ckeditorData !== vm.value) {
                    $('#' + ckeditorId).val(ckeditorData);
                    vm.$emit('input', ckeditorData);
                }
            });
        },
        beforeDestroy: function () {
            var ckeditorId = this.id;
            if (CKEDITOR.instances[ckeditorId]) {
                //CKEDITOR.instances[ckeditorId].destroy(); //throws error for some reason
            }
        }
    };

    Vue.component('ckeditor', VueCkeditor);

    return VueCkeditor;
});