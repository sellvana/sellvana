define(['backbone', 'marionette', 'backbone.bootstrap-modal'],
    function (Backbone, Marionette) {
        var ModalLauncher = Backbone.Marionette.ItemView.extend({
            getTemplate: function () {
                return this.templateHelper;
            },
            initialize: function (options) {
                this.templateHelper = options.templateHelper;
                this.eventHelper = options.eventHelper;
                this.passedView = options.passedView;
                // dynamically build event key - the idea here is
                // to fire the modal using the #open element but it
                // can be other element passed to the view as argument
                //step two could be pass the event two (eg hover,keypress etc)
                this.events = this.events || {};
                var eventKey = 'click ' + this.eventHelper;
                this.events[eventKey] = 'openModal';
                this.delegateEvents();
            },
            events: {
                'click #open': 'openModal'
            },
            openModal: function () {
                _this = this;
                var modal = new Backbone.BootstrapModal({
                    content: _this.passedView,
                    title: 'modal header',
                    //animate: true
                });
                modal.open();
            },
        });
        return ModalLauncher;
    }
);

