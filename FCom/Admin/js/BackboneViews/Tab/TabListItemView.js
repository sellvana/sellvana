define(['backbone', 'marionette'],
    function (Backbone, Marionette) {
        var TabListItemView = Backbone.Marionette.ItemView.extend({
            tagName: "li",
            template: "#tab-template"
        });
        return TabListItemView;
    }
);
