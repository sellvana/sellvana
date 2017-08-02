define(['lodash', 'sv-mixin-common', 'text!sv-page-catalog-import-products-tpl',
'sv-page-catalog-import-products-upload',
'sv-page-catalog-import-products-configure',
'sv-page-catalog-import-products-import',
'sv-page-catalog-import-products-status'
], function (_, SvMixinCommon, tpl, SvCsvImpUpload, SvCsvImpConfigure, SvCsvImpImport, SvCsvImpStatus) {
    var store = SvMixinCommon.store;

    var states = {
        "upload": 'Upload file',
        "configure": 'Configure import',
        "import": 'Import',
        "status": 'Import Status'
    };

    store.registerModule('csvImport', {
        state: {
            currentState: states.upload,
            states: states
        },
        mutations: {
            setCurrentState: function (state, currentState) {
                if (state.states.hasOwnProperty(currentState)) {
                    state.currentState = state.states[currentState];
                    return;
                }
                console.warn(currentState + ' is not valid import state', 'Valid import states are: ', state.states);
            }
        }
    });

    var Component = {
        data: function () {
            return {
                states: store.state.csvImport.states,
                isUploaded: false,
                file: {}
            }
        },
        computed: {
            currentState: function () {
                return store.state.csvImport.currentState;
            },
            fileConfig: function () {
                return this.file.files ? this.file.files[0] : {};
            },
            hasPreviousImport: function () {
                return true;
            }
        },
        methods: {
            showStatus: function () {
                this.$store.commit('setCurrentState', "status");
            },
            onConfigure: function () {
                this.$store.commit('setCurrentState', "configure");
            },
            startOver: function () {
                this.$store.commit('setCurrentState', "upload");
                this.isUploaded = false;
            },
            onUploadComplete: function (result) {
                _.assign(this.file, result);
                this.isUploaded = true;
                // this.$store.commit('setCurrentState', "configure");
            },
            switchClass: function (step) {
                var className = 'f-switch f-switch' + step;
                if (
                    this.currentState === states.upload && step == 1
                    || this.currentState === states.configure && step == 2
                    || (this.currentState === states.import || this.currentState === states.status ) && step == 3
                ) {
                    className += ' active'
                }

                return className;
            }
        },
        components: {
            'sv-csv-imp-upload': SvCsvImpUpload,
            'sv-csv-imp-configure': SvCsvImpConfigure,
            'sv-csv-imp-import': SvCsvImpImport,
            'sv-csv-imp-status': SvCsvImpStatus
        },
        mixins: [SvMixinCommon],
        template: tpl
    };

    return Component;
});