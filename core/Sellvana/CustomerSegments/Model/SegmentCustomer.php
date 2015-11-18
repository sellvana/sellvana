<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_CustomerSegments_Model_SegmentCustomer extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_segment_customer';
    protected static $_origClass = __CLASS__;

    public function getCustomerSegmentIds($customerId)
    {
        return $this->orm()->where('customer_id', $customerId)->find_many_assoc('id', 'segment_id');
    }
}