define(['sv-hlp', 'text!sv-page-catalog-fields-form-options-tpl'], function (SvHlp, tabFieldOptionsTpl) {
    return {
        mixins: [SvHlp.mixins.common],
        template: tabFieldOptionsTpl,
        methods: {
            fetchData: function (to, from) {
                debugger
                var vm = this;
                if (this.$route.params[0]) {
                    var path = '/' + this.$route.params[0], i1, l1, n1, i2, l2, n2;
                    for (i1 = 0, l1 = this.settings.config.nav.length; i1 < l1; i1++) {
                        n1 = this.settings.config.nav[i1];
                        if (_.isEmpty(n1.children)) {
                            continue;
                        }
                        for (i2 = 0, l2 = n1.children.length; i2 < l2; i2++) {
                            n2 = n1.children[i2];
                            if (n2.path === path) {
                                this.switchTab(n2);
                            }
                        }
                    }
                }
                if (!from) {
                    this.sendRequest('GET', 'catalogfields/options/form_data', {}, function (response) {
                    });
                }
            }
        }
    }
});