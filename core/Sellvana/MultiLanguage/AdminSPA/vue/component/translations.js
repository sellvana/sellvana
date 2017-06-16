define(['lodash', 'vue', 'text!sv-comp-form-translations-tpl'], function (_, Vue, translationsTpl) {
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
            },
            field_id: function () {
                var vm = this, f = this.field;
                return function (locale) {
                    return 'translation-' + f.model + '-' + f.tab + '-' + f.name + '-' + locale;
                }
            }
        },
        methods: {
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
                Vue.set(this.translations, this.new_locale, '');
                this.new_locale = '';
                this.calcAvailableLocales();
            },
            removeTranslation: function (locale) {
                Vue.delete(this.translations, locale);
                this.calcAvailableLocales();
            },
            close: function () {
                this.$emit('event', 'close');
                this.$store.commit('overlay', false);
            }
        },
        created: function () {
            Vue.set(this, 'translations', this.form.i18n[this.field.name] || {});
            this.calcAvailableLocales();
        },
        watch: {
            field: {
                handler: function (field) {
                    Vue.set(this, 'translations', this.form.i18n[this.field.name] || {});
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
                    this.$emit('event', 'update', {field: this.field, translations: translations});
                },
                deep: true
            },
            '$store.state.ui.overlayActive': function (overlayActive) {
                if (!overlayActive) {
                    this.$emit('event', 'close');
                }
            }
        }
    };

    Vue.component('sv-comp-form-translations', SvCompFormTranslations);

    return SvCompFormTranslations;
});