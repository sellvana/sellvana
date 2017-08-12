define(['text!sv-page-modules-grid-datacell-run-level-tpl'], function (tpl) {
    var Component = {
        props: ['grid', 'row', 'col'],
        template: tpl,
        data: function () {
            return {
                run_level_options: ['ONDEMAND', 'REQUIRED', 'REQUESTED', 'DISABLED']
            }
        },
        computed: {

        },
        methods: {
            setRunLevel: function (runLevel) {
                var vm = this, postData = {data: [{module_name: this.row.name, run_level_core: runLevel}]};
                this.sendRequest('POST', '/modules', postData, function (response) {
                    vm.$emit('event', 'fetch-data');
                });
            }
        }
    };

    return Component;
})