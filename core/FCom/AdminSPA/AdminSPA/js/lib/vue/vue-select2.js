define(['jquery', 'lodash', 'select2'], function ($, _) {
    function normalizeOptions(options) {
        if (_.isArrayLike(options)) {
            return options;
        }
        var result = [], i;
        for (i in options) {
            result.push({id: i, text: options[i]});
        }
        return result;
    }

    return {
        props: {
            value: {},
            options: {
                type: [Array, Object],
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
            params.data = normalizeOptions(params.data);
            $(this.$el).val(this.value).select2(params).on('change', function () {
                var $el = $(vm.$el), val = $el.val();
                vm.$emit('input', val);
                if (vm.onChange) {
                    vm.onChange(val);
                }
            });
        },
        watch: {
            value: function (value) {
                var $el = $(this.$el);
                if (!_.isEqual($el.val(), value)) {
                    $el.val(value).trigger('change.select2');
                }
            },
            options: function (options) {
                var $el = $(this.$el);
                var options1 = normalizeOptions(options);
                $el.empty().select2('data', options1);
            },
            params: function (params) {
                var $el = $(this.$el);
                if (this.options) {
                    params = _.extend(params, {data: this.options});
                }
                params.data = normalizeOptions(params.data);
                $el.empty().select2(params);
            }
        },
        destroyed: function () {
            $(this.$el).off().select2('destroy');
        }
    };
});