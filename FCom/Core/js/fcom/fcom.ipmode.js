

define(['backbone',  'jquery', 'unique', 'select2'], function(Backbone, $) {
    var ipMode = {};
    ipMode.Views = Backbone.View.extend({
        template: _.template($('#ip-mode-template').html()),
        events: {
            'click .remove-ip-mode': 'remove'
        },
        render: function (mode) {
            this.setElement(this.template({id: guid()}));
            var select = this.$el.find('select').select2();
            if (typeof (mode) !== 'undefined') {
                select.select2('val', mode);
            }
            return this;
        },
        remove: function () {
            this.$el.remove();
        }

    });
    var initMode = function initMode(mode, el) {
        var select = $(el).find('select').select2();
        mode.forEach(function (obj) {
            var tmp = obj.trim().split(':');
            if (tmp[0] != '') {
                if (typeof (tmp[1]) === 'undefined') {
                    select.select2('val', tmp[0].trim());
                } else {
                    var ip = new ipMode.Views();
                    $(el).append(ip.render(tmp[1]).el);
                    ip.$el.find('.text-ip-mode').val(tmp[0]);
                }
            }
        });
    }
    return {
        ipMode: ipMode,
        initMode: initMode
    };
})