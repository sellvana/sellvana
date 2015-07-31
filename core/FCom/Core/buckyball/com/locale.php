<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
* Facility to handle l10n and i18n
*/
class BLocale extends BClass
{
    static protected $_domainPrefix = 'fulleron/';
    static protected $_domainStack = [];

    /**
     * Default locale
     *
     * @var string
     */
    protected $_defaultLocale = 'en_US';

    /**
     * Current locale
     *
     * @var string
     */
    protected $_currentLocale;

    /**
    * Default timezone
    *
    * @var string
    */
    protected $_defaultTz = 'America/Los_Angeles';

    /**
    * Cache for DateTimeZone objects
    *
    * @var DateTimeZone
    */
    protected $_tzCache = [];

    /**
    * Translations tree
    *
    * static::$_tr = array(
    *   'STRING1' => 'DEFAULT TRANSLATION',
    *   'STRING2' => array(
    *      '_' => 'DEFAULT TRANSLATION',
    *      'Module1' => 'MODULE1 TRANSLATION',
    *      'Module2' => 'MODULE2 TRANSLATION',
    *      ...
    *   ),
    * );
    *
    * @var array
    */
    protected static $_tr;

    protected static $_customTranslators = [];

    /**
     * Shortcut to help with IDE autocompletion
     *
     * @param bool  $new
     * @param array $args
     * @return BLocale
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }

    /**
    * Constructor, set default timezone and locale
    *
    */
    public function __construct()
    {
        date_default_timezone_set($this->_defaultTz);
        setlocale(LC_ALL, $this->_defaultLocale);
        $this->_tzCache['UTC'] = new DateTimeZone('UTC');
    }

    public function transliterate($str, $filler = '-')
    {
        $qFiller = preg_quote($filler);
        if (function_exists('transliterator_transliterate')) { // PHP >= 5.4.0
            $str = preg_replace('/[^\\pL0-9]+/u', ' ', $str); // leave only letters and numbers, consolidate fillers
            $rules = "Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();";
            $str = transliterator_transliterate($rules, $str);
            $str = preg_replace('/[' . $qFiller . '\s]+/', $filler, $str); // consolidate fillers
        } else {
            $str = preg_replace('/[^\\pL0-9]+/u', $filler, $str); // leave only letters and numbers, consolidate fillers
            $str = iconv("utf-8", "us-ascii//TRANSLIT", $str); // transliterate
            $str = preg_replace('/[^' . $qFiller . 'a-z0-9]+/', '', strtolower($str)); // remove leftovers from transliteration
        }
        $str = trim($str, strtolower($filler)); // remove fillers from start and end of string|string is converted to lower case, so should be the filter char
        return $str;
    }

    public function getAvailableLocaleCodes()
    {
        static $codes = [
            'aa_DJ', 'aa_ER', 'aa_ET', 'af_ZA', 'am_ET', 'an_ES', 'ar_AE', 'ar_BH', 'ar_DZ', ' ar_EG', 'ar_IN',
            'ar_IQ', 'ar_JO', 'ar_KW', 'ar_LB', 'ar_LY', 'ar_MA', 'ar_OM', ' ar_QA', 'ar_SA', 'ar_SD', 'ar_SY',
            'ar_TN', 'ar_YE', 'az_AZ', 'as_IN', 'ast_ES', ' be_BY', 'bem_ZM', 'ber_DZ', 'ber_MA', 'bg_BG',
            'bho_IN', 'bn_BD', 'bn_IN', 'bo_CN', ' bo_IN', 'br_FR', 'brx_IN', 'bs_BA', 'byn_ER', 'ca_AD',
            'ca_ES', 'ca_FR', 'ca_IT', 'crh_UA', 'cs_CZ', 'csb_PL', 'cv_RU', 'cy_GB', 'da_DK', 'de_AT', 'de_BE',
            'de_CH', 'de_DE', 'de_LU', 'dv_MV', 'dz_BT', 'el_GR', 'el_CY', 'en_AG', 'en_AU', 'en_BW', 'en_CA',
            'en_DK', 'en_GB', 'en_HK', 'en_IE', 'en_IN', 'en_NG', 'en_NZ', 'en_PH', 'en_SG', 'en_US', 'en_ZA',
            'en_ZM', 'en_ZW', 'es_AR', 'es_BO', 'es_CL', 'es_CO', 'es_CR', 'es_CU', 'es_DO', 'es_EC', 'es_ES',
            'es_GT', 'es_HN', 'es_MX', 'es_NI', 'es_PA', 'es_PE', 'es_PR', 'es_PY', 'es_SV', 'es_US', 'es_UY',
            'es_VE', 'et_EE', 'eu_ES', 'fa_IR', 'ff_SN', 'fi_FI', 'fil_PH', 'fo_FO', 'fr_BE', 'fr_CA', 'fr_CH',
            'fr_FR', 'fr_LU', 'fur_IT', 'fy_NL', 'fy_DE', 'ga_IE', 'gd_GB', 'gez_ER', 'gez_ET', 'gl_ES', 'gu_IN',
            'gv_GB', 'ha_NG', 'he_IL', 'hi_IN', 'hne_IN', 'hr_HR', 'hsb_DE', 'ht_HT', 'hu_HU', 'hy_AM', 'id_ID',
            'ig_NG', 'ik_CA', 'is_IS', 'it_CH', 'it_IT', 'iu_CA', 'iw_IL', 'ja_JP', 'ka_GE', 'kk_KZ', 'kl_GL',
            'km_KH', 'kn_IN', 'ko_KR', 'kok_IN', 'ks_IN', 'ku_TR', 'kw_GB', 'ky_KG', 'lb_LU', 'lg_UG', 'li_BE',
            'li_NL', 'lij_IT', 'lo_LA', 'lt_LT', 'lv_LV', 'mag_IN', 'mai_IN', 'mg_MG', 'mhr_RU', 'mi_NZ',
            'mk_MK', 'ml_IN', 'mn_MN', 'mr_IN', 'ms_MY', 'mt_MT', 'my_MM', 'nan_TW', 'nb_NO', 'nds_DE', 'nds_NL',
            'ne_NP', 'nl_AW', 'nl_BE', 'nl_NL', 'nn_NO', 'nr_ZA', 'nso_ZA', 'oc_FR', 'om_ET', 'om_KE', 'or_IN',
            'os_RU', 'pa_IN', 'pa_PK', 'pap_AN', 'pl_PL', 'ps_AF', 'pt_BR', 'pt_PT', 'ro_RO', 'ru_RU', 'ru_UA',
            'rw_RW', 'sa_IN', 'sc_IT', 'sd_IN', 'se_NO', 'shs_CA', 'si_LK', 'sid_ET', 'sk_SK', 'sl_SI', 'so_DJ',
            'so_ET', 'so_KE', 'so_SO', 'sq_AL', 'sq_MK', 'sr_ME', 'sr_RS', 'ss_ZA', 'st_ZA', 'sv_FI', 'sv_SE',
            'sw_KE', 'sw_TZ', 'ta_IN', 'ta_LK', 'te_IN', 'tg_TJ', 'th_TH', 'ti_ER', 'ti_ET', 'tig_ER', 'tk_TM',
            'tl_PH', 'tn_ZA', 'tr_CY', 'tr_TR', 'ts_ZA', 'tt_RU', 'ug_CN', 'uk_UA', 'unm_US', 'ur_IN', 'ur_PK',
            'uz_UZ', 've_ZA', 'vi_VN', 'wa_BE', 'wae_CH', 'wal_ET', 'wo_SN', 'xh_ZA', 'yi_US', 'yo_NG', 'yue_HK',
            'zh_CN', 'zh_HK', 'zh_SG', 'zh_TW', 'zu_ZA',
        ];
        return array_combine($codes, $codes);
    }

    public function getAvailableLanguages($format = 'latin')
    {
        static $languages = [
            ['ab', 'Abkhaz', 'аҧсшәа'],
            ['aa', 'Afar', 'Afaraf'],
            ['af', 'Afrikaans', 'Afrikaans'],
            ['ak', 'Akan', 'Akan'],
            ['sq', 'Albanian', 'Shqip'],
            ['am', 'Amharic', 'አማርኛ'],
            ['ar', 'Arabic', 'العربية'],
            ['an', 'Aragonese', 'aragonés'],
            ['hy', 'Armenian', 'Հայերեն'],
            ['as', 'Assamese', 'অসমীয়া'],
            ['av', 'Avaric', 'магӀарул мацӀ'],
            ['ae', 'Avestan', 'avesta'],
            ['ay', 'Aymara', 'aymar aru'],
            ['az', 'Azerbaijani', 'azərbaycan dili'],
            ['bm', 'Bambara', 'bamanankan'],
            ['ba', 'Bashkir', 'башҡорт теле'],
            ['eu', 'Basque', 'euskara'],
            ['be', 'Belarusian', 'беларуская мова'],
            ['bn', 'Bengali, Bangla', 'বাংলা'],
            ['bh', 'Bihari', 'भोजपुरी'],
            ['bi', 'Bislama', 'Bislama'],
            ['bs', 'Bosnian', 'bosanski jezik'],
            ['br', 'Breton', 'brezhoneg'],
            ['bg', 'Bulgarian', 'български език'],
            ['my', 'Burmese', 'ဗမာစာ'],
            ['ca', 'Catalan, Valencian', 'català, valencià'],
            ['ch', 'Chamorro', 'Chamoru'],
            ['ce', 'Chechen', 'нохчийн мотт'],
            ['ny', 'Chichewa, Chewa, Nyanja', 'chiCheŵa, chinyanja'],
            ['zh', 'Chinese', '中文 (Zhōngwén), 汉语, 漢語'],
            ['cv', 'Chuvash', 'чӑваш чӗлхи'],
            ['kw', 'Cornish', 'Kernewek'],
            ['co', 'Corsican', 'corsu'],
            ['cr', 'Cree', 'ᓀᐦᐃᔭᐍᐏᐣ'],
            ['hr', 'Croatian', 'hrvatski jezik'],
            ['cs', 'Czech', 'čeština'],
            ['da', 'Danish', 'dansk'],
            ['dv', 'Divehi, Dhivehi', 'ދިވެހި'],
            ['nl', 'Dutch', 'Nederlands'],
            ['dz', 'Dzongkha', 'རྫོང་ཁ'],
            ['en', 'English', 'English'],
            ['eo', 'Esperanto', 'Esperanto'],
            ['et', 'Estonian', 'eesti'],
            ['ee', 'Ewe', 'Eʋegbe'],
            ['fo', 'Faroese', 'føroyskt'],
            ['fj', 'Fijian', 'vosa Vakaviti'],
            ['fi', 'Finnish', 'suomi'],
            ['fr', 'French', 'Français'],
            ['ff', 'Fula, Fulah, Pulaar, Pular', 'Fulfulde, Pulaar, Pular'],
            ['gl', 'Galician', 'Galego'],
            ['ka', 'Georgian', 'ქართული'],
            ['de', 'German', 'Deutsch'],
            ['el', 'Greek (modern)', 'ελληνικά'],
            ['gn', 'Guaraní', 'Avañe\'ẽ'],
            ['gu', 'Gujarati', 'ગુજરાતી'],
            ['ht', 'Haitian, Haitian Creole', 'Kreyòl ayisyen'],
            ['ha', 'Hausa', '(Hausa) هَوُسَ'],
            ['he', 'Hebrew (modern)', 'עברית'],
            ['hz', 'Herero', 'Otjiherero'],
            ['hi', 'Hindi', 'हिन्दी, हिंदी'],
            ['ho', 'Hiri Motu', 'Hiri Motu'],
            ['hu', 'Hungarian', 'Magyar'],
            ['ia', 'Interlingua', 'Interlingua'],
            ['id', 'Indonesian', 'Bahasa Indonesia'],
            ['ie', 'Interlingue', 'Interlingue'],
            ['ga', 'Irish', 'Gaeilge'],
            ['ig', 'Igbo', 'Asụsụ Igbo'],
            ['ik', 'Inupiaq', 'Iñupiaq, Iñupiatun'],
            ['io', 'Ido', 'Ido'],
            ['is', 'Icelandic', 'Íslenska'],
            ['it', 'Italian', 'italiano'],
            ['iu', 'Inuktitut', 'ᐃᓄᒃᑎᑐᑦ'],
            ['ja', 'Japanese', '日本語 (にほんご)'],
            ['jv', 'Javanese', 'basa Jawa'],
            ['kl', 'Kalaallisut, Greenlandic', 'kalaallisut'],
            ['kn', 'Kannada', 'ಕನ್ನಡ'],
            ['kr', 'Kanuri', 'Kanuri'],
            ['ks', 'Kashmiri', 'कश्मीरी, كشميري‎'],
            ['kk', 'Kazakh', 'қазақ тілі'],
            ['km', 'Khmer', 'ខ្មែរ, ខេមរភាសា, ភាសាខ្មែរ'],
            ['ki', 'Kikuyu, Gikuyu', 'Gĩkũyũ'],
            ['rw', 'Kinyarwanda', 'Ikinyarwanda'],
            ['ky', 'Kyrgyz', 'Кыргызча'],
            ['kv', 'Komi', 'коми кыв'],
            ['kg', 'Kongo', 'Kikongo'],
            ['ko', 'Korean', '한국어, 조선어'],
            ['ku', 'Kurdish', 'Kurdî, كوردی‎'],
            ['kj', 'Kwanyama, Kuanyama', 'Kuanyama'],
            ['la', 'Latin', 'latine, lingua latina'],
            ['lb', 'Luxembourgish, Letzeburgesch', 'Lëtzebuergesch'],
            ['lg', 'Ganda', 'Luganda'],
            ['li', 'Limburgish, Limburgan, Limburger', 'Limburgs'],
            ['ln', 'Lingala', 'Lingála'],
            ['lo', 'Lao', 'ພາສາລາວ'],
            ['lt', 'Lithuanian', 'lietuvių kalba'],
            ['lu', 'Luba-Katanga', 'Tshiluba'],
            ['lv', 'Latvian', 'latviešu valoda'],
            ['gv', 'Manx', 'Gaelg, Gailck'],
            ['mk', 'Macedonian', 'македонски јазик'],
            ['mg', 'Malagasy', 'fiteny malagasy'],
            ['ms', 'Malay', 'bahasa Melayu, بهاس ملايو‎'],
            ['ml', 'Malayalam', 'മലയാളം'],
            ['mt', 'Maltese', 'Malti'],
            ['mi', 'Māori', 'te reo Māori'],
            ['mr', 'Marathi (Marāṭhī)', 'मराठी'],
            ['mh', 'Marshallese', 'Kajin M̧ajeļ'],
            ['mn', 'Mongolian', 'монгол'],
            ['na', 'Nauru', 'Ekakairũ Naoero'],
            ['nv', 'Navajo, Navaho', 'Dinékʼehǰí'],
            ['nd', 'Northern Ndebele', 'isiNdebele'],
            ['ne', 'Nepali', 'नेपाली'],
            ['ng', 'Ndonga', 'Owambo'],
            ['nb', 'Norwegian Bokmål', 'Norsk bokmål'],
            ['nn', 'Norwegian Nynorsk', 'Norsk nynorsk'],
            ['no', 'Norwegian', 'Norsk'],
            ['ii', 'Nuosu', 'ꆈꌠ꒿ Nuosuhxop'],
            ['nr', 'Southern Ndebele', 'isiNdebele'],
            ['oc', 'Occitan', 'occitan, lenga d\òc'],
            ['oj', 'Ojibwe, Ojibwa', 'ᐊᓂᔑᓈᐯᒧᐎᓐ'],
            ['cu', 'Old Church Slavonic, Church Slavonic, Old Bulgarian', 'ѩзыкъ словѣньскъ'],
            ['om', 'Oromo', 'Afaan Oromoo'],
            ['or', 'Oriya', 'ଓଡ଼ିଆ'],
            ['os', 'Ossetian, Ossetic', 'ирон æвзаг'],
            ['pa', 'Panjabi, Punjabi', 'ਪੰਜਾਬੀ, پنجابی‎'],
            ['pi', 'Pāli', 'पाऴि'],
            ['fa', 'Persian (Farsi)', 'فارسی'],
            ['pl', 'Polish', 'polszczyzna'],
            ['ps', 'Pashto, Pushto', 'پښتو'],
            ['pt', 'Portuguese', 'português'],
            ['qu', 'Quechua', 'Kichwa'],
            ['rm', 'Romansh', 'rumantsch grischun'],
            ['rn', 'Kirundi', 'Ikirundi'],
            ['ro', 'Romanian', 'limba română'],
            ['ru', 'Russian', 'русский язык'],
            ['sa', 'Sanskrit (Saṁskṛta)', 'संस्कृतम्'],
            ['sc', 'Sardinian', 'sardu'],
            ['sd', 'Sindhi', 'सिन्धी, سنڌي، سندھی‎'],
            ['se', 'Northern Sami', 'Davvisámegiella'],
            ['sm', 'Samoan', 'gagana fa\'a Samoa'],
            ['sg', 'Sango', 'yângâ tî sängö'],
            ['sr', 'Serbian', 'српски језик'],
            ['gd', 'Scottish Gaelic, Gaelic', 'Gàidhlig'],
            ['sn', 'Shona', 'chiShona'],
            ['si', 'Sinhala, Sinhalese', 'සිංහල'],
            ['sk', 'Slovak', 'slovenčina, slovenský jazyk'],
            ['sl', 'Slovene', 'slovenski jezik, slovenščina'],
            ['so', 'Somali', 'Soomaaliga, af Soomaali'],
            ['st', 'Southern Sotho', 'Sesotho'],
            ['es', 'Spanish', 'español'],
            ['su', 'Sundanese', 'Basa Sunda'],
            ['sw', 'Swahili', 'Kiswahili'],
            ['ss', 'Swati', 'SiSwati'],
            ['sv', 'Swedish', 'Svenska'],
            ['ta', 'Tamil', 'தமிழ்'],
            ['te', 'Telugu', 'తెలుగు'],
            ['tg', 'Tajik', 'тоҷикӣ, toğikī, تاجیکی‎'],
            ['th', 'Thai', 'ไทย'],
            ['ti', 'Tigrinya', 'ትግርኛ'],
            ['bo', 'Tibetan Standard, Tibetan, Central', 'བོད་ཡིག'],
            ['tk', 'Turkmen', 'Türkmen, Түркмен'],
            ['tl', 'Tagalog', 'Wikang Tagalog, ᜏᜒᜃᜅ᜔ ᜆᜄᜎᜓᜄ᜔'],
            ['tn', 'Tswana', 'Setswana'],
            ['to', 'Tonga (Tonga Islands)', 'faka Tonga'],
            ['tr', 'Turkish', 'Türkçe'],
            ['ts', 'Tsonga', 'Xitsonga'],
            ['tt', 'Tatar', 'tatar tele'],
            ['tw', 'Twi', 'Twi'],
            ['ty', 'Tahitian', 'Reo Tahiti'],
            ['ug', 'Uyghur, Uighur', 'Uyƣurqə, ئۇيغۇرچە‎'],
            ['uk', 'Ukrainian', 'українська мова'],
            ['ur', 'Urdu', 'اردو'],
            ['uz', 'Uzbek', 'O‘zbek, Ўзбек, أۇزبېك‎'],
            ['ve', 'Venda', 'Tshivenḓa'],
            ['vi', 'Vietnamese', 'Tiếng Việt'],
            ['vo', 'Volapük', 'Volapük'],
            ['wa', 'Walloon', 'walon'],
            ['cy', 'Welsh', 'Cymraeg'],
            ['wo', 'Wolof', 'Wollof'],
            ['fy', 'Western Frisian', 'Frysk'],
            ['xh', 'Xhosa', 'isiXhosa'],
            ['yi', 'Yiddish', 'ייִדיש'],
            ['yo', 'Yoruba', 'Yorùbá'],
            ['za', 'Zhuang, Chuang', 'Saɯ cueŋƅ, Saw cuengh'],
            ['zu', 'Zulu', 'isiZulu'],
        ];

        $result = [];
        switch ($format) {
            case 'latin':
                if (function_exists('array_column')) {
                    $result = array_column($languages, 1, 0);
                } else {
                    foreach ($languages as $a) {
                        $result[$a[0]] = $a[1];
                    }
                }
                break;

            case 'native':
                if (function_exists('array_column')) {
                    $result = array_column($languages, 2, 0);
                } else {
                    foreach ($languages as $a) {
                        $result[$a[0]] = $a[2];
                    }
                }
                break;

            case 'combined':
                foreach ($languages as $a) {
                    $result[$a[0]] = $a[1] . ' (' . $a[2] . ')';
                }
                break;

            case 'raw':
                $result = $languages;
                break;

            default:
                throw new BException('Invalid label type');
        }
        return $result;
    }

    public function getAvailableCountries($format = 'name', $limitCountries = null)
    {
        static $countries = [
            ['AD', 'Andorra', 'AND', '20'],
            ['AE', 'United Arab Emirates', 'ARE', '784'],
            ['AF', 'Afghanistan', 'AFG', '4'],
            ['AG', 'Antigua and Barbuda', 'ATG', '28'],
            ['AI', 'Anguilla', 'AIA', '660'],
            ['AL', 'Albania', 'ALB', '8'],
            ['AM', 'Armenia', 'ARM', '51'],
            ['AO', 'Angola', 'AGO', '24'],
            ['AQ', 'Antarctica', 'ATA', '10'],
            ['AR', 'Argentina', 'ARG', '32'],
            ['AS', 'American Samoa', 'ASM', '16'],
            ['AT', 'Austria', 'AUT', '40'],
            ['AU', 'Australia', 'AUS', '36'],
            ['AW', 'Aruba', 'ABW', '533'],
            ['AX', 'Aland Islands', 'ALA', '248'],
            ['AZ', 'Azerbaijan', 'AZE', '31'],
            ['BA', 'Bosnia and Herzegovina', 'BIH', '70'],
            ['BB', 'Barbados', 'BRB', '52'],
            ['BD', 'Bangladesh', 'BGD', '50'],
            ['BE', 'Belgium', 'BEL', '56'],
            ['BF', 'Burkina Faso', 'BFA', '854'],
            ['BG', 'Bulgaria', 'BGR', '100'],
            ['BH', 'Bahrain', 'BHR', '48'],
            ['BI', 'Burundi', 'BDI', '108'],
            ['BJ', 'Benin', 'BEN', '204'],
            ['BL', 'Saint Barthelemy', 'BLM', '652'],
            ['BM', 'Bermuda', 'BMU', '60'],
            ['BN', 'Brunei', 'BRN', '96'],
            ['BO', 'Bolivia', 'BOL', '68'],
            ['BQ', 'Bonaire, Sint Eustatius and Saba', 'BES', '535'],
            ['BR', 'Brazil', 'BRA', '76'],
            ['BS', 'Bahamas', 'BHS', '44'],
            ['BT', 'Bhutan', 'BTN', '64'],
            ['BV', 'Bouvet Island', 'BVT', '74'],
            ['BW', 'Botswana', 'BWA', '72'],
            ['BY', 'Belarus', 'BLR', '112'],
            ['BZ', 'Belize', 'BLZ', '84'],
            ['CA', 'Canada', 'CAN', '124'],
            ['CC', 'Cocos (Keeling) Islands', 'CCK', '166'],
            ['CD', 'Democratic Republic of the Congo', 'COD', '180'],
            ['CF', 'Central African Republic', 'CAF', '140'],
            ['CG', 'Congo', 'COG', '178'],
            ['CH', 'Switzerland', 'CHE', '756'],
            ['CI', "Cote d'ivoire (Ivory Coast)", 'CIV', '384'],
            ['CK', 'Cook Islands', 'COK', '184'],
            ['CL', 'Chile', 'CHL', '152'],
            ['CM', 'Cameroon', 'CMR', '120'],
            ['CN', 'China', 'CHN', '156'],
            ['CO', 'Colombia', 'COL', '170'],
            ['CR', 'Costa Rica', 'CRI', '188'],
            ['CU', 'Cuba', 'CUB', '192'],
            ['CV', 'Cape Verde', 'CPV', '132'],
            ['CW', 'Curacao', 'CUW', '531'],
            ['CX', 'Christmas Island', 'CXR', '162'],
            ['CY', 'Cyprus', 'CYP', '196'],
            ['CZ', 'Czech Republic', 'CZE', '203'],
            ['DE', 'Germany', 'DEU', '276'],
            ['DJ', 'Djibouti', 'DJI', '262'],
            ['DK', 'Denmark', 'DNK', '208'],
            ['DM', 'Dominica', 'DMA', '212'],
            ['DO', 'Dominican Republic', 'DOM', '214'],
            ['DZ', 'Algeria', 'DZA', '12'],
            ['EC', 'Ecuador', 'ECU', '218'],
            ['EE', 'Estonia', 'EST', '233'],
            ['EG', 'Egypt', 'EGY', '818'],
            ['EH', 'Western Sahara', 'ESH', '732'],
            ['ER', 'Eritrea', 'ERI', '232'],
            ['ES', 'Spain', 'ESP', '724'],
            ['ET', 'Ethiopia', 'ETH', '231'],
            ['FI', 'Finland', 'FIN', '246'],
            ['FJ', 'Fiji', 'FJI', '242'],
            ['FK', 'Falkland Islands (Malvinas)', 'FLK', '238'],
            ['FM', 'Micronesia', 'FSM', '583'],
            ['FO', 'Faroe Islands', 'FRO', '234'],
            ['FR', 'France', 'FRA', '250'],
            ['GA', 'Gabon', 'GAB', '266'],
            ['GB', 'United Kingdom', 'GBR', '826'],
            ['GD', 'Grenada', 'GRD', '308'],
            ['GE', 'Georgia', 'GEO', '268'],
            ['GF', 'French Guiana', 'GUF', '254'],
            ['GG', 'Guernsey', 'GGY', '831'],
            ['GH', 'Ghana', 'GHA', '288'],
            ['GI', 'Gibraltar', 'GIB', '292'],
            ['GL', 'Greenland', 'GRL', '304'],
            ['GM', 'Gambia', 'GMB', '270'],
            ['GN', 'Guinea', 'GIN', '324'],
            ['GP', 'Guadaloupe', 'GLP', '312'],
            ['GQ', 'Equatorial Guinea', 'GNQ', '226'],
            ['GR', 'Greece', 'GRC', '300'],
            ['GS', 'South Georgia and the South Sandwich Isl', 'SGS', '239'],
            ['GT', 'Guatemala', 'GTM', '320'],
            ['GU', 'Guam', 'GUM', '316'],
            ['GW', 'Guinea-Bissau', 'GNB', '624'],
            ['GY', 'Guyana', 'GUY', '328'],
            ['HK', 'Hong Kong', 'HKG', '344'],
            ['HM', 'Heard Island and McDonald Islands', 'HMD', '334'],
            ['HN', 'Honduras', 'HND', '340'],
            ['HR', 'Croatia', 'HRV', '191'],
            ['HT', 'Haiti', 'HTI', '332'],
            ['HU', 'Hungary', 'HUN', '348'],
            ['ID', 'Indonesia', 'IDN', '360'],
            ['IE', 'Ireland', 'IRL', '372'],
            ['IL', 'Israel', 'ISR', '376'],
            ['IM', 'Isle of Man', 'IMN', '833'],
            ['IN', 'India', 'IND', '356'],
            ['IO', 'British Indian Ocean Territory', 'IOT', '86'],
            ['IQ', 'Iraq', 'IRQ', '368'],
            ['IR', 'Iran', 'IRN', '364'],
            ['IS', 'Iceland', 'ISL', '352'],
            ['IT', 'Italy', 'ITA', '380'],
            ['JE', 'Jersey', 'JEY', '832'],
            ['JM', 'Jamaica', 'JAM', '388'],
            ['JO', 'Jordan', 'JOR', '400'],
            ['JP', 'Japan', 'JPN', '392'],
            ['KE', 'Kenya', 'KEN', '404'],
            ['KG', 'Kyrgyzstan', 'KGZ', '417'],
            ['KH', 'Cambodia', 'KHM', '116'],
            ['KI', 'Kiribati', 'KIR', '296'],
            ['KM', 'Comoros', 'COM', '174'],
            ['KN', 'Saint Kitts and Nevis', 'KNA', '659'],
            ['KP', 'North Korea', 'PRK', '408'],
            ['KR', 'South Korea', 'KOR', '410'],
            ['KW', 'Kuwait', 'KWT', '414'],
            ['KY', 'Cayman Islands', 'CYM', '136'],
            ['KZ', 'Kazakhstan', 'KAZ', '398'],
            ['LA', 'Laos', 'LAO', '418'],
            ['LB', 'Lebanon', 'LBN', '422'],
            ['LC', 'Saint Lucia', 'LCA', '662'],
            ['LI', 'Liechtenstein', 'LIE', '438'],
            ['LK', 'Sri Lanka', 'LKA', '144'],
            ['LR', 'Liberia', 'LBR', '430'],
            ['LS', 'Lesotho', 'LSO', '426'],
            ['LT', 'Lithuania', 'LTU', '440'],
            ['LU', 'Luxembourg', 'LUX', '442'],
            ['LV', 'Latvia', 'LVA', '428'],
            ['LY', 'Libya', 'LBY', '434'],
            ['MA', 'Morocco', 'MAR', '504'],
            ['MC', 'Monaco', 'MCO', '492'],
            ['MD', 'Moldava', 'MDA', '498'],
            ['ME', 'Montenegro', 'MNE', '499'],
            ['MF', 'Saint Martin', 'MAF', '663'],
            ['MG', 'Madagascar', 'MDG', '450'],
            ['MH', 'Marshall Islands', 'MHL', '584'],
            ['MK', 'Macedonia', 'MKD', '807'],
            ['ML', 'Mali', 'MLI', '466'],
            ['MM', 'Myanmar (Burma)', 'MMR', '104'],
            ['MN', 'Mongolia', 'MNG', '496'],
            ['MO', 'Macao', 'MAC', '446'],
            ['MP', 'Northern Mariana Islands', 'MNP', '580'],
            ['MQ', 'Martinique', 'MTQ', '474'],
            ['MR', 'Mauritania', 'MRT', '478'],
            ['MS', 'Montserrat', 'MSR', '500'],
            ['MT', 'Malta', 'MLT', '470'],
            ['MU', 'Mauritius', 'MUS', '480'],
            ['MV', 'Maldives', 'MDV', '462'],
            ['MW', 'Malawi', 'MWI', '454'],
            ['MX', 'Mexico', 'MEX', '484'],
            ['MY', 'Malaysia', 'MYS', '458'],
            ['MZ', 'Mozambique', 'MOZ', '508'],
            ['NA', 'Namibia', 'NAM', '516'],
            ['NC', 'New Caledonia', 'NCL', '540'],
            ['NE', 'Niger', 'NER', '562'],
            ['NF', 'Norfolk Island', 'NFK', '574'],
            ['NG', 'Nigeria', 'NGA', '566'],
            ['NI', 'Nicaragua', 'NIC', '558'],
            ['NL', 'Netherlands', 'NLD', '528'],
            ['NO', 'Norway', 'NOR', '578'],
            ['NP', 'Nepal', 'NPL', '524'],
            ['NR', 'Nauru', 'NRU', '520'],
            ['NU', 'Niue', 'NIU', '570'],
            ['NZ', 'New Zealand', 'NZL', '554'],
            ['OM', 'Oman', 'OMN', '512'],
            ['PA', 'Panama', 'PAN', '591'],
            ['PE', 'Peru', 'PER', '604'],
            ['PF', 'French Polynesia', 'PYF', '258'],
            ['PG', 'Papua New Guinea', 'PNG', '598'],
            ['PH', 'Phillipines', 'PHL', '608'],
            ['PK', 'Pakistan', 'PAK', '586'],
            ['PL', 'Poland', 'POL', '616'],
            ['PM', 'Saint Pierre and Miquelon', 'SPM', '666'],
            ['PN', 'Pitcairn', 'PCN', '612'],
            ['PR', 'Puerto Rico', 'PRI', '630'],
            ['PS', 'Palestine', 'PSE', '275'],
            ['PT', 'Portugal', 'PRT', '620'],
            ['PW', 'Palau', 'PLW', '585'],
            ['PY', 'Paraguay', 'PRY', '600'],
            ['QA', 'Qatar', 'QAT', '634'],
            ['RE', 'Reunion', 'REU', '638'],
            ['RO', 'Romania', 'ROU', '642'],
            ['RS', 'Serbia', 'SRB', '688'],
            ['RU', 'Russia', 'RUS', '643'],
            ['RW', 'Rwanda', 'RWA', '646'],
            ['SA', 'Saudi Arabia', 'SAU', '682'],
            ['SB', 'Solomon Islands', 'SLB', '90'],
            ['SC', 'Seychelles', 'SYC', '690'],
            ['SD', 'Sudan', 'SDN', '729'],
            ['SE', 'Sweden', 'SWE', '752'],
            ['SG', 'Singapore', 'SGP', '702'],
            ['SH', 'Saint Helena', 'SHN', '654'],
            ['SI', 'Slovenia', 'SVN', '705'],
            ['SJ', 'Svalbard and Jan Mayen', 'SJM', '744'],
            ['SK', 'Slovakia', 'SVK', '703'],
            ['SL', 'Sierra Leone', 'SLE', '694'],
            ['SM', 'San Marino', 'SMR', '674'],
            ['SN', 'Senegal', 'SEN', '686'],
            ['SO', 'Somalia', 'SOM', '706'],
            ['SR', 'Suriname', 'SUR', '740'],
            ['SS', 'South Sudan', 'SSD', '728'],
            ['ST', 'Sao Tome and Principe', 'STP', '678'],
            ['SV', 'El Salvador', 'SLV', '222'],
            ['SX', 'Sint Maarten', 'SXM', '534'],
            ['SY', 'Syria', 'SYR', '760'],
            ['SZ', 'Swaziland', 'SWZ', '748'],
            ['TC', 'Turks and Caicos Islands', 'TCA', '796'],
            ['TD', 'Chad', 'TCD', '148'],
            ['TF', 'French Southern Territories', 'ATF', '260'],
            ['TG', 'Togo', 'TGO', '768'],
            ['TH', 'Thailand', 'THA', '764'],
            ['TJ', 'Tajikistan', 'TJK', '762'],
            ['TK', 'Tokelau', 'TKL', '772'],
            ['TL', 'Timor-Leste (East Timor)', 'TLS', '626'],
            ['TM', 'Turkmenistan', 'TKM', '795'],
            ['TN', 'Tunisia', 'TUN', '788'],
            ['TO', 'Tonga', 'TON', '776'],
            ['TR', 'Turkey', 'TUR', '792'],
            ['TT', 'Trinidad and Tobago', 'TTO', '780'],
            ['TV', 'Tuvalu', 'TUV', '798'],
            ['TW', 'Taiwan', 'TWN', '158'],
            ['TZ', 'Tanzania', 'TZA', '834'],
            ['UA', 'Ukraine', 'UKR', '804'],
            ['UG', 'Uganda', 'UGA', '800'],
            ['UM', 'United States Minor Outlying Islands', 'UMI', '581'],
            ['US', 'United States', 'USA', '840'],
            ['UY', 'Uruguay', 'URY', '858'],
            ['UZ', 'Uzbekistan', 'UZB', '860'],
            ['VA', 'Vatican City', 'VAT', '336'],
            ['VC', 'Saint Vincent and the Grenadines', 'VCT', '670'],
            ['VE', 'Venezuela', 'VEN', '862'],
            ['VG', 'Virgin Islands, British', 'VGB', '92'],
            ['VI', 'Virgin Islands, US', 'VIR', '850'],
            ['VN', 'Vietnam', 'VNM', '704'],
            ['VU', 'Vanuatu', 'VUT', '548'],
            ['WF', 'Wallis and Futuna', 'WLF', '876'],
            ['WS', 'Samoa', 'WSM', '882'],
            ['XK', 'Kosovo', '', ''],
            ['YE', 'Yemen', 'YEM', '887'],
            ['YT', 'Mayotte', 'MYT', '175'],
            ['ZA', 'South Africa', 'ZAF', '710'],
            ['ZM', 'Zambia', 'ZMB', '894'],
            ['ZW', 'Zimbabwe', 'ZWE', '716'],
        ];
        $result = [];
        $format = strtolower($format);
        switch ($format) {
            case 'name':
                if (function_exists('array_column')) {
                    $result = array_column($countries, 1, 0);
                } else {
                    foreach ($countries as $a) {
                        $result[$a[0]] = $a[1];
                    }
                }
                if ($limitCountries) {
                    $limitCountries = array_flip($limitCountries);
                    foreach ($result as $k => $v) {
                        if (!isset($limitCountries[$k])) {
                            unset($result[$k]);
                        }
                    }
                }
                break;

            case 'raw':
                $result = $countries;
                break;

            default:
                throw new BException('Invalid label type');
        }
        return $result;
    }

    public function getAvailableRegions($format = 'name', $country = null)
    {
        static $regions = [
            'US' => [
                ['AK', 'Alaska', '1'],
                ['AL', 'Alabama', '2'],
                ['AS', 'American Samoa', '3'],
                ['AZ', 'Arizona', '4'],
                ['AR', 'Arkansas', '5'],
                ['CA', 'California', '6'],
                ['CO', 'Colorado', '7'],
                ['CT', 'Connecticut', '8'],
                ['DE', 'Delaware', '9'],
                ['DC', 'District of Columbia', '10'],
                ['FM', 'Federated States of Micronesia', '11'],
                ['FL', 'Florida', '12'],
                ['GA', 'Georgia', '13'],
                ['GU', 'Guam', '14'],
                ['HI', 'Hawaii', '15'],
                ['ID', 'Idaho', '16'],
                ['IL', 'Illinois', '17'],
                ['IN', 'Indiana', '18'],
                ['IA', 'Iowa', '19'],
                ['KS', 'Kansas', '20'],
                ['KY', 'Kentucky', '21'],
                ['LA', 'Louisiana', '22'],
                ['ME', 'Maine', '23'],
                ['MH', 'Marshall Islands', '24'],
                ['MD', 'Maryland', '25'],
                ['MA', 'Massachusetts', '26'],
                ['MI', 'Michigan', '27'],
                ['MN', 'Minnesota', '28'],
                ['MS', 'Mississippi', '29'],
                ['MO', 'Missouri', '30'],
                ['MT', 'Montana', '31'],
                ['NE', 'Nebraska', '32'],
                ['NV', 'Nevada', '33'],
                ['NH', 'New Hampshire', '34'],
                ['NJ', 'New Jersey', '35'],
                ['NM', 'New Mexico', '36'],
                ['NY', 'New York', '37'],
                ['NC', 'North Carolina', '38'],
                ['ND', 'North Dakota', '39'],
                ['MP', 'Northern Mariana Islands', '40'],
                ['OH', 'Ohio', '41'],
                ['OK', 'Oklahoma', '42'],
                ['OR', 'Oregon', '43'],
                ['PW', 'Palau', '44'],
                ['PA', 'Pennsylvania', '45'],
                ['PR', 'Puerto Rico', '46'],
                ['RI', 'Rhode Island', '47'],
                ['SC', 'South Carolina', '48'],
                ['SD', 'South Dakota', '49'],
                ['TN', 'Tennessee', '50'],
                ['TX', 'Texas', '51'],
                ['UT', 'Utah', '52'],
                ['VT', 'Vermont', '53'],
                ['VI', 'Virgin Islands', '54'],
                ['VA', 'Virginia', '55'],
                ['WA', 'Washington', '56'],
                ['WV', 'West Virginia', '57'],
                ['WI', 'Wisconsin', '58'],
                ['WY', 'Wyoming', '59'],
                ['AE', 'Armed Forces Africa', '60'],
                ['AA', 'Armed Forces Americas (except Canada)', '61'],
                ['AE', 'Armed Forces Canada', '62'],
                ['AE', 'Armed Forces Europe', '63'],
                ['AE', 'Armed Forces Middle East', '64'],
                ['AP', 'Armed Forces Pacific', '65'],
            ],
        ];
        $result = [];
        $format = strtolower($format);
        switch ($format) {
            case 'name':
                $hasArrayColumnFunc = function_exists('array_column');
                if (is_string($country)) {
                    if (empty($regions[$country])) {
                        $result = null;
                    } else {
                        if ($hasArrayColumnFunc) {
                            $result = array_column($regions[$country], 1, 0);
                        } else {
                            foreach ($regions[$country] as $a) {
                                $result[$a[0]] = $a[1];
                            }
                        }
                    }
                } else {
                    $limitCountries = is_array($country) ? $country : array_keys($regions);
                    foreach ($limitCountries as $country) {
                        if (!isset($regions[$country])) {
                            continue;
                        }
                        if ($hasArrayColumnFunc) {
                            $result['@' . $country] = array_column($regions[$country], 1, 0);
                        } else {
                            foreach ($regions[$country] as $a) {
                                $result['@' . $country][$a[0]] = $a[1];
                            }
                        }
                    }
                }
                break;

            case 'raw':
                $result = $regions;
                break;

            default:
                throw new BException('Invalid label type');
        }
        return $result;
    }

    public function getRegionCodeByName($regionName, $countryCode = 'US')
    {
        $regions = $this->getAvailableRegions('name', $countryCode);
        if ($regions) {
            $regions = array_flip(array_map('strtolower', $regions));
        }
        $regionName = strtolower($regionName);
        return isset($regions[$regionName]) ? $regions[$regionName] : null;
    }

    public function postcodeRequired($country = null)
    {
        static $requiredFor = [
            'DZ', 'AR', 'AM', 'AU', 'AT', 'AZ', 'A2', 'BD', 'BY', 'BE', 'BA', 'BR', 'BN', 'BG', 'CA', 'IC', 'CN', 'HR',
            'CY', 'CZ', 'DK', 'EN', 'EE', 'FO', 'FI', 'FR', 'GE', 'DE', 'GR', 'GL', 'GU', 'GG', 'HO', 'HU', 'IN', 'ID',
            'IL', 'IT', 'JP', 'JE', 'KZ', 'KR', 'KO', 'KG', 'LV', 'LI', 'LT', 'LU', 'MK', 'MG', 'M3', 'MY', 'MH', 'MQ',
            'YT', 'MX', 'MN', 'ME', 'NL', 'NZ', 'NB', 'NO', 'PK', 'PH', 'PL', 'PO', 'PT', 'PR', 'RE', 'RU', 'SA', 'SF',
            'CS', 'SG', 'SK', 'SI', 'ZA', 'ES', 'LK', 'NT', 'SX', 'UV', 'VL', 'SE', 'CH', 'TW', 'TJ', 'TH', 'TU', 'TN',
            'TR', 'TM', 'VI', 'UA', 'GB', 'US', 'UY', 'UZ', 'VA', 'VN', 'WL', 'YA',
        ];
        if (is_null($country)) {
            return $requiredFor;
        }
        return in_array($country, $requiredFor);
    }

    public function regionRequired($country = null)
    {
        static $requiredFor = ['US', 'CA', 'AU', 'CN', 'MX', 'MY', 'IT'];
        if (is_null($country)) {
            return $requiredFor;
        }
        return in_array($country, $requiredFor);
    }

    public function setCurrentLocale($locale)
    {
        if (strlen($locale) === 2 && strpos($this->_currentLocale, $locale) !== 0) {
            //TODO: move locales configuration to FCom_Core ?
            $allLocales = $this->BConfig->get('modules/Sellvana_MultiLanguage/allowed_locales', []);
            $allLocales += $this->getAvailableLocaleCodes();
            $found = false;
            foreach ($allLocales as $l) {
                if (strpos($l, $locale) === 0) {
                    $locale = $l;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->BDebug->warning('Invalid locale: ' . $locale);
                $locale = $this->_defaultLocale;
            }
        }
        $this->_currentLocale = $locale;
        list($lang) = explode('_', $locale, 2);
        $this->BSession->set('current_language', $lang)->set('current_locale', $locale);
        return $this;
    }

    public function getCurrentLocale()
    {
        if (empty($this->_currentLocale)) {
            $this->_currentLocale = $this->BSession->get('current_locale');
        }
        if (empty($this->_currentLocale)) {
            $this->_currentLocale = $this->_defaultLocale;
        }
        return $this->_currentLocale;
    }

    public function getCurrentLanguage()
    {
        list($lang) = explode('_', $this->getCurrentLocale());
        return $lang;
    }

    /**
     * Import translations to the tree
     *
     * @todo make more flexible with file location
     * @todo YAML
     * @param mixed $data array or file name string
     * @param array $params
     */
    public function importTranslations($data, $params = [])
    {
        $module = !empty($params['_module']) ? $params['_module'] : $this->BModuleRegistry->currentModuleName();
        if (is_string($data)) {
            if (!$this->BUtil->isPathAbsolute($data)) {
                $data = $this->BModuleRegistry->module($module)->root_dir . '/i18n/' . $data;
            }

            if (is_readable($data)) {
                $extension = !empty($params['extension']) ? $params['extension'] : 'csv';
                switch ($extension) {
                    case 'csv':
                        $fp = fopen($data, 'r');
                        while (($r = fgetcsv($fp, 2084))) {
                            static::_addTranslation($r, $module);
                        }
                        fclose($fp);
                        break;

                    case 'json':
                        $content = file_get_contents($data);
                        $translations = $this->BUtil->fromJson($content);
                        if (is_array($translations)) {
                            foreach ($translations as $word => $tr) {
                                static::_addTranslation([$word, $tr], $module);
                            }
                        }
                        break;

                    case 'php':
                        $translations = include $data;
                        foreach ($translations as $word => $tr) {
                            static::_addTranslation([$word, $tr], $module);
                        }
                        break;

                    case 'po':
                        //TODO: implement https://github.com/clinisbut/PHP-po-parser
                        $contentLines = file($data);
                        $translations = [];
                        $mode = null;
                        foreach ($contentLines as $line) {
                            $line = trim($line);
                            if ($line[0] === '"') {
                                $cmd = '+' . $mode;
                                $str = $line;
                            } else {
                                list($cmd, $str) = explode(' ', $line, 2);
                            }
                            $str = preg_replace('/(^\s*"|"\s*$)/', '', $str);
                            switch ($cmd) {
                                case 'msgid': $msgid = $str; $mode = $cmd; $translations[$msgid] = ''; break;
                                case '+msgid': $msgid .= $str; break;
                                case 'msgstr': $mode = $cmd; $translations[$msgid] = $str; break;
                                case '+msgstr': $translations[$msgid] .= $str; break;
                            }
                        }
                        break;
                }
            } else {
                BDebug::info('Could not load translation file: ' . $data);
                return;
            }
        } elseif (is_array($data)) {
            foreach ($data as $r) {
                static::_addTranslation($r, $module);
            }
        }
    }

    /**
    * Get all files from directory
    *
    * @todo merge to BUtil
    * @param mixed $dir
    * @return array
    */
    public function getFilesFromDir($dir)
    {
        $files = [];
        if (false !== ($handle = opendir($dir))) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    if (is_dir($dir . '/' . $file)) {
                        $dir2 = $dir . '/' . $file;
                        $files = array_merge($files, static::getFilesFromDir($dir2));
                    }
                    else {
                        $files[] = $dir . '/' . $file;
                    }
                }
            }
            closedir($handle);
        }

        return $files;
    }

    public function addTranslationsFile($file)
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (empty($ext)) {
            return $this;
        }
        $params['extension'] = $ext;
        static::importTranslations($file, $params);
        return $this;
    }

    protected function _addTranslation($r, $module = null)
    {
        if (empty($r[1])) {
            #BDebug::debug('Empty translation for "'.$r[0].'"');
            return;
        }
        // short and quick way
        static::$_tr[$r[0]][!empty($module) ? $module : '_'] = $r[1];

        /*
        // a bit of memory saving way
        list($from, $to) = $r;
        if (!empty($module)) { // module supplied
            if (!empty(static::$_tr[$from]) && is_string(static::$_tr[$from])) { // only default translation present
                static::$_tr[$from] = array('_'=>static::$_tr[$from]); // convert to array
            }
            static::$_tr[$from][$module] = $to; // save module specific translation
        } else { // no module, default translation
            if (!empty(static::$_tr[$from]) && is_array(static::$_tr[$from])) { // modular translations present
                static::$_tr[$from]['_'] = $to; // play nice
            } else {
                static::$_tr[$from] = $to; // simple
            }
        }
        */
    }

    public function cacheSave()
    {

    }

    public function cacheLoad()
    {

    }

    public function addCustomTranslator($name, $callback)
    {
        static::$_customTranslators[$name] = $this->BUtil->extCallback($callback);
        return $this;
    }

    public function _($string, $params = [], $module = null)
    {
        if (empty(static::$_tr[$string])) { // if no translation at all
            $tr = null;
            foreach (static::$_customTranslators as $translator) {
                $tr = call_user_func($translator, $string, null, $module);
                if ($tr) {
                    static::$_tr[$string]['_'] = $tr;
                    break;
                }
            }
            if (!$tr) {
                $tr = $string; // return original string
            }
        } else { // if some translation present
            $arr = static::$_tr[$string];
            if (!empty($module) && !empty($arr[$module])) { // if module requested and translation for it present
                $tr = $arr[$module]; // use it
            } elseif (!empty($arr['_'])) { // otherwise, if there's default translation
                $tr = $arr['_']; // use it
            } else { // otherwise
                reset($arr); // find the first available translation
                $tr = current($arr); // and use it
            }
        }

        if (is_array($tr) && !empty($tr['#'])) {
            $choices = $tr;
            $defaultString = '';
            $tr = null;
            $value = null;
            foreach ($choices as $condition => $string) {
                if ($condition === '#') {
                    if (!isset($params[$string])) {
                        throw new BException('Parameter is not set: ' . $string);
                    }
                    if (!is_int($params[$string])) {
                        throw new BException('Invalid qualifier parameter: ' . $params[$string]);
                    }
                    $value = $params[$string];
                    continue;
                }
                if (null === $value) {
                    throw new BException('Condition parameter should be specified first');
                }
                if ($condition === '*') { // if star, this is default
                    $defaultString = $string;
                    continue;
                }
                if (!preg_match('/^(\*)?([0-9]+)?(\.\.)?([0-9]+)?$/', $condition, $m) || ($m[2] === '' && $m[4] === '')) {
                    throw new BException('Invalid condition: ' . $condition);
                }
                $condValue = empty($m[1]) ? $value : ($value % 10); // if starts with star, modulo 10
                $valid = true;
                if (!empty($m[3])) { // range
                    if ($m[2] !== '' && $condValue < (int)$m[2]) { // lower bound breached
                        $valid = false;
                    }
                    if (isset($m[4]) && $condValue > (int)$m[4]) { // upper bound breached
                        $valid = false;
                    }
                } elseif ($condValue !== (int)$m[2]) { // single number doesn't match
                    $valid = false;
                }
                if ($valid) {
                    $tr = $string;
                    break;
                }
            }
            if (null === $tr) {
                $tr = $defaultString;
            }
        }

        return $this->BUtil->sprintfn($tr, $params);
    }

    public function tr_choice($choices, $property, array $params)
    {
        if (!isset($params[$property])) {
            throw new BException('Parameter is not set: ' . $property);
        }
        if (!is_int($params[$property])) {
            throw new BException('Invalid qualifier parameter: ' . $params[$property]);
        }
        $value = (int)$params[$property];
        $defaultString = '';
        foreach ($choices as $condition => $string) {
            if ($condition === '*') { // if star, this is default
                $defaultString = $string;
                continue;
            }
            if (!preg_match('/^(\*)?([0-9]+)?(\.\.)?([0-9]+)?$/', $condition, $m) || ($m[2] === '' && $m[4] === '')) {
                throw new BException('Invalid condition: ' . $condition);
            }
            $condValue = empty($m[1]) ? $value : ($value % 10); // if starts with star, modulo 10
            $valid = true;
            if (!empty($m[3])) { // range
                if ($m[2] !== '' && $condValue < (int)$m[2]) { // lower bound breached
                    $valid = false;
                }
                if (isset($m[4]) && $condValue > (int)$m[4]) { // upper bound breached
                    $valid = false;
                }
            } elseif ($condValue !== (int)$m[2]) { // single number matches
                $valid = false;
            }
            if ($valid) {
                return $this->_($string, $params);
            }
        }
        return $this->_($defaultString, $params);
    }

    public function translations($sources)
    {
        $results = [];
        if (is_array($sources)) {
            foreach ($sources as $string) {
                if (is_string($string)) {
                    $results[$string] = static::_($string);
                } else if (is_array($string) && !empty($string)) {
                    $str = (string) $string[0];
                    $params = isset($string[1]) ? (array) $string[1] : [];
                    $module = isset($string[2]) ? (string) $string[2] : null;
                    $results[$str] = static::_($str, $params, $module);
                }
            }
        } else {
            $results[(string) $sources] = static::_((string) $sources);
        }

        return $this->BUtil->toJson($results);
    }

    /*
    public function language($lang=null)
    {
        if (is_null($lang)) {
            return $this->_curLang;
        }
        putenv('LANGUAGE='.$lang);
        putenv('LANG='.$lang);
        setlocale(LC_ALL, $lang.'.utf8', $lang.'.UTF8', $lang.'.utf-8', $lang.'.UTF-8');
        return $this;
    }

    public function module($domain, $file=null)
    {
        if (is_null($file)) {
            if (!is_null($domain)) {
                $domain = static::$_domainPrefix.$domain;
                $oldDomain = textdomain(null);
                if ($oldDomain) {
                    array_push(static::$_domainStack, $domain!==$oldDomain ? $domain : false);
                }
            } else {
                $domain = array_pop(static::$_domainStack);
            }
            if ($domain) {
                textdomain($domain);
            }
        } else {
            $domain = static::$_domainPrefix.$domain;
            bindtextdomain($domain, $file);
            bind_textdomain_codeset($domain, "UTF-8");
        }
        return $this;
    }
    */

    /**
    * Translate a string and inject optionally named arguments
    *
    * @param string $string
    * @param array $args
    * @return string|false
    */
    /*
    public function translate($string, $args=array(), $domain=null)
    {
        if (!is_null($domain)) {
            $string = dgettext($domain, $string);
        } else {
            $string = gettext($string);
        }
        return $this->BUtil->sprintfn($string, $args);
    }
    */

    public function tzOptions($nested = false)
    {
        $allZones = timezone_identifiers_list();
        asort($allZones);
        $zones = [];
        if ($nested) {
            foreach ($allZones as $zone) {
                $z = explode('/', $zone, 2);
                if (empty($z[1])) {
                    $zones[$z[0]] = $z[0];
                } else {
                    $zones['@' . $z[0]][$zone] = str_replace(['_', '/'], [' ', ' / '], $zone);
                }
            }
        } else {
            foreach ($allZones as $zone) {
                $zones[$zone] = str_replace(['_', '/'], [' ', ' / '], $zone);
            }
        }
        return $zones;
    }

    /**
    * Get server timezone
    *
    * @return string
    */
    public function serverTz()
    {
        return date('e'); // Examples: UTC, GMT, Atlantic/Azores
    }

    /**
    * Get timezone offset in seconds
    *
    * @param stirng|null $tz If null, return server timezone offset
    * @return int
    */
    public function tzOffset($tz = null)
    {
        if (null === $tz) { // Server timezone
            return date('O') * 36; //  x/100*60*60; // Seconds from GMT
        }
        if (empty($this->_tzCache[$tz])) {
            $this->_tzCache[$tz] = new DateTimeZone($tz);
        }
        return $this->_tzCache[$tz]->getOffset($this->_tzCache['UTC']);
    }

    /**
    * Convert local datetime to DB (GMT)
    *
    * @param string $value
    * @return string
    */
    public function datetimeLocalToDb($value)
    {
        if (is_array($value)) {
            return array_map([$this, __METHOD__], $value);
        }
        if (!$value) return $value;
        return gmstrftime('%F %T', strtotime($value));
    }

    /**
    * Parse user formatted dates into db style within object or array
    *
    * @param array|object $request fields to be parsed
    * @param null|string|array $fields if null, all fields will be parsed, if string, will be split by comma
    * @return array|object clone of $request with parsed dates
    */
    public function parseRequestDates($request, $fields = null)
    {
        if (is_string($fields)) $fields = explode(',', $fields);
        $isObject = is_object($request);
        if ($isObject) $result = clone $request;
        foreach ($request as $k => $v) {
            if (null === $fields || in_array($k, $fields)) {
                $r = $this->datetimeLocalToDb($v);
            } else {
                $r = $v;
            }
            if ($isObject) $result->$k = $r; else $result[$k] = $r;
        }
        return $result;
    }

    /**
    * Convert DB datetime (GMT) to local
    *
    * @param string $value
    * @param bool $full Full format or short
    * @return string
    */
    public function datetimeDbToLocal($value, $full = false)
    {
        return strftime($full ? '%c' : '%x', strtotime($value) + $this->tzOffset());
    }

    public function getTranslations()
    {
        return static::$_tr;
    }

    static protected $_currencySymbolMap = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'CAD' => 'C$',
        'AUD' => 'A$',
    ];
    static protected $_currencyCode = 'USD';
    static protected $_currencySymbol = '$';

    public function setCurrency($code, $symbol = null)
    {
        static::$_currencyCode = $code;
        if (null === $symbol) {
            if (!empty(static::$_currencySymbolMap[$code])) {
                $symbol = static::$_currencySymbolMap[$code];
            } else {
                $symbol = $code . ' ';
            }
        }
        static::$_currencySymbol = $symbol;
    }

    public function getCurrencyCode()
    {
        return static::$_currencyCode;
    }

    public function getSymbol($currency)
    {
        return !empty(static::$_currencySymbolMap[$currency]) ? static::$_currencySymbolMap[$currency] : false;
    }

    public function currency($value, $currency = null, $decimals = 2)
    {
        if ($currency) {
            $symbol = $this->getSymbol($currency);
        } else {
            $symbol = static::$_currencySymbol;
        }
        return sprintf('%s%s', $symbol, number_format($value, $decimals));
    }

    public function roundCurrency($value, $decimals = 2)
    {
        //TODO: currency specific number of digits
        $precision = pow(10, $decimals);
        return round($value * $precision) / $precision;
    }
}
