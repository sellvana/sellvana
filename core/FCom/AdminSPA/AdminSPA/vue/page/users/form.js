define(['vue', 'sv-app', 'sv-comp-form', 'text!sv-page-users-form-main-tpl'], function (Vue, SvApp, SvCompForm, tabMainTpl) {

    var defForm = {
        options: {},
        updates: {},
        tabs: [],

        user: {}
    };

    var TabMain = {
        props: {
            form: {
                default: defForm
            }
        },
        template: tabMainTpl
    };

    return {
        mixins: [SvApp.mixins.common, SvApp.mixins.form],
        data: function () {
            return {
                form: defForm
            };
        },
        computed: {
            avatarUrl: function () {
                return this.form && this.form.avatar ? this.form.avatar.thumb_url : '';
            }
        },
        methods: {
            updateBreadcrumbs: function (label) {
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: 'Edit User ' + this.form.user.email,
                    breadcrumbs: [
                        {nav:'/system', label: 'System', icon_class:'fa fa-cog'},
                        {link:'/users', label: 'Users'}
                    ]
                }});
            },
            fetchData: function () {
                var userId = this.$router.currentRoute.query.id, vm = this;
                SvApp.methods.sendRequest('GET', 'users/form_data', {id: userId}, function (response) {
                    vm.form = response.form;
                    if (!vm.form.updates) {
                        Vue.set(vm.form, 'updates', {});
                    }
                    vm.updateBreadcrumbs(SvApp._(vm.form.user.email));
                });
            },
            save: function (stayOnPage) {
                var vm = this;
                SvApp.methods.sendRequest('POST', 'orders/form_data', this.form.updates, function (response) {
                    if (!response._ok) {

                    }
                    for (var i in response.form) {
                        Vue.set(vm.form, i, response.form[i]);
                    }
                    if (!vm.form.updates) {
                        Vue.set(vm.form, 'updates', {});
                    }
                    if (!stayOnPage) {
                        vm.$router.go(-1);
                    }
                })
            }
        },
        components: {
            'sv-page-users-form-main': TabMain
        }
    };
});