define(['sv-app', 'sv-comp-form'], function (SvApp, SvCompForm) {
    return {
        store: SvApp.store,
        data: function () {
            return {
                form: {
                    data: {
                        customer: {
                            email: 'test@sellvana.com'
                        }
                    }
                }
            };
        },
        components: {
            'sv-comp-form': SvCompForm
        },
        mounted: function () {
            var curRoute = this.$router.currentRoute;
            SvApp.methods.sendRequest('GET', 'users/form_data', curRoute.query, function (response) {

            })
            this.$store.commit('setData', {curPage: {
                link: curRoute.fullPath,
                label: 'Edit User ' + this.form.data.customer.email,
                breadcrumbs: [
                    {nav:'/system', label: 'System', icon_class:'fa fa-cog'},
                    {link:'/users', label: 'Users'}
                ]
            }});
        }
    };
});