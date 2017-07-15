define(['vue', 'sv-mixin-form'], function (Vue, SvMixinForm) {

        var defForm = {
            options: {},
            updates: {},
            tabs: [],
            errors: {},

            vendor: {}
        };

        return {
            mixins: [SvMixinForm],
            data: function () {
                return {
                    form: defForm
                }
            },
            methods: {
                doFormAction: function (act) {
                    console.log(act);
                },
                updateBreadcrumbs: function (label) {
                    this.$store.commit('setData', {curPage: {
                        link: this.$router.currentRoute.fullPath,
                        label: label,
                        breadcrumbs: [
                            {nav:'/catalog', label:'Catalog', icon_class:'fa fa-book'},
                            {link:'/catalog/vendors', label:'Vendors'}
                        ]
                    }});
                },
                fetchData: function () {
                    var custId = this.$router.currentRoute.query.id, vm = this;
                    this.sendRequest('GET', 'vendors/form_data', {id: custId}, function (response) {
                        vm.processFormDataResponse(response);
                        vm.updateBreadcrumbs(vm.form.vendor.name);
                    });
                },
                doDelete: function () {
                    if (!confirm(this._(('Are you sure you want to delete this vendor?')))) {
                        return;
                    }
                    this.sendRequest('POST', 'vendors/form_delete', {id: this.form.vendor.id}, function (response) {
                        if (!response.ok) {

                        }
                    });
                },
                save: function (stayOnPage) {
                    var vm = this;
                    this.sendRequest('POST', 'vendors/form_data', this.form.updates, function (response) {
                        if (!response._ok) {

                        }
                        for (var i in response.form) {
                            Vue.set(vm.form, i, response.form[i]);
                        }
                        if (!vm.form.updates) {
                            Vue.set(vm.form, 'updates', {});
                        }
                        if (!stayOnPage) {
                            vm.$router.push('/vendors');
                        }
                    })
                }
            },
            watch: {
                'form.vendor': function (vendor) {

                }
            }
        };
    });