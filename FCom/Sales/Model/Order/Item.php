<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Item extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_item';
    protected static $_origClass = __CLASS__;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_Order_Item
    */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(get_called_class(), $args, !$new);
    }

    public function addNew($data)
    {
        BEvents::i()->fire(__CLASS__ . '.addNew', ['orderItem' => $data]);
        return $this->create($data)->save();
    }

    public function update($data)
    {
        BEvents::i()->fire(__CLASS__ . '.update', ['orderItem' => $data]);
        return $this->set($data)->save();
    }

    public function isItemExist($orderId, $product_id)
    {
        return $this->orm()->where("order_id", $orderId)
                        ->where("product_id", $product_id)->find_one();
    }
}
