<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Install_Controller
 *
 * @property FCom_MarketClient_RemoteApi $FCom_MarketClient_RemoteApi
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 * @property FCom_MarketClient_Main $FCom_MarketClient_Main
 */
class FCom_Install_Controller extends FCom_Core_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $method = $this->BRequest->method();
        switch ($method) {
        case 'GET':
            $this->BLayout->applyTheme('FCom_Install');
            break;

        case 'POST':
            $sData =& $this->BSession->dataToUpdate();
            $w = $this->BRequest->post('w');
            $sData['w'] = !empty($sData['w']) ? $this->BUtil->arrayMerge($sData['w'], $w) : $w;
            break;
        }

        return true;
    }

    public function message($msg, $type = 'success', $tag = 'install', $options = [])
    {
        if (is_array($msg)) {
            array_walk($msg, [$this->BLocale, '_']);
        } else {
            $msg = $this->BLocale->_($msg);
        }
        $this->BSession->addMessage($msg, $type, $tag, $options);
        return $this;
    }

    public function action_index()
    {
        $this->BLayout->applyLayout('/');

        $errors = $this->BDebug->getCollectedErrors();
        $this->BLayout->view('index')->errors = $errors;
    }

    public function action_index__POST()
    {
        $w = $this->BRequest->post('w');
        if (empty($w['agree']) || $w['agree'] !== 'Agree') {
            $this->message('Please click "I Agree" checkbox before continuing with installation', 'error', 'install');
            $this->BResponse->redirect('');
            return;
        }
        $redirectUrl = 'install/step1';
        if (!$this->BApp->m('FCom_Admin')) {
            $this->BResponse->redirect('install/download');
            /*
            $this->BResponse->startLongResponse();
            $modules = $this->FCom_MarketClient_RemoteApi->getModuleInstallInfo('FCom_VirtPackCoreEcom');
            $this->FCom_MarketClient_Main->downloadAndInstall($modules, true);
            echo '<script>location.href="'.$redirectUrl.'";</script>';
            echo '<p>ALL DONE. <a href="'.$redirectUrl.'">Click here to continue</a></p>';
            exit;
            */
        } else {
            $this->BResponse->redirect($redirectUrl);
        }
    }

    public function action_download()
    {
        $this->BLayout->setRootView('marketclient/container');
        $data = $this->FCom_MarketClient_RemoteApi->getModuleInstallInfo('FCom_VirtPackCoreEcom');
        $modules = [];
        foreach ($data as $modName => $modInfo) {
            if ($this->BApp->m($modName) || in_array($modName, ['FCom_Core', 'FCom_Install', 'FCom_MarketClient'])) {
                continue;
            }
            $modules[$modName] = $modInfo['version'];
        }
        $this->view('marketclient/container')->set([
            'modules' => $modules,
            'redirect_to' => $this->BApp->href('install/step1'),
        ]);
    }

    public function action_step1()
    {
        $this->BLayout->applyLayout('/step1');
        $sData =& $this->BSession->dataToUpdate();
        if (empty($sData['w']['db'])) {
            $sData['w']['db'] = [
                'host'         => '127.0.0.1',
                'port'         => '3306',
                'dbname'       => 'sellvana',
                'username'     => 'root',
                'password'     => '',
                'table_prefix' => ''
            ];
        }
    }

    public function action_step1__POST()
    {
        if ($this->BRequest->post('do') === 'back') {
            $this->BResponse->redirect('install/index');
            return;
        }
        try {
            $w = $this->BRequest->post('w');
            if (empty($w['db']) || !$this->BValidate->validateInput($w['db'], [
                ['host', '@required'],
                ['host', '/^[A-Za-z0-9.\[\]:-]+$/'],
                ['port', '@required'],
                ['port', '@numeric'],
                ['dbname', '@required'],
                ['dbname', '/^[A-Za-z0-9_]+$/'],
                ['username', '@required'],
                ['username', '/^[A-Za-z0-9_]+$/'],
                ['table_prefix', '/^[A-Za-z0-9_]+$/'],
            ])) {
                throw new BException('Invalid form data');
            }
            $this->BConfig->add(['db' => $w['db']], true);
            $this->BDb->connect(null, true);
            $this->BConfig->writeConfigFiles('db');
            $sData =& $this->BSession->dataToUpdate();
            unset($sData['w']['db']['password']);
            $this->BResponse->redirect('install/step2');
        } catch (Exception $e) {
            //print_r($e);
            $this->message($e->getMessage(), 'error', 'install');
            $this->BResponse->redirect('install/step1');
        }
    }

    public function action_step2()
    {
        $userHlp = $this->FCom_Admin_Model_User;
        if ($this->BDb->ddlTableExists($userHlp->table()) && $userHlp->orm('u')->find_one()) {
            $this->BResponse->redirect('install/step3');
            return;
        } else {
            $this->BApp->m('FCom_Admin')->run_status = BModule::LOADED; // for proper migration on some hosts
            $this->BDb->connect();
            $this->FCom_Core_Model_Module->init();
            $this->BMigrate->migrateModules('FCom_Admin', true);
        }
        $this->BLayout->applyLayout('/step2');
        $sData =& $this->BSession->dataToUpdate();
        if (empty($sData['w']['admin'])) {
            $sData['w']['admin'] = ['username' => 'admin', 'password' => '', 'email' => '', 'firstname' => '', 'lastname' => ''];
        }
    }

    public function action_step2__POST()
    {
        if ($this->BRequest->post('do') === 'back') {
            $this->BResponse->redirect('install/step1');
            return;
        }
        try {
            $w = $this->BRequest->post('w');
            if (empty($w['admin']) || !$this->BValidate->validateInput($w['admin'], [
                ['firstname', '@required'],
                ['lastname', '@required'],
                ['email', '@required'],
                ['email', '@email'],
                ['username', '@required'],
                ['username', '/^[A-Za-z0-9_.@-]+$/'],
                ['password', '@required'],
                ['password_confirm', '@required'],
                ['password_confirm', '@password_confirm'],
            ])) {
                throw new BException('Invalid form data');
            }
            $this->BMigrate->migrateModules('FCom_Admin', true);
            $this->FCom_Admin_Model_User
                ->create($w['admin'])
                ->set('is_superadmin', 1)
                ->save()
                ->login();
            $this->BResponse->redirect('install/step3');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error', 'install');
            $this->BResponse->redirect('install/step2');
        }
    }

    public function action_step3()
    {
        $this->view('step3')->set([
            'debug_modes' => ['DEBUG' => 'DEBUG', /*'PRODUCTION' => 'PRODUCTION', */],
            'run_level_bundles' => ['all' => 'All Bundled', 'min' => 'Minimal'],
        ]);
        $this->BLayout->applyLayout('/step3');
    }

    public function action_step3__POST()
    {
        if ($this->BRequest->post('do') === 'back') {
            $this->BResponse->redirect('install/step2');
            return;
        }

        $w = $this->BRequest->post('w');
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
                        'FCom_ProductCompare' => 'REQUESTED',
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

        $this->BConfig->add([
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
                'default_backend' => $this->BCache->getFastestAvailableBackend(),
            ],
        ], true);

        $this->BConfig->writeConfigFiles();

        $this->BResponse->redirect($this->BApp->adminHref(''));
    }
}
