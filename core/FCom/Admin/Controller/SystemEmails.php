<?php
class FCom_Admin_Controller_SystemEmails extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'systememails';
    protected $_gridTitle = (('System Emails'));
    protected $_recordName = (('System Emails'));
    protected $_navPath = 'system/emails';

    public function gridConfig()
    {
        $config = parent::gridConfig();
    }

}