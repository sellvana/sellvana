<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_PaymentOgone_Model_Order extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_ogone_order';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
    ];
}
