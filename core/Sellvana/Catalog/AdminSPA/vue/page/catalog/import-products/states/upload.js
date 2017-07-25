define(['sv-mixin-common', 'vue-dropzone', 'text!sv-page-catalog-import-products-upload-tpl'], function (SvMixinCommon, VueDropzone, tpl) {

    return {
        mixins: [SvMixinCommon],
        data: function () {
            return {
                upload_url: '/catalog/products/import/config',
                dropzone_options: {
                    'acceptedFileTypes': 'text/txt,text/csv',
                    'maxFileSizeInMB': 100,
                    'autoProcessQueue': true,
                    'headers': {'X-CSRF-TOKEN': this.$store.state.csrfToken}
                }
            }
        },
        components: {
            dropzone: VueDropzone
        },
        methods: {

            dropzoneSending: function (file, xhr, formData) {
                console.log(file, xhr, formData);
            },
            dropzoneSuccess: function () {

            }
        },
        template: tpl
    }
});