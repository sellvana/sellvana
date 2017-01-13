/**
 * @see original: https://github.com/dangvanthanh/vue-ckeditor
 * @license MIT (C) Dang Van Tranh
 */
define(['ckeditor'], function () {
    return {
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
                    return [['Format'], ['Bold', 'Italic'], ['Undo', 'Redo']];
                }
            },
            language: {
                type: String,
                default: 'en'
            },
            extraplugins: {
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
                toolbar: this.toolbar,
                language: this.language,
                height: this.height,
                extraPlugins: this.extraplugins
            };
            CKEDITOR.replace(ckeditorId, ckeditorConfig);
            CKEDITOR.instances[ckeditorId].setData(this.value);
            CKEDITOR.instances[ckeditorId].on('change', function () {
                var ckeditorData = CKEDITOR.instances[ckeditorId].getData();
                if (ckeditorData !== this.value) {
                    vm.$emit('input', ckeditorData);
                }
            });
        },
        beforeDestroy: function () {
            var ckeditorId = this.id;
            if (CKEDITOR.instances[ckeditorId]) {
                //CKEDITOR.instances[ckeditorId].destroy();
            }
        }
    };

});