define(['backbone','marionette','tabView'],
    function(Backbone,Marionette,TabView)function(){
        var TabsContainerView = Backbone.Marionette.CollectionView.extend({
            itemView: TabView,
            itemViewContainer: "div",
            className : "tab-content"
        });
        return TabsContainerView;
});
