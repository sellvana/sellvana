<?php

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
        $countries = $this->BLocale->getAvailableCountries();
        $regions = $this->BLocale->getAvailableRegions();
        $user = $this->FCom_Admin_Model_User->sessionUser();
        $this->view('root')->set(['data' => [
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
        ]]);
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

    public function action_sv_app_dynamic_js()
    {
        $this->BDebug->mode('PRODUCTION');
        $this->layout('sv-app-dynamic-js');
        $html = (string)$this->view('js/sv-app-dynamic-js');
        $script = str_replace(['<script>', '</script>'], '', $html);
        $this->BResponse->setContentType('application/javascript')->set($script);
    }

    public function action_sv_app_data()
    {
        /*
{% set app = THIS.view('app') %}
{% set modules = app.getModules() %}
{% set routes = app.getRoutes() %}
{% set navs = app.getNavs() %}
{% set navTree = app.getNavTree() %}
{% set env = app.getEnv() %}
{% set user = APP.instance('FCom_Admin_Model_User').sessionUser() %}
{% set favorites = APP.instance('FCom_Admin_Model_Favorite').getUserFavorites() %}
{% set pers = APP.instance('FCom_Admin_Model_Personalize') %}
{% set countries = LOCALE.getAvailableCountries() %}
{% set countriesSeq = UTIL.arrayMapToSeq(countries) %}
{% set regions = LOCALE.getAvailableRegions() %}
{% set regionsSeq = UTIL.arrayMapToSeq(regions, 'id', 'text', 1) %}
         */
        $this->BDebug->mode('PRODUCTION');
        $this->respond($result);
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