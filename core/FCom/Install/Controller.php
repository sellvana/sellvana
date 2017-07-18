<?php

/**
 * Class FCom_Install_Controller
 *
 * @property Sellvana_MarketClient_RemoteApi $Sellvana_MarketClient_RemoteApi
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Core_Model_Module $FCom_Core_Model_Module
 * @property Sellvana_MarketClient_Main $Sellvana_MarketClient_Main
 */
class FCom_Install_Controller extends FCom_Core_Controller_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) {
            return false;
        }

        $method = $this->BRequest->method();
        switch ($method) {
        case 'GET':
            $this->BLayout->applyTheme('FCom_Install');
            break;

        case 'POST':
            $sessW = $this->BSession->get('w');
            $w = $this->BRequest->post('w');
            $this->BSession->set('w', $sessW ? $this->BUtil->arrayMerge($sessW, $w) : $w);
            break;
        }

        return true;
    }

    public function message($msg, $type = 'success', $tag = 'install', $options = [])
    {
        if (is_array($msg)) {
            array_walk($msg, [$this->BLocale, 'translate']);
        } else {
            $msg = $this->_($msg);
        }
        $this->BSession->addMessage($msg, $type, $tag, $options);
        return $this;
    }

    public function action_index()
    {
        $this->BLayout->applyLayout('/');

        $errors = $this->BDebug->getCollectedErrors();
        $this->BLayout->getView('index')->errors = $errors;
    }

    public function action_index__POST()
    {
        $w = $this->BRequest->post('w');
        if (empty($w['agree']) || $w['agree'] !== 'Agree') {
            $this->message((('Please click (("I Agree")) checkbox before continuing with installation')), 'error', 'install');
            $this->BResponse->redirect('');
            return;
        }
        $redirectUrl = 'install/step1';
        if (!$this->BApp->m('FCom_Admin')) {
            $this->BResponse->redirect('install/download');
            /*
            $this->BResponse->startLongResponse();
            $modules = $this->Sellvana_MarketClient_RemoteApi->getModuleInstallInfo('Sellvana_VirtPackCoreEcom');
            $this->Sellvana_MarketClient_Main->downloadAndInstall($modules, true);
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
        if (!$this->BModuleRegistry->isLoaded('Sellvana_MarketClient')) {
            $this->BResponse->redirect('install_step1');
            return;
        }
        $this->BLayout->setRootView('marketclient/container');
        $data = $this->Sellvana_MarketClient_RemoteApi->getModuleInstallInfo('Sellvana_VirtPackCoreEcom');
        $modules = [];
        foreach ($data as $modName => $modInfo) {
            if ($this->BApp->m($modName) || in_array($modName, ['FCom_Core', 'FCom_Install', 'Sellvana_MarketClient'])) {
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

        if (!$this->BSession->get('w/db')) {
            $this->BSession->set('w/db', [
                'host'         => '127.0.0.1',
                'port'         => '3306',
                'dbname'       => 'sellvana',
                'username'     => 'root',
                'password'     => '',
                'table_prefix' => ''
            ]);
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
            $this->BSession->set('w/db/password', null);
            $this->BResponse->redirect('install/step2');
        } catch (Exception $e) {
            //print_r($e);
            $this->message($e->getMessage(), 'error', 'install');
            $this->BResponse->redirect('install/step1');
        }
    }

    public function action_step2()
    {
        $this->BDb->connect();
        $userHlp = $this->FCom_Admin_Model_User;
        if ($this->BDb->ddlTableExists($userHlp->table()) && ($user = $userHlp->orm('u')->find_one())) {
            $this->BResponse->redirect('install/step3');
            return;
        } else {
            $this->BApp->m('FCom_Admin')->run_status = BModule::LOADED; // for proper migration on some hosts
            $this->FCom_Core_Model_Module->init();
            $this->BMigrate->migrateModules(['FCom_Core', 'FCom_Admin'], true);
        }
        $this->view('step2')->set([
            'tz' => $this->BLocale->tzOptions(),
        ]);
        $this->BLayout->applyLayout('/step2');
        if (!$this->BSession->get('w/admin')) {
            $this->BSession->set('w/admin', ['username' => 'admin', 'password' => '', 'email' => '', 'firstname' => '', 'lastname' => '', 'tz' => date_default_timezone_get()]);
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
                ['tz', '@required'],
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
                throw new BException("Invalid form data: \n" . $this->BValidate->validateErrorsString());
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
            'debug_modes' => ['DEBUG' => (('DEBUG (default for alpha)')), /*'PRODUCTION' => 'PRODUCTION', */],
            'run_level_bundles' => ['all' => (('All Bundled')), 'min' => (('Minimal'))],
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
                        'Sellvana_MarketClient' => 'REQUESTED',
                        'Sellvana_FrontendThemeBootSimple' => 'REQUESTED',
                    ];
                    break;

                case 'all':
                    $runLevels = [
                        'Sellvana_VirtPackCoreEcom' => 'REQUESTED',
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
                    'theme' => 'Sellvana_FrontendThemeBootSimple',
                ],
            ],
            'cache' => [
                'default_backend' => $this->BCache->getFastestAvailableBackend(),
            ],
        ], true);

        $this->BConfig->writeConfigFiles();

        $this->BEvents->fire(__METHOD__ . ':after', ['data' => $w]);

        $this->BResponse->redirect($this->BApp->adminHref(''));
    }
}
