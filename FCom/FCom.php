<?php debug_backtrace() || exit;

define('FULLERON_ROOT_DIR', dirname(__DIR__));
//set_time_limit(2);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

class FCom extends BClass
{
    protected $_modulesDirs = array();

    /**
    * Shortcut to help with IDE autocompletion
    *
    * @return FCom
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(__CLASS__, $args, !$new);
    }

    /**
    * @deprecated
    */
    static public function area()
    {
        return BApp::i()->get('area');
    }

    /**
    * @deprecated
    */
    static public function rootDir()
    {
        return FULLERON_ROOT_DIR;
    }

    public function addModulesDir($dir)
    {
        $this->_modulesDirs[] = $dir;
        return $this;
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

        $baseHref = $config->get('web/base_href');
        if (!$baseHref) {
            $baseHref = BRequest::i()->webRoot();
            $localConfig['web']['base_href'] = $baseHref;
        }
        if (!$config->get('web/base_src')) {
            $localConfig['web']['base_src'] = $baseHref;
        }
        if (!$config->get('web/base_store')) {
            $localConfig['web']['base_store'] = $baseHref;
        }

        $mediaDir = $config->get('fs/media_dir');
        if (!$mediaDir) {
            $mediaDir = $rootDir.'/media';
            $config->set('fs/media_dir', $mediaDir);
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

        // cache files
        $logDir = $config->get('fs/cache_dir');
        if (!$logDir) {
            $logDir = $storageDir.'/cache';
            $config->set('fs/cache_dir', $logDir);
        }

        // log files
        $logDir = $config->get('fs/log_dir');
        if (!$logDir) {
            $logDir = $storageDir.'/log';
            $config->set('fs/log_dir', $logDir);
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
            $area = 'FCom_Admin'; //TODO: make sure works without (bootstrap considerations)
            BDebug::mode('INSTALLATION');
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
        BDebug::logDir($config->get('fs/log_dir'));
        BDebug::adminEmail($config->get('admin_email'));

        $modeByIp = trim($config->get('modules/'.BApp::i()->get('area').'/mode_by_ip'));
        if ($modeByIp) {
            $ipModes = array();
            foreach (explode("\n", $modeByIp) as $line) {
                $a = explode(':', $line);
                if (empty($a[1])) {
                    $a = array('*', $a[0]);
                }
                $ipModes[trim($a[0])] = strtoupper(trim($a[1]));
            }
            $ip = BRequest::i()->ip();
            if (PHP_SAPI==='cli' && !empty($ipModes['$'])) {
                BDebug::mode($ipModes['$']);
            } elseif (!empty($ipModes[$ip])) {
                BDebug::mode($ipModes[$ip]);
            } elseif (!empty($ipModes['*'])) {
                BDebug::mode($ipModes['*']);
            }
        }
#print_r(BDebug::mode());
        return $this;
    }

    public function initModules()
    {
        $config = BConfig::i();

        if (BDebug::is('DISABLED')) {
            BResponse::i()->status('404', 'Page not found', 'Page not found');
            die;
        }
        if (BDebug::is('INSTALLATION')) {
            $runLevels = array('FCom_Install' => 'REQUIRED');
        } else {
            $runLevels = array(static::area() => 'REQUIRED');
        }
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

        if (defined('BUCKYBALL_ROOT_DIR')) {
            $this->_modulesDirs[] = BUCKYBALL_ROOT_DIR.'/plugins';
            // if minified version used, need to load plugins manually
        }
        $this->_modulesDirs[] = FULLERON_ROOT_DIR.'/market/*';
        $this->_modulesDirs[] = FULLERON_ROOT_DIR.'/local/*';

        foreach ($this->_modulesDirs as $dir) {
            BModuleRegistry::i()->scan($dir);
        }
#BDebug::profile($d);

        BClassAutoload::i(true, array('root_dir'=>FULLERON_ROOT_DIR.'/local'));
        BClassAutoload::i(true, array('root_dir'=>FULLERON_ROOT_DIR.'/market'));
        BClassAutoload::i(true, array('root_dir'=>FULLERON_ROOT_DIR));

        return $this;
    }

    public function run($area)
    {
        $this->init($area);
        if('Tests' == $area){
            BModuleRegistry::i()->bootstrap();
            return;
        }
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
            // Core logic, abstract classes
            ->addModule('FCom_Core', array(
                'version' => '0.1.0',
                'root_dir' => 'Core',
                'bootstrap' => array('file'=>'Core.php', 'callback'=>'FCom_Core::bootstrap'),
                'run_level' => BModule::REQUIRED,
                'migrate' => 'FCom_Core_Migrate',
                'description' => "Base Fulleron classes and JS libraries",
            ))
            // Initial installation module
            ->addModule('FCom_Install', array(
                'version' => '0.1.0',
                'root_dir' => 'Install',
                'bootstrap' => array('file'=>'Install.php', 'callback'=>'FCom_Install::bootstrap'),
                'depends' => array('FCom_Core'),
                'description' => "Initial installation wizard",
            ))
            // API area
            ->addModule('FCom_Api', array(
                'version' => '0.1.0',
                'root_dir' => 'Api',
                'bootstrap' => array('file'=>'Api.php', 'callback'=>'FCom_Api::bootstrap'),
                'depends' => array('FCom_Core'),
                'description' => "API area",
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
                'migrate' => 'FCom_Admin_Migrate',
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
            // cron jobs processing
            ->addModule('FCom_Geo', array(
                'version' => '0.1.0',
                'root_dir' => 'Geo',
                'migrate' => 'FCom_Geo::migrate',
                'bootstrap' => array('file'=>'Geo.php', 'callback'=>'FCom_Geo::bootstrap'),
                'depends' => array('FCom_Core'),
                'description' => "Geographic information about countries and states",
            ))
            // catalog views and controllers
            ->addModule('FCom_Cms', array(
                'version' => '0.1.1',
                'root_dir' => 'Cms',
                'depends' => array('FCom_Core', 'BPHPTAL'),
                'description' => "CMS for custom pages and forms",
                'bootstrap' => array('file'=>'CmsFrontend.php', 'callback'=>'FCom_Cms_Frontend::bootstrap'),
                'migrate' => 'FCom_Cms_Migrate',
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
                'bootstrap' => array('file'=>'ProductReviewsFrontend.php', 'callback'=>'FCom_ProductReviews_Frontend::bootstrap'),
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
                'version' => '0.1.1',
                'root_dir' => 'Catalog',
                'depends' => array('FCom_Core'),
                'description' => "Categories and products management, admin and frontend",
                'migrate' => 'FCom_Catalog_Migrate',
                'bootstrap' => array('file'=>'CatalogFrontend.php', 'callback'=>'FCom_Catalog_Frontend::bootstrap'),
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
                'version' => '0.1.1',
                'root_dir' => 'Customer',
                'depends' => array('FCom_Core'),
                'description' => "Customer Accounts and Management",
                'migrate' => 'FCom_Customer_Migrate',
                'bootstrap' => array('file'=>'CustomerFrontend.php', 'callback'=>'FCom_Customer_Frontend::bootstrap'),
                'areas' => array(
                    'FCom_Api' => array(
                        'bootstrap' => array('file'=>'Api.php', 'callback'=>'FCom_Customer_Api::bootstrap'),
                    ),
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
                'after' => array('FCom_Customer'),
                'description' => "Base custom fields implementation, currently for catalog only",
                'migrate' => 'FCom_CustomField_Migrate',
                'bootstrap' => array('file'=>'CustomFieldFrontend.php', 'callback'=>'FCom_CustomField_Frontend::bootstrap'),
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
                'bootstrap' => array('file'=>'NewsletterFrontend.php', 'callback'=>'FCom_Newsletter_Frontend::bootstrap'),
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
                'bootstrap' => array('file'=>'PayPalFrontend.php', 'callback'=>'FCom_PayPal_Frontend::bootstrap'),
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
                'bootstrap' => array('file'=>'FreshBooksFrontend.php', 'callback'=>'FCom_FreshBooks_Frontend::bootstrap'),
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
                'root_dir' => 'IndexTank',
                'depends' => array('FCom_Core'),
                'description' => "IndexTank API integration",
                'migrate' => 'FCom_IndexTank_Migrate',
                'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
                'areas' => array(
                    'FCom_Admin' => array(
                        'bootstrap' => array('file'=>'IndexTankAdmin.php', 'callback'=>'FCom_IndexTank_Admin::bootstrap'),
                        'depends' => array('BGanon'),
                    ),
                    'FCom_Frontend' => array(
                        'bootstrap' => array('file'=>'IndexTankFrontend.php', 'callback'=>'FCom_IndexTank_Frontend::bootstrap'),
                        //'depends' => array('FCom_Frontend'),
                    ),
                ),
            ))
        ;
    }
}
