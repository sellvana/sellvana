<?php

class FCom_Core_Model_Module extends BDbModule
{
    static protected $_table = 'fcom_module';
    static protected $_origClass = __CLASS__;

    static protected $_fieldOptions = array(
        'core_run_level' => array(
            BModule::ONDEMAND  => 'ONDEMAND',
            BModule::DISABLED  => 'DISABLED',
            BModule::REQUESTED => 'REQUESTED',
            BModule::REQUIRED  => 'REQUIRED',
        ),
        'area_run_level' => array(
            ''  => '',
            BModule::DISABLED  => 'DISABLED',
            BModule::REQUESTED => 'REQUESTED',
            BModule::REQUIRED  => 'REQUIRED',
        ),
        'run_status' => array(
            BModule::IDLE    => 'IDLE',
            BModule::LOADED  => 'LOADED',
            BModule::ERROR   => 'ERROR',
        ),
    );
}

