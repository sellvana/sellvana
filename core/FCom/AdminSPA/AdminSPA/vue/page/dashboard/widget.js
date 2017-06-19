define(['sv-hlp', 'text!sv-page-dashboard-widget-tpl'], function (SvHlp, widgetTpl) {
    var SvPageDashboardWidget = {
        template: widgetTpl,
        props: ['widget'],
        computed: {
            component: function () {
                if (this.widget.component) {
                    return require(this.widget.component);
                } else if (this.widget.template) {
                    return {
                        mixins: [SvHlp.mixins.common],
                        props: ['widget'],
                        template: require('text!' + this.widget.template)
                    }
                }
            }
        }
    };

    return SvPageDashboardWidget;
});