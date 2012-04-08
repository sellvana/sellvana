<?php debug_backtrace() || exit;

define('FULLERON_ROOT_DIR', dirname(__DIR__));
set_time_limit(2);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

class FCom extends BClass
{
    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    static public function area()
    {
        return BApp::i()->get('area');
    }

    static public function rootDir()
    {
        return FULLERON_ROOT_DIR;
    }

    public function init($area)
    {
        try {
            if (BRequest::i()->csrf()) {
                BResponse::i()->status(403, 'Possible CSRF detected', 'Possible CSRF detected');
            }

            // initialize start time and register error/exception handlers
            BDebug::i()->registerErrorHandlers();

            $this->initConfig($area);
            $this->initDebug();
            $this->initModules();

            return BApp::i();

        } catch (Exception $e) {
            BDebug::dumpLog();
            BDebug::exceptionHandler($e);
        }
    }

    public function initConfig($area)
    {
        $config = BConfig::i();

        $localConfig = array();
        $localConfig['fcom_root_dir'] = FULLERON_ROOT_DIR;

        $rootDir = $config->get('fs/root_dir');
        if (!$rootDir) {
            $localConfig['fs']['root_dir'] = $rootDir = FULLERON_ROOT_DIR;
        }

        BDebug::debug('ROOTDIR='.$rootDir);

        $baseSrc = $config->get('web/base_src');
        if (!$baseSrc) {
            $baseSrc = BRequest::i()->webRoot();
            $localConfig['web']['base_src'] = $baseSrc;
        }
        $baseHref = $config->get('web/base_href');
        if (!$baseHref) {
            $baseHref = BRequest::i()->webRoot();
            $localConfig['web']['base_href'] = $baseHref;
        }
        if (!$config->get('web/base_store')) {
            $localConfig['web']['base_store'] = $baseHref;
        }

        $storageDir = $config->get('fs/storage_dir');
        if (!$storageDir) {
            $storageDir = $rootDir.'/storage';
            $config->set('fs/storage_dir', $storageDir);
        }

        // local configuration (db, enabled modules)
        $configDir = $config->get('fs/config_dir');
        if (!$configDir) {
            $configDir = $storageDir.'/config';
            $config->set('fs/config_dir', $configDir);
        }

        // DB configuration is separate to gitignore
        // used as indication that app is already installed and setup
        $configFileStatus = true;
        if (file_exists($configDir.'/db.php')) {
            $config->addFile('db.php', true);
        } else {
            $configFileStatus = false;
        }
        if (file_exists($configDir.'/local.php')) {
            $config->addFile('local.php', true);
        } else {
            $configFileStatus = false;
        }
        if (!$configFileStatus || $config->get('install_status')!=='installed') {
            $area = 'FCom_Install';
        }
#echo "<Pre>"; print_r($config->get()); exit;
        // add area module
        BApp::i()->set('area', $area, true);

        $config->add($localConfig);

        return $this;
    }

    public function initDebug()
    {
        #BDebug::mode('production');
        #BDebug::mode('development');
        #BDebug::mode('debug');

        $config = BConfig::i();
        // Initialize debugging mode and levels
        BDebug::logDir($config->get('fs/storage_dir').'/log');
        BDebug::adminEmail($config->get('admin_email'));

        if (($debugConfig = $config->get('debug'))) {
            if (!empty($debugConfig['ip']) && ($ip = BRequest::i()->ip()) && !empty($debugConfig['ip'][$ip])) {
                BDebug::mode($debugConfig['ip'][$ip]);
            } elseif (!empty($debugConfig['mode'])) {
                BDebug::mode($debugConfig['mode']);
            }
            if (!empty($debugConfig['levels'])) {
                foreach ($debugConfig['levels'] as $type=>$level) {
                    BDebug::level($type, $level);
                }
            }
        }
#print_r(BDebug::mode());
        return $this;
    }

    public function initModules()
    {
        $config = BConfig::i();

        $runLevels = array(static::area() => 'REQUIRED');
        if (BDebug::is('RECOVERY')) { // load manifests for RECOVERY mode
            $recoveryModules = BConfig::i()->get('modules/FCom_Core/recovery_modules');
            if ($recoveryModules) {
                $moduleNames = preg_split('#\s*(,|\n)\s*#', $recoveryModules);
                foreach ($moduleNames as $modName) {
                    $runLevels[$modName] = 'REQUESTED';
                }
            }
        } else { // load all manifests
            $runLevels += (array)$config->get('request/module_run_level') +
                (array)$config->get('modules/'.static::area().'/module_run_level') +
                (array)$config->get('modules/FCom_Core/module_run_level');
        }
        $config->add(array('request'=>array('module_run_level'=>$runLevels)));

        $this->registerBundledModules();
#$d = BDebug::debug('SCANNING MANIFESTS');
        if (defined('BUCKYBALL_ROOT_DIR')) { // if minified version used, load plugins manually
            BModuleRegistry::i()->scan(BUCKYBALL_ROOT_DIR.'/plugins');
        }
        BModuleRegistry::i()
            ->scan(FULLERON_ROOT_DIR.'/market/*')
            ->scan(FULLERON_ROOT_DIR.'/local/*');
#BDebug::profile($d);

        $rootDir = $config->get('fs/root_dir');
        BClassAutoload::i(true, array('root_dir'=>$rootDir.'/local'));
        BClassAutoload::i(true, array('root_dir'=>$rootDir.'/market'));
        BClassAutoload::i(true, array('root_dir'=>FULLERON_ROOT_DIR));

        return $this;
    }

    public function run($area)
    {
        $this->init($area);
        try {
            BApp::i()->run();
        } catch (Exception $e) {
            BDebug::dumpLog();
            BDebug::exceptionHandler($e);
        }
    }

    public function registerBundledModules()
    {
        BModuleRegistry::i()
            // Core logic, abstract classes, all models
            ->addModule('FCom_Core', array(
                'version' => '0.1.0',
                'root_dir' => 'Core',
                'bootstrap' => array('file'=>'Core.php', 'callback'=>'FCom_Core::bootstrap'),
                'run_level' => BModule::REQUIRED,
                'description' => "Base Fulleron classes and JS libraries",
            ))
            // Initial installation module
            ->addModule('FCom_Install', array(
                'version' => '0.1.0',
                'root_dir' => 'Install',
                'bootstrap' => array('file'=>'Install.php', 'callback'=>'FCom_Install::bootstrap'),
                'depends' => array('FCom_Core', 'FCom_Admin'),
                'description' => "Initial installation wizard",
            ))
            // Frontend collection of modules
            ->addModule('FCom_Frontend', array(
                'version' => '0.1.0',
                'root_dir' => 'Frontend',
                'bootstrap' => array('file'=>'Frontend.php', 'callback'=>'FCom_Frontend::bootstrap'),
                'depends' => array('FCom_Core', 'FCom_Frontend_DefaultTheme'),
                'description' => "Base frontend functionality",
            ))
            // Frontend collection of modules
            ->addModule('FCom_Frontend_DefaultTheme', array(
                'version' => '0.1.0',
                'root_dir' => 'Frontend',
                'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Frontend_DefaultTheme::bootstrap'),
                'depends' => array('FCom_Core'),
                'description' => "Default frontend theme",
            ))
            // administration panel views and controllers
            ->addModule('FCom_Admin', array(
                'version' => '0.1.1',
                'root_dir' => 'Admin',
                'bootstrap' => array('file'=>'Admin.php', 'callback'=>'FCom_Admin::bootstrap'),
                'depends' => array('FCom_Core', 'FCom_Admin_DefaultTheme'),
                'description' => "Base admin functionality",
            ))
            // Frontend collection of modules
            ->addModule('FCom_Admin_DefaultTheme', array(
                'version' => '0.1.0',
                'root_dir' => 'Admin',
                'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Admin_DefaultTheme::bootstrap'),
                'depends' => array('FCom_Core'),
                'description' => "Default admin theme",
            ))
            // cron jobs processing
            ->addModule('FCom_Cron', array(
                'version' => '0.1.0',
                'root_dir' => 'Cron',
                'bootstrap' => array('file'=>'Cron.php', 'callback'=>'FCom_Cron::bootstrap'),
                'depends' => array('FCom_Core'),
                'description' => "Cron scheduled tasks manager",
            ))
            // catalog views and controllers
            ->addModule('FCom_Cms', array(
                'version' => '0.1.0',
                'root_dir' => 'Cms',
                'depends' => array('FCom_Core', 'BPHPTAL'),
                'description' => "CMS for custom pages and forms",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'CmsAdmin.php', 'callback'=>'FCom_Cms_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'CmsFrontend.php', 'callback'=>'FCom_Cms_Frontend::bootstrap'),
                    ),
                ),
            ))
            // product reviews
            ->addModule('FCom_ProductReviews', array(
                'version' => '0.1.0',
                'root_dir' => 'ProductReviews',
                'depends' => array('FCom_Catalog', 'FCom_Customer'),
                'description' => "Product reviews by customers",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'ProductReviewsAdmin.php', 'callback'=>'FCom_ProductReviews_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'ProductReviewsFrontend.php', 'callback'=>'FCom_ProductReviews_Frontend::bootstrap'),
                    ),
                ),
            ))
            // catalog views and controllers
            ->addModule('FCom_Catalog', array(
                'version' => '0.1.0',
                'root_dir' => 'Catalog',
                'depends' => array('FCom_Core'),
                'description' => "Categories and products management, admin and frontend",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'CatalogAdmin.php', 'callback'=>'FCom_Catalog_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'CatalogFrontend.php', 'callback'=>'FCom_Catalog_Frontend::bootstrap'),
                    ),
                ),
            ))
            // customer account and management
            ->addModule('FCom_Customer', array(
                'version' => '0.1.0',
                'root_dir' => 'Customer',
                'depends' => array('FCom_Core'),
                'description' => "Customer Accounts and Management",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'CustomerAdmin.php', 'callback'=>'FCom_Customer_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'CustomerFrontend.php', 'callback'=>'FCom_Customer_Frontend::bootstrap'),
                    ),
                ),
            ))
            // catalog views and controllers
            ->addModule('FCom_CustomField', array(
                'version' => '0.1.0',
                'root_dir' => 'CustomField',
                'bootstrap' => array('file'=>'CustomField.php', 'callback'=>'FCom_CustomField::bootstrap'),
                'depends' => array('FCom_Catalog'),
                'description' => "Base custom fields implementation, currently for catalog only",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'CustomFieldAdmin.php', 'callback'=>'FCom_CustomField_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'CustomFieldFrontend.php', 'callback'=>'FCom_CustomField_Frontend::bootstrap'),
                    ),
                ),
            ))
            // cart, checkout and customer account views and controllers
            ->addModule('FCom_Checkout', array(
                'version' => '0.1.0',
                'root_dir' => 'Checkout',
                'bootstrap' => array('file'=>'Checkout.php', 'callback'=>'FCom_Checkout::bootstrap'),
                'depends' => array('FCom_Catalog'),
                'description' => "Base cart and checkout functionality",
            ))
            ->addModule('FCom_Newsletter', array(
                'version' => '0.1.0',
                'root_dir' => 'Newsletter',
                'depends' => array('FCom_Core'),
                'description' => "Base subscription and mailing list management",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'NewsletterAdmin.php', 'callback'=>'FCom_Newsletter_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'NewsletterFrontend.php', 'callback'=>'FCom_Newsletter_Frontend::bootstrap'),
                    ),
                ),
            ))
            // paypal IPN
            ->addModule('FCom_PayPal', array(
                'version' => '0.1.0',
                'root_dir' => 'PayPal',
                'depends' => array('FCom_Core'),
                'description' => "PayPal&reg; standard payment method",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'PayPalAdmin.php', 'callback'=>'FCom_PayPal_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'PayPalFrontend.php', 'callback'=>'FCom_PayPal_Frontend::bootstrap'),
                    ),
                ),
            ))
            // freshbook simple invoicing
            ->addModule('FCom_FreshBooks', array(
                'version' => '0.1.0',
                'root_dir' => 'FreshBooks',
                'depends' => array('FCom_Core'),
                'description' => "FreshBooks&reg; payment method and invoice management API integration",
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'FreshBooksAdmin.php', 'callback'=>'FCom_FreshBooks_Admin::bootstrap'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'FreshBooksFrontend.php', 'callback'=>'FCom_FreshBooks_Frontend::bootstrap'),
                    ),
                ),
            ))
            // IndexTank integration
            ->addModule('FCom_IndexTank', array(
                'version' => '0.1.0',
                'root_dir' => 'PayPal',
                'depends' => array('FCom_Core'),
                'description' => "IndexTank API integration",
                'bootstrap' => array('file'=>'IndexTank.php', 'callback'=>'FCom_IndexTank::bootstrap'),
            ))
        ;
    }
}
