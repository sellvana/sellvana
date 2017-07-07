define(['sv-hlp', 'vue-dropzone', 'text!sv-page-catalog-quick-add-products-tpl', 'json!sv-page-catalog-quick-add-products-config'],
    function (SvHlp, VueDropzone, addProductsTpl, addProductsConfig) {

    var Component = {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.formTab],
        template: addProductsTpl,
        data: function () {
            return {
                config: addProductsConfig,
                products: [{enabled: true}, {enabled: true}, {enabled: true}]
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
                this.sendRequest('POST', '/quickadd/products', {}, function (response) {
                    this.config = response.config;
                });
            },
            showUploadSuccess: function () {

            }
        }
    };

    return Component;
});