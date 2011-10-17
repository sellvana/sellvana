<?php

#set_time_limit(2);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__."/../../buckyball/bucky/buckyball.php";

class FCom extends BClass
{
    static protected $_area;

    public function run($area)
    {
        $config = BConfig::i();

        // local configuration (db, enabled modules)
        $config->add(include(__DIR__.'/../storage/config/local.php'));

        // add area module
        $config->add(array('bootstrap'=>array('modules'=>array('fcom.'.$area))));
        self::$_area = $area;

        BModuleRegistry::i()
            // Core logic, abstract classes, all models
            ->module('fcom.core', array(
                'version' => '0.1.0',
                'root_dir' => __DIR__.'/core',
                'bootstrap' => array('file'=>'core.php', 'callback'=>'FCom_Core::bootstrap'),
            ))
            // Frontend collection of modules
            ->module('fcom.frontend', array(
                'version' => '0.1.0',
                'root_dir' => __DIR__.'/frontend',
                'bootstrap' => array('file'=>'frontend.php', 'callback'=>'FCom_Frontend::bootstrap'),
                'depends' => array('fcom.core'),
            ))
            // administration panel views and controllers
            ->module('fcom.admin', array(
                'version' => '0.1.0',
                'root_dir' => __DIR__.'/admin',
                'bootstrap' => array('file'=>'admin.php', 'callback'=>'FCom_Admin::bootstrap'),
                'depends' => array('buckyball.ui', 'fcom.core'),
            ))
            // catalog views and controllers
            ->module('fcom.catalog', array(
                'version' => '0.1.0',
                'root_dir' => __DIR__.'/catalog',
                'bootstrap' => array('file'=>'catalog.php', 'callback'=>'FCom_Catalog::bootstrap'),
                'depends' => array('fcom.core'),
            ))
            // cart, checkout and customer account views and controllers
            ->module('fcom.checkout', array(
                'version' => '0.1.0',
                'root_dir' => __DIR__.'/checkout',
                'bootstrap' => array('file'=>'checkout.php', 'callback'=>'FCom_Checkout::bootstrap'),
                'depends' => array('fcom.core'),
                'url_prefix' => 'checkout',
            ))
            // paypal IPN
            ->module('fcom.paypal', array(
                'version' => '0.1.0',
                'root_dir' => __DIR__.'/paypal',
                'bootstrap' => array('file'=>'paypal.php', 'callback'=>'FCom_PayPal::bootstrap'),
                'depends' => array('fcom.core'),
                'url_prefix' => 'paypal',
            ))
            // freshbook simple invoicing
            ->module('fcom.freshbooks', array(
                'version' => '0.1.0',
                'root_dir' => __DIR__.'/freshbooks',
                'bootstrap' => array('file'=>'freshbooks.php', 'callback'=>'FCom_FreshBooks::bootstrap'),
                'depends' => array('fcom.core'),
            ))
        ;

        if (($modes = $config->get('debug/ip')) && ($ip = BRequest::i()->ip()) && !empty($modes[$ip])) {
            BDebug::i()->mode($modes[$ip]);
        }

        BModuleRegistry::i()->scan('../../bucky/plugins')->scan('../market/*')->scan('../local/*');

        BApp::i()->run();
    #echo '<hr>'.(microtime(true)-$time);
    #print_r(ORM::get_query_log());
    }

    static public function area()
    {
        return self::$_area;
    }
}