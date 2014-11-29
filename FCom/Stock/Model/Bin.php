<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Stock_Model_Bin
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property datetime $create_at
 * @property datetime $update_at
 */
class FCom_Stock_Model_Bin extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_stock_bin';
    static protected $_origClass = __CLASS__;

}
