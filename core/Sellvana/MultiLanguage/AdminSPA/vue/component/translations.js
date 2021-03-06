define(['lodash', 'jquery', 'vue', 'sv-app-data', 'text!sv-comp-form-translations-tpl'], function (_, $, Vue, SvAppData, translationsTpl) {
    var SvCompFormTranslations = {
        template: translationsTpl,
        props: ['form', 'field'],
        data: function () {
            return {
                translations: {},
                new_locale: '',
                avail_locales: []
            }
        },
        computed: {
            all_locales: function () {
                return SvAppData.locales_seq;
            }
        },
        methods: {
            field_id: function (locale) {
                var f = this.field;
                return 'translation-' + f.model + '-' + f.tab + '-' + f.name + '-' + locale;
            },
            calcAvailableLocales: function () {
                var usedLocales = {}, locales = [], i, l, loc;
                for (i = 0, l = this.translations.length; i < l; i++) {
                    usedLocales[this.translations[i].locale] = 1;
                }
                for (i = 0, l = SvAppData.locales_seq.length; i < l; i++) {
                    loc = SvAppData.locales_seq[i];
                    if (!usedLocales[loc.id]) {
                        locales.push(loc);
                    }
                }
                this.avail_locales = locales;
            },
            addTranslation: function () {
                this.$set(this.translations, this.new_locale, '');
                this.new_locale = '';
                this.calcAvailableLocales();
            },
            removeTranslation: function (locale) {
                Vue.delete(this.translations, locale);
                this.calcAvailableLocales();
            },
            close: function () {
                this.emitEvent('close');
                this.$store.commit('overlay', false);
            }
        },
        created: function () {
            this.$set(this, 'translations', this.form.i18n[this.field.name] || {});
            this.calcAvailableLocales();
        },
        mounted: function () {
            var $container = $(this.$refs.container);
            if ($container.css('max-width') !== 'none') {
                this.$store.commit('overlay', true);
            }
        },
        watch: {
            field: {
                handler: function (field) {
                    this.$set(this, 'translations', this.form.i18n[this.field.name] || {});
                    this.calcAvailableLocales();
                },
                deep: true
            },
            new_locale: function (new_locale) {
                if (new_locale) {
                    this.addTranslation();
                }
            },
            translations: {
                handler: function (translations) {
                    this.emitEvent('update', {field: this.field, translations: translations});
                },
                deep: true
            },
            '$store.state.ui.overlayActive': function (overlayActive) {
                if (!overlayActive) {
                    this.emitEvent('close');
                }
            }
        }
    };

    Vue.component('sv-comp-form-translations', SvCompFormTranslations);

    return SvCompFormTranslations;
});