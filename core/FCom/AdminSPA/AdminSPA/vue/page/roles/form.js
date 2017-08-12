define(['lodash', 'vue', 'sv-mixin-form'], function (_, Vue, SvMixinForm) {

    return {
        mixins: [SvMixinForm],
        methods: {
            updateBreadcrumbs: function () {
                var r = this.form.role;
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: this.form.config.title || this._(('Loading...')),
                    breadcrumbs: [
                        {nav:'/system', label: 'System', icon_class:'fa fa-cog'},
                        {link:'/roles', label: 'Roles'}
                    ]
                }});
            },
            fetchData: function () {
                var roleId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'roles/form_data', {id: roleId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs();
                });
            },
            doDelete: function () {
                var vm = this;
                if (!confirm(this._(('Are you sure you want to delete this role?')))) {
                    return;
                }
                this.sendRequest('POST', 'roles/form_delete', {id: this.form.role.id}, function (response) {
                    if (response.ok) {
                        vm.$router.push('/roles');
                    }
                });
            },
            save: function (stayOnPage) {
                var vm = this;
                this.sendRequest('POST', 'roles/form_data', {role: this.form.role}, function (response) {
                    for (var i in response.form) {
                        vm.$set(vm.form, i, response.form[i]);
                    }
                    if (response.ok && !stayOnPage) {
                        vm.$router.push('/roles');
                    }
                    vm.$store.commit('actionInProgress', false);
                })
            }
        }
    };
});