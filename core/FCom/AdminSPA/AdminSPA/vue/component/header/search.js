define(['text!sv-comp-header-search-tpl'], function (headerSearchTpl) {
    var SvCompHeaderSearch = {
        props: ['mobile'],
        template: headerSearchTpl,
        data: function () {
            return {
                query: '',
                results: []
            }
        },
        methods: {
            submitSearch: function () {
                var vm = this;
                this.sendRequest('GET', '/header/search', {q: this.query}, function (response) {
                    if (response.link) {
                        vm.$router.push(response.link);
                    }
                });
            }
        }
    };

    return SvCompHeaderSearch;
});