<?php debug_backtrace() || exit;

#set_time_limit(2);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__."/../lib/b/buckyball.php";

class FCom extends BClass
{
    static protected $_area;

    static public function area()
    {
        return self::$_area;
    }

    static public function rootDir()
    {
        static $dir;
        if (!$dir) {
            $dir = dirname(__DIR__);
        }
        return $dir;
    }

    public function run($area)
    {
        try {
            $config = BConfig::i();

            // initialize start time and register error/exception handlers
            BDebug::i();
            BDebug::registerErrorHandlers();

            BDebug::mode('debug');
            #BDebug::mode('development');

            $rootDir = $config->get('root_dir');
            if (!$rootDir) {
                $rootDir = dirname(__DIR__);
                $config->add(array('root_dir'=>$rootDir));
            }
            BDebug::debug('ROOTDIR='.$rootDir);

            // local configuration (db, enabled modules)
            $configDir = $config->get('config_dir');
            if (!$configDir) {
                $configDir = $rootDir.'/storage/config';
                $config->add(array('config_dir'=>$configDir));
            }

            $basePath = $config->get('web/base_path');
            if (!$basePath) {
                $basePath = BRequest::i()->webRoot();
                $config->add(array('web'=>array('base_path'=>$basePath)));
            }

            BDebug::logDir($rootDir.'/storage/log');
            BDebug::adminEmail($config->get('admin_email'));

            // DB configuration is separate to gitignore
            // used as indication that app is already installed and setup
            if (file_exists($configDir.'/db.php')) {
                $config->add(include($configDir.'/db.php'));
                $config->add(include($configDir.'/local.php'));
            } else {
                $area = 'FCom_Install';
            }

            // add area module
            $config->add(array('modules'=>array($area=>array('run_level'=>BModule::REQUIRED))));
            self::$_area = $area;

            // Initialize debugging mode and levels
            if (($debugConfig = $config->get('debug'))) {
                if (!empty($debugConfig['ip']) && ($ip = BRequest::i()->ip()) && !empty($debugConfig['ip'][$ip])) {
                    BDebug::mode($debugConfig['ip'][$ip]);
                } elseif (!empty($debugConfig['ip']['mode'])) {
                    BDebug::mode($debugConfig['ip']['mode']);
                }
                if (!empty($debugConfig['levels'])) {
                    foreach ($debugConfig['levels'] as $type=>$level) {
                        BDebug::level($type, $level);
                    }
                }
            }
    #print_r(BDebug::mode());
            // Register modules
            $this->registerBundledModules();
            BModuleRegistry::i()
                ->scan($rootDir.'/lib/b/plugins')
                ->scan($rootDir.'/market/*')
                ->scan($rootDir.'/local/*');

            // Run application
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
            ->module('FCom_Core', array(
                'version' => '0.1.0',
                'root_dir' => 'Core',
                'bootstrap' => array('file'=>'Core.php', 'callback'=>'FCom_Core::bootstrap'),
            ))
            // Initial installation module
            ->module('FCom_Install', array(
                'version' => '0.1.0',
                'root_dir' => 'Install',
                'bootstrap' => array('file'=>'Install.php', 'callback'=>'FCom_Install::bootstrap'),
                'depends' => array('FCom_Core'),
            ))
            // Frontend collection of modules
            ->module('FCom_Frontend', array(
                'version' => '0.1.0',
                'root_dir' => 'Frontend',
                'bootstrap' => array('file'=>'Frontend.php', 'callback'=>'FCom_Frontend::bootstrap'),
                'depends' => array('FCom_Core'),
            ))
            // Frontend collection of modules
            ->module('FCom_Frontend_DefaultTheme', array(
                'version' => '0.1.0',
                'root_dir' => 'Frontend',
                'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Frontend_DefaultTheme::bootstrap'),
                'depends' => array('FCom_Core'),
            ))
            // administration panel views and controllers
            ->module('FCom_Admin', array(
                'version' => '0.1.0',
                'root_dir' => 'Admin',
                'bootstrap' => array('file'=>'Admin.php', 'callback'=>'FCom_Admin::bootstrap'),
                'depends' => array('buckyball.ui', 'FCom_Core'),
            ))
            // Frontend collection of modules
            ->module('FCom_Admin_DefaultTheme', array(
                'version' => '0.1.0',
                'root_dir' => 'Admin',
                'bootstrap' => array('file'=>'DefaultTheme.php', 'callback'=>'FCom_Admin_DefaultTheme::bootstrap'),
                'depends' => array('FCom_Core'),
            ))
            // cron jobs processing
            ->module('FCom_Cron', array(
                'version' => '0.1.0',
                'root_dir' => 'Cron',
                'bootstrap' => array('file'=>'Cron.php', 'callback'=>'FCom_Cron::bootstrap'),
                'depends' => array('buckyball.ui', 'FCom_Core'),
            ))
            // catalog views and controllers
            ->module('FCom_Catalog', array(
                'version' => '0.1.0',
                'root_dir' => 'Catalog',
                'bootstrap' => array('file'=>'Catalog.php', 'callback'=>'FCom_Catalog::bootstrap'),
                'depends' => array('FCom_Core'),
            ))
            // cart, checkout and customer account views and controllers
            ->module('FCom_Checkout', array(
                'version' => '0.1.0',
                'root_dir' => 'Checkout',
                'bootstrap' => array('file'=>'Checkout.php', 'callback'=>'FCom_Checkout::bootstrap'),
                'depends' => array('FCom_Catalog'),
                'url_prefix' => 'checkout',
            ))
            ->module('FCom_Newsletter', array(
                'version' => '0.1.0',
                'root_dir' => 'Newsletter',
                'bootstrap' => array('file'=>'Newsletter.php', 'callback'=>'FCom_Newsletter::bootstrap'),
                'depends' => array('FCom_Core'),
                'url_prefix' => 'newsletter',
            ))
            // paypal IPN
            ->module('FCom_PayPal', array(
                'version' => '0.1.0',
                'root_dir' => 'PayPal',
                'bootstrap' => array('file'=>'PayPal.php', 'callback'=>'FCom_PayPal::bootstrap'),
                'depends' => array('FCom_Core'),
                'url_prefix' => 'paypal',
            ))
            // freshbook simple invoicing
            ->module('FCom_FreshBooks', array(
                'version' => '0.1.0',
                'root_dir' => 'FreshBooks',
                'bootstrap' => array('file'=>'FreshBooks.php', 'callback'=>'FCom_FreshBooks::bootstrap'),
                'depends' => array('FCom_Core'),
            ))
        ;
    }
}