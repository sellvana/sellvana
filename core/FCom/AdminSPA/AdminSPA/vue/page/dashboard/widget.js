define(['text!sv-page-dashboard-widget-tpl'], function (widgetTpl) {
    var SvPageDashboardWidget = {
        template: widgetTpl,
        props: ['widget'],
        computed: {
            component: function () {
                if (this.widget.component) {
                    return require(this.widget.component);
                } else if (this.widget.template) {
                    return {
                                                props: ['widget'],
                        template: require('text!' + this.widget.template)
                    }
                }
            }
        }
    };

    return SvPageDashboardWidget;
});