define(['backbone', 'marionette', 'tabView'],
    function (Backbone, Marionette, TabView) {
        var TabsContainerView = Backbone.Marionette.CollectionView.extend({
            itemView: TabView,
            itemViewContainer: "div",
            className: "tab-content"
        });
        return TabsContainerView;
    }
);
