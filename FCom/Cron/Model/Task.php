<?php

class FCom_Cron_Model_Task extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_cron_task';
    protected static $_importExportProfile = array(
        'unique_key' => array( 'handle', ),
    );
}