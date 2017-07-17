define(['sv-mixin-common'], function (SvMixinCommon, formTpl) {
    var SvPagePromoForm = {
        mixins: [SvMixinCommon],
        // template: formTpl
        methods: {
            updateBreadcrumbs: function (label) {
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: label,
                    breadcrumbs: [
                        {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'},
                        {link:'/promos', label:'Promotions'}
                    ]
                }});
            }
        },
        created: function () {
            this.updateBreadcrumbs('Stub Form');
        }
    };

    return SvPagePromoForm;
});