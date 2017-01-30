<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Main
 *
 * @property FCom_Admin_Model_Favorite FCom_Admin_Model_Favorite
 * @property Sellvana_MultiLanguage_Main Sellvana_MultiLanguage_Main
 */
class FCom_AdminSPA_AdminSPA_Controller_Main extends FCom_Admin_Controller_Abstract
{
    public function authenticate($args = [])
    {
        return true;
    }

    public function action_index()
    {
        $this->BDebug->disableAllLogging();
        $this->BDebug->disableDumpLog();
        $this->layout('/');
        /** @var FCom_AdminSPA_AdminSPA_View_App $app */
        $app = $this->view('app');
        $locales = $this->BLocale->getAvailableLocaleCodes();
        $countries = $this->BLocale->getAvailableCountries();
        $regions = $this->BLocale->getAvailableRegions();
        $user = $this->FCom_Admin_Model_User->sessionUser();

        $data = [
            'modules' => $app->getModules(),

            'routes' => $app->getRoutes(),
            //'navs' => $app->getNavs(),
            'nav_tree' => $app->getNavTree(),
            'env' => $app->getEnv(),

            'user' => $user ? $user->as_array() : false,

            'favorites' => $this->FCom_Admin_Model_Favorite->getUserFavorites(),

            'countries' => $countries,
            'countries_seq' => $this->BUtil->arrayMapToSeq($countries),

            'regions' => $regions,
            'regions_seq' => $this->BUtil->arrayMapToSeq($regions),

            'csrf_token' => $this->BSession->csrfToken(),
        ];

        if ($this->BModuleRegistry->isLoaded('Sellvana_MultiLanguage')) {
            $data['locales'] = $this->Sellvana_MultiLanguage_Main->getAllowedLocales();
            $data['locales_seq'] = $this->BUtil->arrayMapToSeq($data['locales']);
        }

        $this->view('root')->set(['data' => $data]);
    }

    public function action_l10n()
    {
        $result = <<<EOT
[en-US]
test = This is a test
test.title = click me!
[fr]
test = Ceci est un test
test.title = cliquez-moi !
EOT;
        $this->BResponse->setContentType('application/l10n')->set($result);
    }

    public function action_components()
    {
        $this->_getComponent();
    }

    public function action_components__POST()
    {
        $this->_getComponent();
    }

    protected function _getComponent()
    {
        $path = $this->BRequest->param(1) ?: $this->BRequest->param('path', true);
        $path = preg_replace('#[^a-zA-Z0-9_/-]#', '', $path);

        if (!$path) {
            $this->BResponse->status(404, 'Template not found', 'Template not found');
            return;
        }
        $view = $this->view('components/' . $path);
        $args = $this->BRequest->request('args');
        if ($args) {
            $view->set($this->BUtil->fromJson($args));
        }
        $this->BDebug->mode('PRODUCTION');
        $result = $view->render();
        $this->BResponse->set($result);
        return;

//        if (!$path) {
//            $result = ['error' => true, 'message' => $this->_('Invalid path')];
//        } else {
//            $view = $this->view('components/' . $path);
//            $args = $this->BRequest->request('args');
//            if ($args) {
//                $view->set($this->BUtil->fromJson($args));
//            }
//            $result = [
//                'template' => $view->render(),
//            ];
//        }
//        $script = 'define([], function() { return ' . $this->BUtil->toJson($result) . ' });';
//        $this->BResponse->setContentType('text/javascript')->set($script);
    }
}