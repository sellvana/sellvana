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
                        {link:'/mailing/campaigns', label: 'Campaigns'}
                    ]
                }});
            },
            fetchData: function () {
                var campaignId = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', 'mailing/campaigns/form_data', {id: campaignId}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs();
                });
            },
            doDelete: function () {
                var vm = this;
                if (!confirm(this._(('Are you sure you want to delete this campaign?')))) {
                    return;
                }
                this.sendRequest('POST', 'mailing/campaigns/form_delete', {id: this.form.campaign.id}, function (response) {
                    if (response.ok) {
                        vm.$router.push('/mailing/campaigns');
                    }
                });
            },
            save: function (stayOnPage) {
                var vm = this;
                this.sendRequest('POST', 'mailing/campaigns/form_data', {campaign: this.form.campaign}, function (response) {
                    for (var i in response.form) {
                        vm.$set(vm.form, i, response.form[i]);
                    }
                    if (response.ok && !stayOnPage) {
                        vm.$router.push('/mailing/campaigns');
                    }
                })
            }
        }
    };
});