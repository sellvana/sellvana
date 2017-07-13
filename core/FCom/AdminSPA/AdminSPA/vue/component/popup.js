define(['vue', 'sv-hlp', 'text!sv-comp-popup-tpl'], function (Vue, SvHlp, tpl) {

    var Component = {
        template: tpl,
        mixins: [SvHlp.mixins.common, SvHlp.mixins.formTab],
        props: ['popup'],
        data: function () {
            return {
                add_fields: [],
                visible_fields: []
            }
        },
        computed: {
            form: function () {
                return this.popup.form;
            },
            add_fields_options: function () {
                var i, l, f, j, m, af, skip, options = [];
                if (this.form) {
                    for (i = 0, l = this.form.config.fields.length; i < l; i++) {
                        f = this.form.config.fields[i];
                        skip = false;
                        for (j = 0, m = this.visible_fields.length; j < m; j++) {
                            af = this.visible_fields[j];
                            if (f.name === af.name) {
                                skip = true;
                                break;
                            }
                        }
                        if (!skip) {
                            options.push(f);
                        }
                    }
                }
                return options;
            }
        },
        methods: {
            closePopup: function () {
                this.$emit('event', 'popup-action', {name: 'cancel'});
            },
            processFieldEvent: function (type, args) {
                var i, l, f;
                switch (type) {
                    case 'remove_field':
                        for (i = 0, l = this.visible_fields.length; i < l; i++) {
                            f = this.visible_fields[i];
                            if (f.name === args.name) {
                                this.visible_fields.splice(i, 1);
                            }
                        }
                        break;
                }
                this.$emit('event', type, args); // field-event?
            },
            processComponentEvent: function (type, args) {
                this.$emit('event', type, args);
            },
            doAction: function (act) {
                this.$emit('event', 'popup-action', act);
            },
            addFields: function () {
                var i, l, af, j, m, f;
                for (i = 0, l = this.add_fields.length; i < l; i++) {
                    af = this.add_fields[i];
                    for (j = 0, m = this.form.config.fields.length; j < m; j++) {
                        f = this.form.config.fields[j];
                        if (f.name === af) {
                            this.visible_fields.push(f);
                        }
                    }
                }
                this.add_fields = [];
            }
        },
        created: function () {
            var i, l, f;
            if (this.form) {
                for (i = 0, l = this.form.config.fields.length; i < l; i++) {
                    f = this.form.config.fields[i];
                    f.removable = true;
                    if (f.visible) {
                        this.visible_fields.push(f);
                    }
                }
            }
        },
        mounted: function () {
            this.$store.commit('overlay', true);
        },
        destroyed: function () {
            this.$store.commit('overlay', false);
        },
        watch: {
            '$store.state.ui.overlayActive': function (active) {
                if (!active) {
                    this.closePopup();
                }
            }
        }
    };

    Vue.component('sv-comp-popup', Component);

    return Component;
});