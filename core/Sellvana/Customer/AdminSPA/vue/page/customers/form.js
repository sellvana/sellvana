define(['vue', 'sv-mixin-form'], function (Vue, SvMixinForm) {

        return {
            mixins: [SvMixinForm],
            data: function () {
                return {
                    form: {
                        customer: {}
                    }
                }
            },
            methods: {
                updateBreadcrumbs: function (label) {
                    this.$store.commit('setData', {curPage: {
                        link: this.$router.currentRoute.fullPath,
                        label: label,
                        breadcrumbs: [
                            {nav:'/customers', label:'Customers', icon_class:'fa fa-user'},
                            {link:'/customers', label:'Customers'}
                        ]
                    }});
                },
                fetchData: function () {
                    var custId = this.$router.currentRoute.query.id, vm = this;
                    this.sendRequest('GET', 'customers/form_data', {id: custId}, function (response) {
                        vm.processFormDataResponse(response);
                        vm.updateBreadcrumbs(vm.form.customer.firstname + ' ' + vm.form.customer.lastname);
                    });
                },
                doDelete: function () {
                    if (!confirm(this._(('Are you sure you want to delete this customer?')))) {
                        return;
                    }
                    this.sendRequest('POST', 'customers/form_delete', {id: this.form.customer.id}, function (response) {
                        if (!response.ok) {

                        }
                    });
                },
                save: function (stayOnPage) {
                    var vm = this;
                    this.sendRequest('POST', 'customers/form_data', this.form.updates, function (response) {
                        if (!response._ok) {

                        }
                        for (var i in response.form) {
                            Vue.set(vm.form, i, response.form[i]);
                        }
                        if (!vm.form.updates) {
                            Vue.set(vm.form, 'updates', {});
                        }
                        if (!stayOnPage) {
                            vm.$router.push('/customers');
                        }
                    })
                }
            }
        };
    });