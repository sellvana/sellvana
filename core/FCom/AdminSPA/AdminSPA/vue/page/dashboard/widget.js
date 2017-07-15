define(['sv-mixin-common', 'text!sv-page-dashboard-widget-tpl'], function (SvMixinCommon, widgetTpl) {
    var SvPageDashboardWidget = {
        template: widgetTpl,
        props: ['widget'],
        computed: {
            component: function () {
                if (this.widget.component) {
                    return require(this.widget.component);
                } else if (this.widget.template) {
                    return {
                        mixins: [SvMixinCommon],
                        props: ['widget'],
                        template: require('text!' + this.widget.template)
                    }
                }
            }
        }
    };

    return SvPageDashboardWidget;
});