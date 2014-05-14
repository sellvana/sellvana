define(['backbone',  'jquery', 'unique', 'select2'], function(Backbone, $) {
    var ipMode = {};
    ipMode.Views = Backbone.View.extend({
        template: _.template($('#ip-mode-template').html()),
        events: {
            'click .remove-ip-mode': 'remove'
        },
        render: function (mode, name) {
            var data = {};
            if (typeof (name) !== 'undefined') {
                data = {
                    name: name,
                    default_mode: true,
                    class_button: 'add-ip-mode btn-info'
                };
            } else {
                data = {
                    name: guid(),
                    class_button: 'remove-ip-mode btn-danger'
                };
            }
	    console.log(data);
	    this.setElement(this.template(data));            
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
    var initMode = function initMode(data) {
        if (data.mode[0] == '') {
            var ip = new ipMode.Views();
            data.el.append(ip.render(data.mode[0], data.name).el);
            data.el.find('select').prepend('<option value="" selected></option>');
        }
        data.mode.forEach(function (obj) {
            var tmp = obj.trim().split(':');
            if (tmp[0] != '') {
                var ip = new ipMode.Views();
                if (typeof (tmp[1]) === 'undefined') {
                    data.el.append(ip.render(tmp[0], data.name).el);
                } else {
                    data.el.append(ip.render(tmp[1]).el);
                    ip.$el.find('.text-ip-mode').val(tmp[0]);
                }
            }
        });
    };
    return {
        ipMode: ipMode,
        initMode: initMode
    };
});