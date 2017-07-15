define(['sv-mixin-common'], function (SvMixinCommon) {
    return {
        mixins: [SvMixinCommon],
        mounted: function () {
            this.$store.commit('setData', {curPage: {
                link: '/not-found',
                label: 'Page Not Found',
                breadcrumbs: [
                ]
            }});
        }
    };
});