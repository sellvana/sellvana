/**
 * Created by pp on 20.02.14.
 */
/**
 * JavaScript locale module,
 * require(['jquery', 'fcom.locale'], function ($, Locale) {
 *      Locale.addTranslations({{ LOCALE.translations(['Categories','Home', ['Search: %s', 'me']]) | raw }});
 *      Locale._('Categories'); // in de locale results in 'Kategorien'
 *      Locale._('Search: %s'); // in de locale results in 'Suche: me'
 * }
 */
define(['jquery'], function ($) {
    return {
        translations: {},
        addTranslations: function (translations) {
            var self = this;
            $.each(translations, function(original, translated){
                self.addTranslation(original, translated);
            });
        },
        addTranslation: function (original, translated) {
            original = this.processPhrase(original);
            translated = this.processPhrase(translated);
            if (this.translations[original] != undefined) {
                console.log("Replacing translation for [%s]", original);
            }
            this.translations[original] = translated;
        },
        _: function (phrase) {
            phrase = this.processPhrase(phrase);
            if (this.translations[phrase] != undefined) {
                return this.translations[phrase];
            }
            console.log("Could not find translation for: [%s]", phrase);
            return phrase;
        },
        processPhrase: function (phrase) {
            return $.trim(phrase);
        }
    };
})
;
