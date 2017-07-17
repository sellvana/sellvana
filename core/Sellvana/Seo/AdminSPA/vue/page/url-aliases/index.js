define(['sv-mixin-grid', 'sv-comp-grid', 'json!sv-page-seo-url-aliases-grid-config'], function (SvMixinGrid, SvCompGrid, gridConfig) {
    var Component = {
        mixins: [SvMixinGrid],
        data: function () {
            return {
                grid: {
                    config: gridConfig
                }
            }
        },
        components: {
            'sv-comp-grid': SvCompGrid
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