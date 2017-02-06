define(['vue', 'vue-select'], function (Vue, VueSelect) {

    Vue.component('v-select', {
        props: {
            value: { default: null },
            options: { type: Array, default: [] },
            searchable: { type: Boolean, default: true },
            multiple: { type: Boolean, default: false },
            placeholder: { type: String, default: '' },
            transition: { type: String, default: 'expand' },
            clearSearchOnSelect: { type: Boolean, default: true },
            label: { type: String, default: 'label' },
            onChange: { type: Function, default: null }
        },
        components: {
            'vue-select': VueSelect.default
        },
        template: '<vue-select v-model="value_model" :options="options" :searchable="searchable" :multiple="multiple" ' +
        ':placeholder="placeholder" :transition="transition" :clearSearchOnSelect="clearSearchOnSelect" ' +
        ':label="label" onChange="onChange"></vue-select>',
        data: function () {
            return {
                value_model: null
            }
        },
        created: function () {

        },
        watch: {
            value: function (value) {
                this.value_model = value;
            }
        }
    });
});