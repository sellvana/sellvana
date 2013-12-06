define(['backbone', 'marionette'],
    function (Backbone, Marionette) {
        var FormView = Backbone.Marionette.ItemView.extend({
            template: "#form-template"
        });
        return FormView;
    }
);
