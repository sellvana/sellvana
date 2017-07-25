define(['sv-mixin-common', 'text!sv-page-catalog-import-products-tpl',
'sv-page-catalog-import-products-upload',
'sv-page-catalog-import-products-configure',
'sv-page-catalog-import-products-import',
'sv-page-catalog-import-products-status'
], function (SvMixinCommon, tpl, SvCsvImpUpload, SvCsvImpConfigure, SvCsvImpImport, SvCsvImpStatus) {
    var store = SvMixinCommon.store;

    var states = {
        upload: 'Upload file',
        configure: 'Configure import',
        import: 'Import',
        status: 'Import Status'
    };

    store.registerModule('csvImport', {
        state: {
            currentState: states.upload,
            states: states
        },
        mutations: {
            setCurrentState: function (state, currentState) {
                if (state.states.hasOwnProperty(currentState)) {
                    state.currentState = currentState;
                    return;
                }
                console.warn(currentState + ' is not valid import state', 'Valid import states are: ', state.states);
            }
        }
    });
    console.log(store.state.csvImport);
    var Component = {
        data: function () {
            return {
                currentState: store.state.csvImport.currentState,
                states: store.state.csvImport.states
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