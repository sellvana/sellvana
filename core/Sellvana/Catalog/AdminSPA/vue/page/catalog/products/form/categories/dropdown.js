define([], function () {
    return {
        props: ['grid', 'row', 'col'],
        // template: '<td><v-select v-model="row.category_id" :debounce="250" :onSearch="fetchOptions" :options="options" :placeholder="\'Choose a category...\'|_"></v-select></td>',
        //template: '<td><select2 v-model="row.category_id" :debounce="250" :onSearch="fetchOptions" :options="options" :placeholder="\'Choose a category...\'|_"></select2></td>',
        template: '<td class="datacell-products-category_id" style="width:400px">' +
            '<v-multiselect :id="\'category_id-\' + row.id" :internal-search="true" :options="options"' +
            ' :loading="loading" label="text" :placeholder="\'Start Typing to Select a Category...\'|_" :clear-on-select="false"' +
            ' :close-on-select="true" :options-limit="200" :limit="3" :limit-text="searchLimitText"' +
            ' :show-labels="false" @search-change="searchChange" @input="searchSelect"></v-multiselect></td>',
        data: function () {
            return {
                options: [],
                loading: false
            }
        },
        methods: {
            searchLimitText: function (count) {
                return this._((('and {count} other results')), {count:count});
            },
            searchChange: function (search) {
                console.log('search', search);
                var vm = this;
                this.loading = true;
                this.$http.get('products/form/categories/options?q=' + search).then(function (response) {
                    console.log(response);
                    vm.options = response.data.options;
                    vm.loading = false;
                });
            },
            searchSelect: function (category) {
                this.$set(this.row, 'category_id', category.id);
            }
        }
    }
})