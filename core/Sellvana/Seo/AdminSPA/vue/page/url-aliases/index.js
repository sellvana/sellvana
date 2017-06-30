define(['sv-hlp', 'sv-comp-grid', 'json!sv-page-seo-url-aliases-grid-config'], function (SvHlp, SvCompGrid, gridConfig) {
    var Component = {
        mixins: [SvHlp.mixins.common, SvHlp.mixins.grid],
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