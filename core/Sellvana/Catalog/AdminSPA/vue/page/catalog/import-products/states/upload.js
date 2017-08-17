define(['sv-app-data', 'sv-mixin-common', 'vue-dropzone', 'text!sv-page-catalog-import-products-upload-tpl'],
    function (SvAppData, SvMixinCommon, VueDropzone, tpl) {
    var uploadedEventName = 'uploaded';
    var uploadingEventName = 'uploading';
    return {
        mixins: [SvMixinCommon],
        data: function () {
            return {
                upload_url: SvAppData.env.root_href + 'import-products/upload?type=product-import',
                dropzone_options: {
                    maxFiles: 1,
                    'paramName': 'upload',
                    'uploadMultiple': true,
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
                this.$emit(uploadingEventName, file);
            },
            dropzoneSuccess: function (file, result) {
                this.$emit(uploadedEventName, result)
            }
        },
        template: tpl
    }
});