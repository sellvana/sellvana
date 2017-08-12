define(['lodash', 'vue', 'sv-mixin-form'], function (_, Vue, SvMixinForm) {

    return {
        mixins: [SvMixinForm],
        methods: {
            updateBreadcrumbs: function () {
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: this.form.config.title || this._(('Loading...')),
                    breadcrumbs: [
                        {nav:'/mailing', label: 'Mailing'},
                        {link:'/mailing/lists', label: 'Lists'}
                    ]
                }});
            },
            fetchData: function () {
                var listId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'mailing/lists/form_data', {id: listId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs();
                });
            },
            doDelete: function () {
                var vm = this;
                if (!confirm(this._(('Are you sure you want to delete this list?')))) {
                    return;
                }
                this.sendRequest('POST', 'mailing/lists/form_delete', {id: this.form.list.id}, function (response) {
                    if (response.ok) {
                        vm.$router.push('/mailing/lists');
                    }
                });
            },
            save: function (stayOnPage) {
                var vm = this;
                this.sendRequest('POST', 'mailing/lists/form_data', {list: this.form.list}, function (response) {
                    for (var i in response.form) {
                        vm.$set(vm.form, i, response.form[i]);
                    }
                    if (response.ok && !stayOnPage) {
                        vm.$router.push('/mailing/lists');
                    }
                })
            }
        }
    };
});