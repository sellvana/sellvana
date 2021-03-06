define(['lodash', 'vue', 'text!sv-page-default-form-tpl'], function (_, Vue, svPageDefaultFormTpl) {
    var formMixin = {
        template: svPageDefaultFormTpl,
        data: function () {
            return {
                tab: false,
                form: {
                    config: {
                        options: {},
                        tabs: [],
                        fields: []
                    },
                    updates: {},
                    errors: {}
                }
            };
        },
        computed: {
            formTitle: function () {
                return this.form && this.form.config && this.form.config.title || '';
            },
            thumbUrl: function () {
                return this.form && this.form.config && this.form.config.thumb_url || '';
            },
            formTabs: function () {
                return this.form && this.form.config && this.form.config.tabs || [];
            },

            formTabLabel: function () {
                if (!this.form || !this.form.config || !this.form.config.tabs || _.isEmpty(this.form.config.tabs)) {
                    return '';
                }
                for (var i = 0, l = this.form.config.tabs.length; i < l; i++) {
                    if (this.form.config.tabs[i].name === this.tab) {
                        return this.form.config.tabs[i].label;
                    }
                }
                return '';
            },
            pageActionsGroups: function () {
                return this.form.config.page_actions_groups || {};
            }
        },
        methods: {
            getOption: function (type, value) {
                if (!this.form.options[type]) {
                    return {};
                }
                if (!value) {
                    return this.form.options[type];
                }
                return this.form.options[type][value];
            },

            doFormAction: function (act) {
                if (act.method) {
                    this[act.method]();
                    return;
                }
                switch (act.name) {
                    case 'save': this.save(); break;
                    case 'save-continue': this.saveAndContinue(); break;
                    case 'delete': this.doDelete(); break;
                    case 'back': this.goBack(); break;
                    default: console.log('Invalid action: ', act);
                }
            },

            saveAndContinue: function () {
                this.save(true);
            },

            switchTab: function (tab) { // tab: object
                if (false && _.isString(tab)) {
                    for (var i = 0, l = this.form.config.tabs.length, t; t=this.form.config.tabs[i], i < l; i++) {
                        if (t.name === tab) {
                            tab = t;
                            break;
                        }
                    }
                }
                this.tab = tab;
                //this.$router.go({query: {tab: tab}});
            },

            goBack: function () {
                this.$router.go(-1);
            },

            addUpdates: function (part) {
                this.$set(this.form, 'updates', _.extend({}, this.form.updates, part));
            },

            processFormDataResponse: function (response) {
                if (!response.form) {
                    console.log('No form object in response', response);
                    return;
                }
                if (!response.form.updates || _.isArrayLike(response.form.updates)) {
                    response.form.updates = {};
                }
                if (!response.form.errors || _.isArrayLike(response.form.errors)) {
                    response.form.errors = {};
                }

                var deps = [], i, l, f, watchModels = {}, vm = this;

                for (i = 0, l = response.form.config.tabs.length; i < l; i++) {
                    deps.push(response.form.config.tabs[i].component);
                }
                require(deps, function () {
                    var tabs = response.form.config.tabs;
                    for (i = 0, l = response.form.config.tabs.length; i < l; i++) {
                        vm.$set(vm.form.config.tabs[i], 'component_config', arguments[i]);
                    }
                    if (!this.tab) {
                        vm.switchTab(response.form.config.tabs[0]);
                    }
                });
                this.$set(this, 'form', response.form);

                if (response.form.config.fields) {
                    for (i = 0, l = response.form.config.fields.length; i < l; i++) {
                        f = response.form.config.fields[i];
                        watchModels[f.model] = true;
                    }
                }

                _.forEach(watchModels, function (flag, model) {
                    vm.$set(vm.form, model + '_old', _.cloneDeep(response.form[model]));
                    vm.$watch('form.' + model, function (n, o) {
                        vm.processModelDiff(n, o, model);
                        vm.validateForm();
                    }, {deep: true});
                });
            },
            onEvent: function (type, args) {
                if (args && args.link) {
                    window.location = '#' + args.link;
                    return;
                }

                switch (type) {
                    case 'tab-switch': //TODO: F&R: $emit('tab'
                        this.switchTab(args);
                        break;

                    case 'tab-edited':
                        for (var i = 0, l = this.form.config.tabs.length; i < l; i++) {
                            if (this.form.config.tabs[i].name === args.name) {
                                this.form.config.tabs[i].edited = true;
                            }
                        }
                        break;

                    case 'page-action':
                    case 'form-action':
                        this.doFormAction(args);
                        break;

                    default:
                        console.log(type, args);
                        this.emitEvent(type, args);
                }
            },

            validateField: function (f, apply) {
                var i, l, r, v, a, e;
                if (_.isString(f)) {
                    for (i = 0, l = this.form.config.fields.length; i < l; i++) {
                        if (this.form.config.fields[i].name === f) {
                            f = this.form.config.fields[i];
                            apply = true;
                            break;
                        }
                    }
                    if (!apply) { // field not found
                        console.log('Field not found: ' + f);
                        return null;
                    }
                }
                r = f.validate;
                if (!r && !f.required) {
                    return {};
                }
                v = this.form[f.model][f.name];
                a = {field: f.label};
                e = {};

                if (v === null || v === '') {
                    if (f.required) {
                        e.required = f.required_message || this._((('Field is required: {field}')), a);
                    }
                } else if (r) {
                    var regexp = new RegExp(r.pattern.replace(/^\/|\/$/g, ''));
                    if (r.pattern && v && !v.match(regexp)) {
                        e.pattern = r.pattern_message || this._((('Invalid field value: {field}')), a);
                    }
                    if ((r.email || f.input_type === 'email') && !v.match(/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/)) {
                        e.email = r.email_message || this._((('Invalid email: {field}')), a);
                    }
                    if ((r.url || f.input_type === 'url') && !v.match('/^(https?|ftp):\/\/(-\.)?([^\s/?\.#-]+\.?)+(\/[^\s]*)?$/iS')) {
                        e.url = r.url_message || this._((('Invalid URL: {field}')), a);
                    }
                }

                if (apply) {
                    var hasErrors = !_.isEmpty(e);
                    if (hasErrors) {
                        var errors = {};
                        errors[f.model] = {};
                        errors[f.model][f.name] = e;
                        if (this.form.errors) {
                            errors = _.extend({}, this.form.errors, errors);
                        }
                        this.$set(this.form, 'errors', errors);
                    } else if (_.get(this.form, 'errors[' + f.model + '][' + f.name + ']')) {
                        Vue.delete(this.form.errors[f.model], f.name);
                    }
                    for (i = 0, l = this.form.config.tabs.length; i < l; i++) {
                        if (this.form.config.tabs[i].name === f.tab) {
                            this.form.config.tabs[i].errors = hasErrors;
                            break;
                        }
                    }
                }
                return e;
            },

            validateForm: function () {
                var i, l, f, e, tabErrors = {}, errors = {}, otherErrors = false;

                for (i = 0, l = this.form.config.fields.length; i < l; i++) {
                    f = this.form.config.fields[i];
                    e = this.validateField(f);
                    if (!_.isEmpty(e)) {
                        if (!errors[f.model]) {
                            errors[f.model] = {};
                        }
                        errors[f.model][f.name] = e;
                        tabErrors[f.tab] = true;
                    } else if (!tabErrors[f.tab]) {
                        tabErrors[f.tab] = false;
                    }
                }

                this.$set(this.form, 'errors', errors);
                for (i = 0, l = this.form.config.tabs.length; i < l; i++) {
                    e = tabErrors[this.form.config.tabs[i].name];
                    if (e === true || e === false) {
                        this.$set(this.form.config.tabs[i], 'errors', e);
                    }
                    if (this.form.config.tabs[i].errors) {
                        otherErrors = true;
                    }
                }
                return _.isEmpty(errors) && !otherErrors;
            },

            clearTabsFlags: function () {
                for (i = 0, l = this.form.config.tabs.length; i < l; i++) {
                    this.$set(this.form.config.tabs[i], 'edited', false);
                    this.$set(this.form.config.tabs[i], 'errors', false);
                }
            },
            processModelDiff: function (newModel, oldModel, model) {
                // have to do all this because oldModel isn't working
                if (model) {
                    newModel = this.form[model];
                    oldModel = this.form[model + '_old'];
                }
                var i, l, j, m = this.form.config.fields.length, f, tabs = {}, update = false;
                for (i in newModel) {
                    if (newModel[i] != oldModel[i]) {
                        f = false;
                        for (j = 0; j < m; j++) {
                            if (this.form.config.fields[j].name === i) {
                                f = this.form.config.fields[j];
                                break;
                            }
                        }
                        if (!f) {
                            continue;
                        }
                        tabs[f.tab] = true;
                        update = true;
                    }
                }

                if (update) {
                    for (i = 0, l = this.form.config.tabs.length; i < l; i++) {
                        if (tabs[this.form.config.tabs[i].name]) {
                            this.$set(this.form.config.tabs[i], 'edited', true);
                        }
                    }
                }
            },
            updateBreadcrumbs: function () {
                this.$store.commit('setData', {curPage: {
                    link: this.$router.currentRoute.fullPath,
                    label: this.form.config.title || (('Loading...')),
                    breadcrumbs: this.form.config.breadcrumbs
                }});
            },
            fetchData: function () {
                var id = this.$router.currentRoute.query.id, vm = this;
                this.sendRequest('GET', this.form_data_url, {id: id}, function (response) {
                    vm.processFormDataResponse(response);
                    vm.updateBreadcrumbs();
                });
            },
            getPostDataForSave: function () {
                var model = this.form.config.main_model, postData = {};
                postData[model] = this.form[model];
                return postData;
            },
            save: function (stayOnPage) {
                var vm = this;
                this.$store.commit('actionInProgress', stayOnPage ? 'save-continue' : 'save');
                if (!this.validateForm()) {
                    this.$store.commit('actionInProgress', false);
                    alert(this._(('Please correct form errors before submitting.')));
                    return;
                }
                this.sendRequest('POST', this.form_data_url, this.getPostDataForSave(), function (response) {
                    if (response.form) {
                        vm.processFormDataResponse(response);
                        vm.updateBreadcrumbs();
                    }
                    if (!response.error && !stayOnPage) {
                        vm.$router.push(vm.form.config.index_url);
                    }
                    vm.$store.commit('actionInProgress', false);
                });
            },
            doDelete: function () {
                var vm = this;
                if (!confirm(this._(('Are you sure you want to delete this record?')))) {
                    return;
                }
                this.sendRequest('DELETE', this.form_data_url, {id: this.form[this.form.config.main_model].id}, function (response) {
                    if (response.ok) {
                        vm.$router.push(vm.form.config.index_url);
                    }
                });
            }
        },
        watch: {
            '$route': 'fetchData'
        },
        created: function () {
            if (this.updateBreadcrumbs) {
                this.updateBreadcrumbs(this._(('Loading data...')));
            }
            this.fetchData(this.$route);
        },
        beforeRouteLeave: function (to, from, next) {
            // TODO: doesn't trigger on route args change (?id=5)
            if (this.form.updates && Object.keys(this.form.updates).length > 1) {
                if (!confirm(this._(('There are unsaved changes, are you sure you want to leave?')))) {
                    console.log(this.form.updates);
                    next(false);
                } else {
                    next();
                }
            } else {
                next();
            }
        }
    };

    return formMixin;
});