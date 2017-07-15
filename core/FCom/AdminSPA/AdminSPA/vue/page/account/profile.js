define(['lodash', 'vue', 'sv-mixin-form-tab', 'json!sv-page-account-profile-config'], function (_, Vue, SvMixinFormTab, profileFormConfig) {
    return {
        mixins: [SvMixinFormTab],
        data: function () {
            var user = _.extend({change_password: false, new_password: '', confirm_password: ''}, this.$store.state.user);
            return {
                form: {
                    user: user,
                    config: profileFormConfig
                }
            }
        },
        methods: {
            updateBreadcrumbs: function () {
                this.$store.commit('setData', {curPage: {
                    link: '/profile',
                    label: 'Account Profile',
                    breadcrumbs: [
                    ]
                }});
            }
        },
        created: function () {
            this.updateBreadcrumbs();
        }
    };
});