define(['backbone',  'jquery',  'select2'], function(Backbone, $) {
    var ipMode = {};
    ipMode.Views = Backbone.View.extend({
        template: _.template($('#ip-mode-template').html()),
        events: {
            'click .remove-ip-mode': 'remove'
        },
        render: function (mode) {
            this.setElement(this.template());
            this.$el.find('select').select2().select2('val', mode);
            return this;
        },
        remove: function () {
            this.$el.remove();
        }

    });
    return ipMode;
});
