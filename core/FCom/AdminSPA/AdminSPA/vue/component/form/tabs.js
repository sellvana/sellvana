define(['vue', 'sv-hlp', 'text!sv-comp-form-tabs-tpl'], function (Vue, SvHlp, tabsTpl) {
    var SvCompFormTabs = {
        mixins: [SvHlp.mixins.common],
        props: ['form', 'container-class', 'tab'],
        template: tabsTpl,
        computed: {
            formTabs: function () {
                return this.form && this.form.config && this.form.config.tabs || [];
            }
        },
        methods: {
            switchTab: function (tab) {
                this.$emit('event', 'tab_switch', tab);
            }
        }
    };

    Vue.component('sv-comp-form-tabs', SvCompFormTabs);

    return SvCompFormTabs;
});