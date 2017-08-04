define(['sv-mixin-grid', 'json!sv-page-seo-url-aliases-grid-config'], function (SvMixinGrid, gridConfig) {
    var Component = {
        mixins: [SvMixinGrid],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            }
        },
        created: function () {
            this.$store.commit('setData', {curPage: {
                link: '/seo/url-aliases',
                label: 'URL Aliases',
                breadcrumbs: [
                    {nav:'/seo', label:'SEO'}
                ]
            }});
        }
    };

    return Component;
});