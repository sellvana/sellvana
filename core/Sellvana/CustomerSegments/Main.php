<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_CustomerSegments_Main
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_CustomerSegments_Main extends BClass
{
    public function onCustomerBeforeSave($args)
    {
        $defCustSegment = $this->BConfig->get('modules/Sellvana_CustomerSegments/default_segment_id');
        $args['model']->set('customer_segment', $defCustSegment);
    }
}