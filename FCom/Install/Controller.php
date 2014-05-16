<?php

class FCom_Install_Controller extends FCom_Core_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $method = BRequest::i()->method();
        switch ($method) {
        case 'GET':
            BLayout::i()->applyTheme('FCom_Install');
            break;

        case 'POST':
            $sData =& BSession::i()->dataToUpdate();
            $w = BRequest::i()->post('w');
            $sData['w'] = !empty($sData['w']) ? BUtil::arrayMerge($sData['w'], $w) : $w;
            break;
        }

        return true;
    }

    public function message($msg, $type = 'success', $tag = 'install', $options = [])
    {
        if (is_array($msg)) {
            array_walk($msg, 'BLocale::_');
        } else {
            $msg = BLocale::_($msg);
        }
        BSession::i()->addMessage($msg, $type, $tag, $options);
        return $this;
    }

    public function action_index()
    {
        BLayout::i()->applyLayout('/');

        $errors = BDebug::i()->getCollectedErrors();
        BLayout::i()->view('index')->errors = $errors;
    }

    public function action_index__POST()
    {
        $w = BRequest::i()->post('w');
        if (empty($w['agree']) || $w['agree'] !== 'Agree') {
            $this->message('Please click "I Agree" checkbox before continuing with installation', 'error', 'install');
            BResponse::i()->redirect('');
            return;
        }
        $redirectUrl = 'install/step1';
        if (!BApp::m('FCom_Admin')) {
            BResponse::i()->redirect('install/download');
            /*
            BResponse::i()->startLongResponse();
            $modules = FCom_MarketClient_RemoteApi::i()->getModuleInstallInfo('FCom_VirtPackCoreEcom');
            FCom_MarketClient_Main::i()->downloadAndInstall($modules, true);
            echo '<script>location.href="'.$redirectUrl.'";</script>';
            echo '<p>ALL DONE. <a href="'.$redirectUrl.'">Click here to continue</a></p>';
            exit;
            */
        } else {
            BResponse::i()->redirect($redirectUrl);
        }
    }

    public function action_download()
    {
        BLayout::i()->setRootView('marketclient/container');
        $data = FCom_MarketClient_RemoteApi::i()->getModuleInstallInfo('FCom_VirtPackCoreEcom');
        $modules = [];
        foreach ($data as $modName => $modInfo) {
            if (BApp::m($modName) || in_array($modName, ['FCom_Core', 'FCom_Install', 'FCom_MarketClient'])) {
                continue;
            }
            $modules[$modName] = $modInfo['version'];
        }
        $this->view('marketclient/container')->set([
            'modules' => $modules,
            'redirect_to' => BApp::href('install/step1'),
        ]);
    }

    public function action_step1()
    {
        BLayout::i()->applyLayout('/step1');
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['w']['db'])) {
            $sData['w']['db'] = ['host' => '127.0.0.1', 'dbname' => 'sellvana', 'username' => 'root', 'password' => '', 'table_prefix' => ''];
        }
    }

    public function action_step1__POST()
    {
        if (BRequest::i()->post('do') === 'back') {
            BResponse::i()->redirect('install/index');
            return;
        }
        try {
            $w = BRequest::i()->post('w');
            BConfig::i()->add(['db' => $w['db']], true);
            BDb::connect(null, true);
            FCom_Core_Main::i()->writeConfigFiles('db');
            BResponse::i()->redirect('install/step2');
        } catch (Exception $e) {
            //print_r($e);
            $this->message($e->getMessage(), 'error', 'install');
            BResponse::i()->redirect('install/step1');
        }
    }

    public function action_step2()
    {
        $userHlp = FCom_Admin_Model_User::i();
        if (BDb::ddlTableExists($userHlp->table()) && $userHlp->orm('u')->find_one()) {
            BResponse::i()->redirect('install/step3');
            return;
        } else {
            BApp::m('FCom_Admin')->run_status = BModule::LOADED; // for proper migration on some hosts
            BDb::connect();
            FCom_Core_Model_Module::i()->init();
            BMigrate::i()->migrateModules('FCom_Admin', true);
        }
        BLayout::i()->applyLayout('/step2');
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['w']['admin'])) {
            $sData['w']['admin'] = ['username' => 'admin', 'password' => '', 'email' => '', 'firstname' => '', 'lastname' => ''];
        }
    }

    public function action_step2__POST()
    {
        if (BRequest::i()->post('do') === 'back') {
            BResponse::i()->redirect('install/step1');
            return;
        }
        try {
            $w = BRequest::i()->post('w');
            BMigrate::i()->migrateModules('FCom_Admin', true);
            FCom_Admin_Model_User::i()
                ->create($w['admin'])
                ->set('is_superadmin', 1)
                ->save()
                ->login();
            BResponse::i()->redirect('install/step3');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error', 'install');
            BResponse::i()->redirect('install/step2');
        }
    }

    public function action_step3()
    {
        $this->view('step3')->set([
            'debug_modes' => ['DEBUG' => 'DEBUG', /*'PRODUCTION' => 'PRODUCTION', */],
            'run_level_bundles' => ['all' => 'All Bundled', 'min' => 'Minimal'],
        ]);
        BLayout::i()->applyLayout('/step3');
    }

    public function action_step3__POST()
    {
        if (BRequest::i()->post('do') === 'back') {
            BResponse::i()->redirect('install/step2');
            return;
        }

        $w = BRequest::i()->post('w');
        $runLevels = [];
        if (!empty($w['config']['run_levels_bundle'])) {
            switch ($w['config']['run_levels_bundle']) {
                case 'min':
                    $runLevels = [
                        'FCom_MarketClient' => 'REQUESTED',
                        'FCom_FrontendThemeBootSimple' => 'REQUESTED',
                    ];
                    break;

                case 'all':
                    $runLevels = [
                        'FCom_Api' => 'REQUESTED',
                        'FCom_AuthorizeNet' => 'REQUESTED',
                        'FCom_Catalog' => 'REQUESTED',
                        'FCom_CatalogIndex' => 'REQUESTED',
                        'FCom_Checkout' => 'REQUESTED',
                        'FCom_Cms' => 'REQUESTED',
                        'FCom_Cron' => 'REQUESTED',
                        'FCom_Customer' => 'REQUESTED',
                        'FCom_CustomerGroups' => 'REQUESTED',
                        'FCom_CustomField' => 'REQUESTED',
                        'FCom_CustomModule' => 'REQUESTED',
                        'FCom_Disqus' => 'REQUESTED',
                        'FCom_EasyPost' => 'REQUESTED',
                        'FCom_Email' => 'REQUESTED',
                        'FCom_FreshBooks' => 'REQUESTED',
                        //'FCom_FrontendCP' => 'REQUESTED',
                        'FCom_FrontendThemeBootSimple' => 'REQUESTED',
                        'FCom_Geo' => 'REQUESTED',
                        'FCom_MarketClient' => 'REQUESTED',
                        'FCom_MultiCurrency' => 'REQUESTED',
                        'FCom_MultiLanguage' => 'REQUESTED',
                        'FCom_MultiSite' => 'REQUESTED',
                        'FCom_MultiVendor' => 'REQUESTED',
                        'FCom_MultiWarehouse' => 'REQUESTED',
                        'FCom_Ogone' => 'REQUESTED',
                        'FCom_PaymentBasic' => 'REQUESTED',
                        'FCom_PaymentCC' => 'REQUESTED',
                        'FCom_PayPal' => 'REQUESTED',
                        'FCom_ProductReviews' => 'REQUESTED',
                        'FCom_Promo' => 'REQUESTED',
                        'FCom_PushServer' => 'REQUESTED',
                        'FCom_Sales' => 'REQUESTED',
                        'FCom_Seo' => 'REQUESTED',
                        'FCom_ShippingPlain' => 'REQUESTED',
                        'FCom_ShippingUps' => 'REQUESTED',
                        'FCom_Test' => 'REQUESTED',
                        'FCom_Wishlist' => 'REQUESTED',
                    ];
                    break;
            }
        }

        BConfig::i()->add([
            'install_status' => 'installed',
            'db' => ['implicit_migration' => 1/*, 'currently_migrating' => 0*/],
            'module_run_levels' => ['FCom_Core' => $runLevels],
            'mode_by_ip' => [
                'FCom_Frontend' => !empty($w['config']['run_mode_frontend']) ? $w['config']['run_mode_frontend'] : 'DEBUG',
                'FCom_Admin' => !empty($w['config']['run_mode_admin']) ? $w['config']['run_mode_admin'] : 'DEBUG',
            ],
            'modules' => [
                'FCom_Frontend' => [
                    'theme' => 'FCom_FrontendThemeBootSimple',
                ],
            ],
            'cache' => [
                'default_backend' => BCache::i()->getFastestAvailableBackend(),
            ],
        ], true);

        FCom_Core_Main::i()->writeConfigFiles();

        BResponse::i()->redirect(BApp::i()->adminHref(''));
    }
}
