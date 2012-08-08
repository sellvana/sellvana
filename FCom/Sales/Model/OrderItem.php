<?php

class FCom_Sales_Model_OrderItem extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_item';
    protected static $_origClass = __CLASS__;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_OrderItem
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(get_called_class(), $args, !$new);
    }

    public function add($data)
    {
        BPubSub::i()->fire(__CLASS__.'.add', array('orderItem'=>$data));
        return $this->create($data)->save();
    }

    public function isItemExist($orderId, $product_id)
    {
        return $this->orm()->where("order_id", $orderId)
                        ->where("product_id", $product_id)->find_one();
    }
}