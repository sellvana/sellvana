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

    public function action_index()
    {
        BLayout::i()->applyLayout('/');

        $errors = BDebug::i()->getCollectedErrors();
        BLayout::i()->view('index')->errors = $errors;

        $this->messages('root', 'install');
    }

    public function action_index__POST()
    {
        $sData = BSession::i()->data();
        if (empty($sData['w']['agree']) || $sData['w']['agree']!=='Agree') {
            BResponse::i()->redirect(BApp::href('?error=1'));
        }
        BResponse::i()->redirect(BApp::href('install/step1'));
    }

    public function action_step1()
    {
        BLayout::i()->applyLayout('/step1');
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['w']['db'])) {
            $sData['w']['db'] = array('host'=>'127.0.0.1', 'dbname'=>'fulleron', 'username'=>'root', 'password'=>'', 'table_prefix'=>'');
        }
        $this->messages('root', 'install');
    }

    public function action_step1__POST()
    {
        if (BRequest::i()->post('do')==='back') {
            BResponse::i()->redirect('install/index');
        }
        try {
            $w = BRequest::i()->post('w');
            BConfig::i()->add(array('db'=>$w['db']), true);
            FCom_Core_Main::i()->writeConfigFiles('db');

            if (class_exists('FCom_Admin_Model_User') && BDb::ddlTableExists(FCom_Admin_Model_User::table())
                && FCom_Admin_Model_User::i()->orm('u')->find_one()
            ) {
                if (BConfig::i()->get('install_status')==='installed') {
                    $url = BApp::href();
                } else {
                    $url = BApp::href('install/step3');
                }
            } else {
                BMigrate::i()->migrateModules('FCom_Admin');
                $url = BApp::href('install/step2');
            }
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'install');
            $url = BApp::href('install/step1');
        }
        BResponse::i()->redirect($url);
    }

    public function action_step2()
    {
        BLayout::i()->applyLayout('/step2');
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['w']['admin'])) {
            $sData['w']['admin'] = array('username'=>'admin', 'password'=>'', 'email'=>'', 'firstname'=>'', 'lastname'=>'');
        }
        $this->messages('root', 'install');
    }

    public function action_step2__POST()
    {
        if (BRequest::i()->post('do')==='back') {
            BResponse::i()->redirect('install/step1');
        }
        try {
            $w = BRequest::i()->post('w');
            BMigrate::i()->migrateModules('FCom_Admin');
            FCom_Admin_Model_User::i()
                ->create($w['admin'])
                ->set('is_superadmin', 1)
                ->save()
                ->login();
            $url = BApp::href('install/step3');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'install');
            $url = BApp::href('install/step2');
        }
        BResponse::i()->redirect($url);
    }

    public function action_step3()
    {
        $this->view('step3')->set(array(
            'debug_modes' => array('PRODUCTION' => 'PRODUCTION', 'DEBUG' => 'DEBUG'),
            'run_level_bundles' => array('min' => 'Minimal', 'all' => 'All Bundled'),
        ));
        BLayout::i()->applyLayout('/step3');
        $this->messages('root', 'install');
    }

    public function action_step3__POST()
    {
        if (BRequest::i()->post('do')==='back') {
            BResponse::i()->redirect('install/step2');
        }

        $w = BRequest::i()->post('w');
        $runLevels = array();
        if (!empty($w['config']['run_levels_bundle'])) {
            switch ($w['config']['run_levels_bundle']) {
                case 'min':
                    $runLevels = array(
                        'FCom_MarketClient' => 'REQUESTED',
                        'FCom_FrontendThemeBlue' => 'REQUESTED',
                    );
                    break;

                case 'all':
                    $runLevels = array(
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
                        'FCom_Disqus' => 'REQUESTED',
                        'FCom_EasyPost' => 'REQUESTED',
                        'FCom_Email' => 'REQUESTED',
                        'FCom_FreshBooks' => 'REQUESTED',
                        'FCom_FrontendCP' => 'REQUESTED',
                        'FCom_FrontendThemeBlue' => 'REQUESTED',
                        'FCom_FrontendThemeBootstrap' => 'REQUESTED',
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
                    );
                    break;
            }
        }
        BConfig::i()->add(array(
            'install_status' => 'installed',
            'db' => array('implicit_migration' => 1),
            'module_run_levels' => array('FCom_Core' => $runLevels),
            'mode_by_ip' => array(
                'FCom_Frontend' => !empty($w['config']['run_mode_frontend']) ? $w['config']['run_mode_frontend'] : 'PRODUCTION',
                'FCom_Admin' => !empty($w['config']['run_mode_admin']) ? $w['config']['run_mode_admin'] : 'PRODUCTION',
            ),
        ), true);
        FCom_Core_Main::i()->writeConfigFiles();
        BResponse::i()->redirect(BApp::baseUrl());
    }
}
