define(['sv-comp-form-media', 'text!sv-page-catalog-products-form-images-tpl'], function (SvCompFormMedia, tabTpl) {
    return {
        template: tabTpl,
        props: ['form']
    }
});