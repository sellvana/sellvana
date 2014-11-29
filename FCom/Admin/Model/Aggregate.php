<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Admin_Model_Aggregate
 *
 * @property int $id
 * @property string $data_type
 * @property string $data_args
 * @property string $data_day
 * @property float $amount
 */
class FCom_Admin_Model_Aggregate extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_aggregate';
    protected static $_origClass = __CLASS__;

}
