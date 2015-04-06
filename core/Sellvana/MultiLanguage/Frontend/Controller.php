<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiLanguage_Frontend_Controller
 *
 * @property Sellvana_MultiLanguage_Main $Sellvana_MultiLanguage_Main
 */
class Sellvana_MultiLanguage_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_switch()
    {
        $ref = $this->BRequest->referrer();
        $locale = $this->BRequest->param('locale', true);
        $locales = $this->Sellvana_MultiLanguage_Main->getAllowedLocales();
        if (in_array($locale, $locales)) {
            $langInUrl = $this->BConfig->get('web/language_in_url');
            $curLocale = $this->BLocale->getCurrentLocale();
            list($curLang) = explode('_', $curLocale, 2);
            switch ($langInUrl) {
                case 'lang':
                    list($lang) = explode('_', $locale, 2);
                    $ref = preg_replace("#/{$curLang}(/|$)#", "/{$lang}/", $ref);
                    break;

                case 'locale':
                    $ref = preg_replace("#/{$curLocale}(/|$)", "/{$locale}/", $ref);
                    break;
            }
            $this->BLocale->setCurrentLocale($locale);
        }
        $this->BResponse->redirect($ref);
    }
}