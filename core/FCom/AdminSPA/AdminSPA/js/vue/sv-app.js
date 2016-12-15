define(['jquery', 'vue'], function ($, Vue) {

    // Translations, usage: <t>String<t> or <t tag="div" :args="{p:page, m:max}">Page {p} of {m}</t>
    //TODO: implement Sellvana logic
    Vue.component('t', {
        props: {
            'tag' : { type: String, default: 'span' },
            'args': { type: Object, default: function () { return {}; } }
        },
        render: function (h) {
            var data = {}, result = this.$slots.default;
            /*
             var translated = _(result[0].text, this.args);
             if (!translated.match(/^\{\{/)) {
             result = translated;
             }
             */
            return h(this.tag, data, result);
        }
    });

    Vue.component('select2', {
        props: ['options', 'value'],
        template: '<select><slot></slot></select>',
        mounted: function () {
            var vm = this;
            $(this.$el).val(this.value).select2({ data: this.options }).on('change', function () { vm.$emit('input', this.value); });
        },
        watch: {
            value: function (value) { $(this.$el).select2('val', value); },
            options: function (options) { $(this.$el).select2({ data: options }); }
        },
        destroyed: function () { $(this.$el).off().select2('destroy'); }
    });

    return {
        data: {
            modules: {}
        },
        methods: {
            assetUrl: function (module, path) {
                return Sellvana.data.modules[module].src_root + '/AdminSPA/' + path;
            },
            componentUrl: function (module, path, type) {
                type = type || 'component';
                var url = Sellvana.data.modules[module].src_root + '/AdminSPA/vue/' + type + '/' + path;
                if (path.match(/\.html$/)) {
                    url = 'text!' + url;
                }
                return url;
            },
            routeView: function (args) {
                return function (resolve, reject) {
                    require(args, function (component, template) {
                        if (!component) {
                            component = {};
                        }
                        if (template) {
                            component.template = template;
                        }
                        resolve(component);
                    });
                }
            }
        },
        views: {

        }
    }
});