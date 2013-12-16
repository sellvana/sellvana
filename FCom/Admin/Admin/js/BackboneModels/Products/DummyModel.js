define(['backbone', 'marionette', 'memento'],
    function (Bakcbone, Marionnete, Memento) {
        var DummyModel = Backbone.Model.extend({
            //url: "yourbackend",
            initialize: function () {
                var memento = new Backbone.Memento(this);
                _.extend(this, memento);
            },
            defaults: {
                title: "Mrs",
                name: "",
                email: "",
                birthday: "",
                password: "",
            },
            schema: {
                title: { type: 'Select', options: ['Mr', 'Mrs', 'Ms'] },
                name: 'Text',
                email: { validators: ['required', 'email'] },
                birthday: 'Date',
                password: 'Password'
            }
        });
        return DummyModel;
    }
);
