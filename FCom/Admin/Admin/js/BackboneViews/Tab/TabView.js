define(['backbone', 'marionette', 'formGenerator'],
    function (Backbone, Marionette, FormGenerator) {
        var TabView = Backbone.Marionette.ItemView.extend({
            region: {},
            className: "tab-pane",
            template: "#tab-content-template",
            id: function () {
                return "tabs" + this.model.id;
            },
            initialize: function () {
                _this = this;
                this.region = new Backbone.Marionette.Region({
                    el: _this.el
                });
            },
            onRender: function () {
                // TODO here we check if is form, grid, or graph....what do you neeeeed?
                formview = new FormGenerator({modelToUse: this.model.get('modelToUse')});
                this.region.show(formview);
            }

        });
        return TabView;
    }
);
