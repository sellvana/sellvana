define(['vue', 'sv-hlp'], function (Vue, SvHlp) {
    var InlineParam = {
        template1: '<span class="param-result" :class="{edit: edit_mode}">' +
            '<a v-if="!options" href="#" @click.prevent="toggleEdit">{{valueLabel|_}}</a>' +
            '<span class="param-options">' +
                '<select2 v-if="options" v-model="value_model" :params="select2"><option v-for="o in options" :value="o.id">{{o.text|_}}</option></select2>' +
                '<input v-if="!options" ref="input" :type="inputType" :value="value_model" @input="value_model=$event.target.value" ' +
                    '@blur="exitEdit" @keyup.enter="exitEdit" @keyup.esc="exitEdit">' +
            '</span>' +
        '</span>',

        template: '<span class="param-result" :class="editModeClass">' +
            '<a href="#" @click.prevent="toggleEdit">{{valueLabel|_}}</a>' +
            '<span class="param-options" :class="{list:options}">' +
                '<ul v-if="options"><li v-for="o in options"><a href="#" @click.prevent="selectOption(o)">{{o.text|_}}</a></li></ul>' +
                '<input v-else ref="input" :type="inputType" :value="value_model" @input="value_model=$event.target.value" ' +
                    '@blur="exitEdit" @keyup.enter="exitEdit" @keyup.esc="exitEdit">' +
            '</span>' +
        '</span>',

        mixins: [SvHlp.mixins.common],

        props: ['value', 'options', 'params', 'select2'],
        data: function () {
            return {
                value_model: '',
                edit_mode: false
            }
        },
        mounted: function () {
            this.value_model = this.value;
        },
        computed: {
            valueLabel: function () {
                if (this.options) {
                    var i, l, o;
                    for (i = 0, l = this.options.length; i < l; i++) {
                        o = this.options[i];
                        if (o.id === this.value_model) {
                            return typeof o.label !== 'undefined' ? o.label : o.text;
                        }
                    }
                }
                return this.value_model || '...';
            },
            inputType: function () {
                return this.params && this.params.type || 'text';
            },
            editModeClass: function () {
                return this.options ? {open: this.edit_mode} : {edit: this.edit_mode};
            }
        },
        methods: {
            toggleEdit: function () {
                this.edit_mode = !this.edit_mode;
                if (this.edit_mode && !this.options) {
                    this.$nextTick(function () {
                        this.$refs.input.focus();
                    });
                }
            },
            exitEdit: function () {
                this.edit_mode = false;
            },
            selectOption: function (o) {
                this.value_model = o.id;
                this.exitEdit();
            }
        },
        watch: {
            value: function (value) {
                this.value_model = value;
            },
            value_model: function (value_model) {
                this.$emit('input', value_model);
            }
        }
    };

    Vue.component('inline-param', InlineParam);

    return InlineParam;
});
