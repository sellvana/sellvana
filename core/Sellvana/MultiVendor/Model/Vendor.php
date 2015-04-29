<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiVendor_Model_Vendor
 *
 */
class Sellvana_MultiVendor_Model_Vendor extends FCom_Core_Model_Abstract
{
    static protected $_origClass = __CLASS__;
    static protected $_table = 'fcom_multivendor_vendor';

    static protected $_fieldOptions = [
        'notify_type' => [
            'no' => 'No notifications',
            'realtime' => 'Real-time notifications',
        ],
    ];

    static protected $_fieldDefaults = [
        'notify_type' => 'realtime',
    ];

    public function vendorOptions()
    {
        return ['' => ''] + $this->orm()->order_by_asc('vendor_name')->find_many_assoc('id', 'vendor_name');
    }
}