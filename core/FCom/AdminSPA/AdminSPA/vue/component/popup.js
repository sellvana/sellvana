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
                return options;
            }
        },
        methods: {
            closePopup: function () {
                this.$emit('event', 'close');
            },
            processFieldEvent: function (type, args) {
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
                    af.removable = true;
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