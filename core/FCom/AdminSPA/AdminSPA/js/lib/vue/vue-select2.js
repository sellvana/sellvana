define(['jquery', 'lodash', 'select2'], function ($, _) {
    return {
        props: {
            value: {},
            options: {
                type: Array,
                default: function () {
                    return [];
                }
            },
            params: {
                type: Object,
                default: function () {
                    return {};
                }
            },
            onChange: {
                type: Function
            }
        },
        template: '<select><slot></slot></select>',
        mounted: function () {
            var vm = this, params = $.extend({}, this.params);
            if (this.options) {
                params.data = this.options;
            }
//console.log('mounted', this.value);
            $(this.$el).val(this.value).select2(params).on('change', function () {
                var $el = $(vm.$el), val = $el.val();
                vm.$emit('input', val);
                if (vm.onChange) {
                    vm.onChange(val);
                }
//console.log('HERE');
                // vm.options = null;
                // $el.select2('data', []);
            });
        },
        watch: {
            value: function (value) {
//console.log('value', value);
                var $el = $(this.$el);
                if (!_.isEqual($el.val(), value)) {
                    $el.val(value).trigger('change.select2');
                }
            },
            options: function (options) {
//console.log('options', options);
                var $el = $(this.$el);
                // if (!_.isEqual($el.select2('data'), options)) {
//console.log('update options', options);
                var params = _.extend({}, this.params, {data: options});
                $el.empty().select2('data', options);
                //$el.select2('data', options);
                // }
            },
            params: function (params) {
                //params.data = this.options;
//console.log('params', params);
                var $el = $(this.$el);
                if (this.options) {
                    params = _.extend(params, {data: this.options});
                }
                $el.empty().select2(params);
            }
        },
        destroyed: function () {
            $(this.$el).off().select2('destroy');
        }
    };
});