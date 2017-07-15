define(['vue', 'dropzone', 'vue-dropzone', 'sv-app-data', 'text!sv-comp-form-media-tpl'], function (Vue, Dropzone, VueDropzone, SvAppData, mediaTpl) {

    var SvCompFormMedia = {
        template: mediaTpl,
        props: ['form'],
        components: {
            dropzone: VueDropzone
        },
        computed: {
            id: function () {
                return 'dropzone';
            },
            dropzoneOptions: function () {
                return {
                    uploadMultiple: true,
                    addRemoveLinks: true,
                    headers: {'X-CSRF-TOKEN': SvAppData.csrf_token}
                };
            },
            uploadUrl: function () {
                return SvAppData.env.root_href + 'media/upload';
            }
        },
        methods: {
            showSuccess: function () {
                console.log('success');
            }
        }
    };

    Vue.component('sv-comp-form-media', SvCompFormMedia);

    return SvCompFormMedia;
});