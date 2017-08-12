define(['jquery', 'lodash', 'vue', 'vue-resource', 'sv-mixin-common', 'accounting', 'moment', 'sortablejs',
    'vue-ckeditor', 'vue-select', 'vue-multiselect', 'vue-select2', 'spin', 'ladda', 'nprogress', 'perfect-scrollbar',
    'vue-password-strength-meter', 'sv-comp-form-field'
],
function ($, _, Vue, VueResource, SvMixinCommon, Accounting, Moment, Sortable,
          VueCkeditor, VueSelect, VueMultiselect, VueSelect2, Spin, Ladda, NProgress, PerfectScrollbar,
          VuePassword, SvCompFormField
) {

    Vue.use(VueResource);
    Vue.mixin(SvMixinCommon);

    // String extensions

    String.prototype.supplant = function (o) {
        return this.replace(/\{([^{}]*)\}/g, function (a, b) {
            var r = o[b];
//console.log(a, b, o, o[b]);
            return typeof r === 'string' || typeof r === 'number' ? r : a;
        });
    };

    String.prototype._ = function (args) { return SvMixinCommon.methods._(this, args); };

    // Directives

    Vue.directive('sortable', {
        inserted: function(el, binding) {
            $(el).data('sortable-binding', binding);
            el.sortableInstance = Sortable.create(el, binding.value);
        },
        unbind: function (el) {
            el.sortableInstance.destroy();
        }
    });

    Vue.directive('scrollbar', {
        bind: function (el) {
            PerfectScrollbar.initialize(el);
            $(el).on('resize', function () {
                PerfectScrollbar.update(el);
            });
        },
        unbind: function (el) {
            PerfectScrollbar.destroy(el);
        }
    });

    Vue.directive('ladda', {
        bind: function (el, binding) {
            var $el = $(el);
            $el.addClass('ladda-button').wrapInner('<span class="ladda-label"></span>');
            if (!$el.attr('data-style')) {
                $el.attr('data-style', binding.value.style || 'expand-left');
            }
            if (!$el.attr('data-spinner-size')) {
                $el.attr('data-spinner-size', binding.value.spinner_size || 20);
            }

            el.ladda = Ladda.create(el);
        },
        update: function (el, binding) {
            if (binding.value.on && !binding.oldValue.on) {
                el.ladda.start();
            }  else if (!binding.value.on && binding.oldValue.on) {
                el.ladda.stop();
            }
            if (_.isNumber(binding.value.progress)) {
                el.ladda.setProgress(binding.value.progress);
            }
        }
    });

    // Components

    Vue.component('v-select', VueSelect.default);
    Vue.component('v-multiselect', VueMultiselect.default);
    Vue.component('vue-password', VuePassword.default);

    Vue.component('jsontree', {
        template: '<div></div>',
        props: ['json'],
        mounted: function () {
            $(this.$el).html(JSONTree.create(this.json));
        },
        watch: {
            json: function (json) {
                $(this.$el).html(JSONTree.create(this.json));
            }
        }
    });

    Vue.component('checkbox', {
        template: '<label><input type="checkbox" :id="id" v-model="internal" class="f-input-checkbox"><div class="f-checkbox-block" :style="blockStyle">' +
        '<div class="f-checkbox-block__elem" :style="elemStyle"></div></div></label>',
        props: ['value', 'height', 'width', 'id'],
        data: function () {
            return {
                internal: null
            }
        },
        computed: {
            blockStyle: function () {
                var style = {};
                if (this.height) {
                    style.height = this.height + 'px';
                }
                if (this.width) {
                    style.width = this.width + 'px';
                }
                return style;
            },
            elemStyle: function () {
                var style = {};
                if (this.height) {
                    style.height = style.width = (this.height - 2) + 'px';
                }
                return style;
            }
        },
        created: function () {
            this.internal = this.value;
        },
        watch: {
            value: function (value) {
                this.internal = this.value;
            },
            internal: function (internal) {
                this.$emit('input', internal);
            }
        }
    });

    Vue.component('dropdown', {
        props: ['id', 'label'],
        template: '<div class="dropdown action" :class="{open:ddOpen(id)}">' +
        '<a href="#" class="dropdown-toggle" @click.prevent.stop="ddToggle(id)">' +
        '<span>{{label}}</span><span class="f-caret"><b class="caret"></b></span></a>' +
        '<div class="dropdown-menu" @click.stop><slot></slot></div></div>'
    });

    // Filters

    Vue.filter('@', function (path) { return _.at(this, [path])[0]; });

    Vue.filter('_', SvMixinCommon.methods._);

    Vue.filter('currency', function (value, currencyCode) {
        //TODO: implement config by currencyCode
        var symbol = '$', precision = 2, thousand = ',', decimal = '.', format = '%s%v';
        return Accounting.formatMoney(value, symbol, precision, thousand, decimal, format);
    });

    Vue.filter('date', function (value, format) {
        switch (format || '') {
            case '': format = 'MMMM Do YYYY, h:mm:ss a'; break;
            case 'short': format = 'MM Do \'YY'; break;
        }
        return Moment(value, 'YYYY-MM-DD hh:mm:ss').format(format);
    });

    Vue.filter('ago', function (value) {
        return Moment(value, 'YYYY-MM-DD hh:mm:ss').fromNow();
    });

    Vue.filter('size', function (value) {
        if (typeof value === 'object') {
            return Object.keys(value).length;
        } else if (value.isArray()) {
            return value.length;
        } else {
            return 1;
        }
    });

    // RequireJS configuration

    requirejs.onError = function (err) {
        console.log('onError', err.xhr);
        if (err.xhr) {
            if (err.xhr.status === 401) {
                router.push('/login');
            }
        } else {
            console.log(err);
        }
    };

    requirejs.config({
        config: {
            text: {
                onXhrComplete: function (xhr, url) {
                    var response = xhr.response;
                    if (_.isString(response) && response[0] === '{') {
                        response = JSON.parse(response);
                    } else {
                        return;
                    }
                    if (response._login) {
                        router.push('/login');
                    }
                }
            }
        }
    });

    // Window resize handling

    $(window).resize(function (ev) {
        SvMixinCommon.store.commit('windowResize', $(window).width());
    });
    SvMixinCommon.store.commit('windowResize', $(window).width());

    return {
    }
});