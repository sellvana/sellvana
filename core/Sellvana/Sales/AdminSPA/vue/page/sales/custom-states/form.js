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
                        {nav:'/sales', label: 'Sales', icon_class:'fa fa-cog'},
                    ]
                }});
            },
            fetchData: function () {
                var stateId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'custom_states/form_data', {id: stateId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs();
                });
            },
            doDelete: function () {
                var vm = this;
                if (!confirm(this._(('Are you sure you want to delete this custom state?')))) {
                    return;
                }
                this.sendRequest('POST', 'custom_states/form_delete', {id: this.form.state_custom.id}, function (response) {
                    if (response.ok) {
                        vm.$router.push('/sales/custom-states');
                    }
                });
            },
            save: function (stayOnPage) {
                var vm = this;
                this.sendRequest('POST', 'custom_states/form_data', {state_custom: this.form.state_custom}, function (response) {
                    for (var i in response.form) {
                        vm.$set(vm.form, i, response.form[i]);
                    }
                    if (response.ok && !stayOnPage) {
                        vm.$router.push('/sales/custom-states');
                    }
                })
            }
        }
    };
});