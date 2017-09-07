define(['text!sv-page-catalog-products-form-customfields-tpl'], function (tpl) {
    var SvPageCatalogProductsFormCustomFields = {
        template: tpl,
        data: function () {
            return {
                available_fieldsets: [],
                add_fieldset: '',
                add_field: '',
                fieldsets: [
                    {
                        label: 'Default Fieldset',
                        product: this.form.product,
                        config: {
                            removable: true,
                            draggable: true,
                            fields: [
                                {
                                    model: 'product',
                                    name: 'color',
                                    type: 'select2',
                                    label: 'Color'
                                },
                                {
                                    model: 'product',
                                    name: 'size',
                                    type: 'select2',
                                    label: 'Size'
                                }
                            ]
                        }
                    }
                ]
            }
        },
        computed: {

        },
        methods: {
            addFieldset: function () {

            },
            addField: function (fs) {

            },
            availableFields: function (fs) {

            },
            openCreateFieldset: function () {

            },
            removeFieldset: function (fs) {

            },
            toggleFieldset: function (fs) {

            },
            openCreateField: function (fs) {

            },
            onFieldEvent: function (ev) {

            }
        }
    };

    return SvPageCatalogProductsFormCustomFields;
});