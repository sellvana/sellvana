define(['lodash', 'vue', 'sv-mixin-common', 'text!sv-page-catalog-import-products-tpl',
    'sv-page-catalog-import-products-upload',
    'sv-page-catalog-import-products-configure',
    'sv-page-catalog-import-products-import',
    'sv-page-catalog-import-products-status'
], function (_, Vue, SvMixinCommon, tpl, SvCsvImpUpload, SvCsvImpConfigure, SvCsvImpImport, SvCsvImpStatus) {
    var store = SvMixinCommon.store;

    window.$importBus = new Vue();

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
                baseUrl: 'import-products',
                config: {},
                states: store.state.csvImport.states,
                isUploaded: false,
                file: {}
            }
        },
        mounted: function () {
            this.fetchStatus();
        },
        computed: {
            currentState: function () {
                return store.state.csvImport.currentState;
            },
            fileConfig: function () {
                return this.file.files ? this.file.files[0] : {};
            },
            hasPreviousImport: function () {
                return Object.keys(this.config).length > 0;
            },
            canShowPreviousImport: function () {
                return this.hasPreviousImport && !this.stateStatus;
            },
            stateConfigure: function () {
                return this.currentState === states.configure;
            },
            canUpload: function () {
                return this.stateConfigure || this.stateStatus;
            },
            stateUpload: function () {
                return this.currentState === states.upload;
            },
            stateImport: function () {
                return this.currentState === states.import;
            },
            stateStatus: function () {
                return this.currentState === states.status;
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
            onImportComplete: function () {
                console.log('complete');

                this.$store.commit('setCurrentState', "status");
                this.fetchStatus();
            },
            onImportStart: function () {
                console.log('import-start');
                window.$importBus.$emit('import-start'); // notify components that import has to start
            },
            onConfigComplete: function () {
                this.$store.commit('setCurrentState', "import"); // start import when config is saved
            },
            fetchStatus: function () {
                // fetch status from admin and update config
                var url = this.baseUrl + '/status';
                var self = this;
                console.log(url);

                this.sendRequest('GET', url, {})
                    .done(function (result) {
                        console.log(result);

                        if (result) {
                            self.config = result;
                        }
                    })
                    .fail(function (error) {
                        console.error('ADMIN-SPA', error);
                    });
            },
            switchClass: function (step) {
                var className = 'f-switch f-switch' + step;
                if (
                    this.currentState === states.upload && step === 1
                    || this.currentState === states.configure && step === 2
                    || (this.currentState === states.import || this.currentState === states.status ) && step === 3
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