<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Item
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $qty
 * @property float $total
 * @property string $product_info
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Sales_Model_Order_History $FCom_Sales_Model_Order_History
 * @property FCom_Sales_Model_Order_Item_State $FCom_Sales_Model_Order_Item_State
 */
class FCom_Sales_Model_Order_Item extends FCom_Core_Model_Abstract
{
    use FCom_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_item';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @return FCom_Sales_Model_Order_Item_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->FCom_Sales_Model_Order_Item_State->factory($this);
        }
        return $this->_state;
    }

    /**
     * @param $orderId
     * @param $product_id
     * @return static
     */
    public function isItemExist($orderId, $product_id)
    {
        return $this->orm()->where("order_id", $orderId)
                        ->where("product_id", $product_id)->find_one();
    }

    public function isShippable()
    {
        return $this->get('shipping_weight') > 0;
    }

    public function __destruct()
    {
        unset($this->_state, $this->_order);
    }
}
