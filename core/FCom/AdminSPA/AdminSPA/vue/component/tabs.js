define(['vue', 'sv-hlp', 'text!sv-comp-tabs-tpl'], function (Vue, SvHlp, tabsTpl) {
    var SvCompFormTabs = {
        mixins: [SvHlp.mixins.common],
        props: {
            'config': {type: Object},
            'container-class': {type: String, default: 'f-tabs-container'},
            'tab': {type: [Object, Boolean]}
        },
        template: tabsTpl,
        computed: {
            formTabs: function () {
                return this.config && this.config.tabs || [];
            }
        },
        methods: {
            switchTab: function (tab) {
                this.$emit('event', 'tab_switch', tab);
            }
        }
    };

    Vue.component('sv-comp-tabs', SvCompFormTabs);

    return SvCompFormTabs;
});