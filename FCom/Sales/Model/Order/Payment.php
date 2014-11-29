<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Payment
 *
 * @property int $id
 * @property datetime $create_at
 * @property datetime $update_at
 * @property string $method
 * @property int $parent_id
 * @property int $order_id
 * @property float $amount
 * @property string $data_serialized
 * @property string $status
 * @property string $transaction_id //todo: why this field is varchar?
 * @property string $transaction_type
 * @property int $online
 */
class FCom_Sales_Model_Order_Payment extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_payment';
    protected static $_origClass = __CLASS__;

    /**
     * @param $data
     * @return static
     */
    public function addNew($data)
    {
        $this->BEvents->fire(__CLASS__ . '.addNew', ['paymentData' => $data]);
        return $this->create($data);
    }

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_Sales_Model_Order_Payment
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }
}
