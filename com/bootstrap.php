<?php

#set_time_limit(2);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

require "../../../buckyball/bucky/buckyball.php";

BConfig::i()->addFile('protected/config.json');

BModuleRegistry::i()
    // Core logic, abstract classes, all models
    ->module('fcom.core', array(
        'version' => '0.1.0',
        'root_dir' => 'core',
        'bootstrap' => array('file'=>'core.php', 'callback'=>'FCom_Core::bootstrap'),
    ))
    ->module('fcom.frontend', array(
        'version' => '0.1.0',
        'root_dir' => 'frontend',
        'bootstrap' => array('file'=>'frontend.php', 'callback'=>'FCom_Frontend::bootstrap'),
        'depends' => array('fcom.core'),
    ))
    // administration panel views and controllers
    ->module('fcom.admin', array(
        'version' => '0.1.0',
        'root_dir' => 'admin',
        'bootstrap' => array('file'=>'admin.php', 'callback'=>'FCom_Admin::bootstrap'),
        'depends' => array('buckyball.ui', 'fcom.core'),
    ))
    // catalog views and controllers
    ->module('fcom.catalog', array(
        'version' => '0.1.0',
        'root_dir' => 'catalog',
        'bootstrap' => array('file'=>'catalog.php', 'callback'=>'FCom_Catalog::bootstrap'),
        'depends' => array('fcom.core'),
    ))
    // cart, checkout and customer account views and controllers
    ->module('fcom.checkout', array(
        'version' => '0.1.0',
        'root_dir' => 'checkout',
        'bootstrap' => array('file'=>'checkout.php', 'callback'=>'FCom_Checkout::bootstrap'),
        'depends' => array('fcom.core'),
    ))
    // paypal IPN
    ->module('fcom.paypal', array(
        'version' => '0.1.0',
        'root_dir' => 'paypal',
        'bootstrap' => array('file'=>'paypal.php', 'callback'=>'FCom_PayPal::bootstrap'),
        'depends' => array('fcom.core'),
    ))
    // freshbook simple invoicing
    ->module('fcom.freshbooks', array(
        'version' => '0.1.0',
        'root_dir' => 'freshbooks',
        'bootstrap' => array('file'=>'freshbooks.php', 'callback'=>'FCom_FreshBooks::bootstrap'),
        'depends' => array('fcom.core'),
    ))

;

class FCom extends BClass
{
    public function run($module) {
    #$time = microtime(true);
        $modules = array();
        foreach (explode(',', $module) as $m) {
            $modules[] = $m;
        }

        BConfig::i()->addFile('../common/config.json')->add(array('bootstrap'=>array('modules'=>$modules)));

        $ip = BRequest::i()->ip();
        $modesByIP = BConfig::i()->get('debug/ip');
        if (!empty($modesByIP[$ip])) {
            BDebug::i()->mode($modesByIP[$ip]);
        }

        BModuleRegistry::i()->scan('../../bucky/plugins')->scan('../common');

        BApp::i()->run();
    #echo '<hr>'.(microtime(true)-$time);
    #print_r(ORM::get_query_log());
    }
}