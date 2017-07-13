define(['lodash', 'sv-hlp', 'vue-dropzone', 'text!sv-page-catalog-quick-add-products-tpl', 'json!sv-page-catalog-quick-add-products-config'],
    function (_, SvHlp, VueDropzone, addProductsTpl, addProductsConfig) {

    function uuidv4() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    var defaultProducts = [
        {uuid: uuidv4(), enabled: true},
        {uuid: uuidv4(), enabled: true},
        {uuid: uuidv4(), enabled: true}
    ];

    var Component = {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.formTab],
        template: addProductsTpl,
        data: function () {
            return {
                config: addProductsConfig,
                products: defaultProducts
            }
        },
        components: {
            dropzone: VueDropzone
        },
        methods: {
            addNewProducts: function (n) {
                for (var i = 0; i < n; i++) {
                    this.products.push({enabled: true});
                }
            },
            createProducts: function () {
                var vm = this, postData = {products: this.products};
                this.sendRequest('POST', '/quickadd/products', postData, function (response) {
                    if (response.ok) {
                        vm.products = defaultProducts;
                    }
                });
            },
            duplicateProduct: function (p) {
                this.products.push(_.cloneDeep(p));
            },
            removeProduct: function (pId) {
                this.products.splice(pId, 1);
            },
            dropzoneSending: function (file, xhr, formData) {
                console.log(file, xhr, formData);
            },
            dropzoneSuccess: function () {

            }
        }
    };

    return Component;
});