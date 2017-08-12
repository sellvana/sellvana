define(['vue'], function (Vue) {
    var Component = {
        props: ['grid', 'row', 'col'],
        template: '<td><select2 v-model="row[col.name]" :params="col.select2_params" @input="onInput">' +
            '<option v-for="o in col.options" :value="o.id">{{o.text}}</option></select2></td>',
        methods: {
            onInput: function (value) {
                console.log(value);
                if (!this.grid.edited_data) {
                    this.$set(this.grid, 'edited_data', {});
                }
                if (!this.grid.edited_data[this.row.id]) {
                    this.$set(this.grid.edited_data, this.row.id, {});
                }
                this.$set(this.grid.edited_data[this.row.id], this.col.name, value);
            }
        }
    };

    Vue.component('sv-comp-grid-data-edit-dropdown', Component);

    return Component;
});