define(['sv-hlp', 'text!sv-comp-header-search-tpl'], function (SvHlp, headerSearchTpl) {
    var SvCompHeaderSearch = {
        props: ['mobile'],
        mixins: [SvHlp.mixins.common],
        template: headerSearchTpl,
        data: function () {
            return {
                query: '',
                results: []
            }
        },
        methods: {
            submitSearch: function () {
                this.sendRequest('GET', '/header/search', {q: this.query}, function (response) {
                    if (response.link) {
                        SvHlp.router.push(response.link);
                    }
                });
            }
        }
    };

    return SvCompHeaderSearch;
});