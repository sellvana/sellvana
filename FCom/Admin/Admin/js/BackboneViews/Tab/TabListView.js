define(['backbone', 'marionette', 'tabListItemView'],
    function (Backbone, Marionette, TabListItemView) {
        var TabListView = Backbone.Marionette.CollectionView.extend({
            itemView: TabListItemView,
            itemViewContainer: "ul",
            tagName: "ul",
            id: "tabs",
            className: "nav nav-lists",
            attributes: {
                "data-tabs": "tabs"
            }
        });
        return TabListView;
    }
);
