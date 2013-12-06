define(['backbone', 'marionette', 'backbone.forms'],
    function (Backbone, Marionette, Form) {
        var FormGenerator = Backbone.Marionette.ItemView.extend({
            id: function () {
                return this.options.modelToUse;
            },
            initialize: function (options) {
                this.modelToUse = options.modelToUse;
            },
            template: '<div><a id="cancel" href="#">reset</a></div>',
            events: {
                "click #cancel": "restoreValues"
            },
            restoreValues: function () {
                this.model.restore();
            },
            onRender: function () {
                var modelrequired = this.modelToUse;
                var _this = this;
                require([modelrequired], function (modelrequired) {
                    _this.model = new modelrequired();
                    _this.model.store();

                    _this.form = new Backbone.Form({
                        model: _this.model
                    }).render();

                    _this.$el.append(_this.form.el);
                    _this.binder = new Backbone.ModelBinder();
                    _this.binder.bind(_this.model, _this.el);
                });
            }
        });
        return FormGenerator;

    }
);
