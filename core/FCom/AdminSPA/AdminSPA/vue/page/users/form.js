define(['lodash', 'vue', 'sv-mixin-form'], function (_, Vue, SvMixinForm) {

    return {
        mixins: [SvMixinForm],
        computed: {
            avatarUrl: function () {
                return this.form && this.form.avatar ? this.form.avatar.thumb_url : '';
            }
        },
        methods: {
            updateBreadcrumbs: function () {
                var u = this.form.user;
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: this.form.config.title || this._(('Loading...')),
                    breadcrumbs: [
                        {nav:'/system', label: 'System', icon_class:'fa fa-cog'},
                        {link:'/users', label: 'Users'}
                    ]
                }});
            },
            fetchData: function () {
                var userId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'users/form_data', {id: userId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs();
                });
            },
            doDelete: function () {
                var vm = this;
                if (!confirm(this._(('Are you sure you want to delete this user?')))) {
                    return;
                }
                this.sendRequest('POST', 'users/form_delete', {id: this.form.user.id}, function (response) {
                    if (response.ok) {
                        vm.$router.push('/users');
                    }
                });
            },
            save: function (stayOnPage) {
                var vm = this;
                this.sendRequest('POST', 'users/form_data', {user: this.form.user}, function (response) {
                    for (var i in response.form) {
                        Vue.set(vm.form, i, response.form[i]);
                    }
                    if (response.ok && !stayOnPage) {
                        vm.$router.push('/users');
                    }
                })
            }
        }
    };
});