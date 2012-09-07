<?php

class FCom_Sales_Model_Order extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order';
    protected static $_origClass = __CLASS__;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_Order
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(get_called_class(), $args, !$new);
    }

    public function billing()
    {
        return FCom_Checkout_Model_Address::i()->orm('a')
                ->where('cart_id', $this->cart_id)->where('atype', 'billing')->find_one();
    }

    public function add($data)
    {
        $data['status'] = 'new';
        BPubSub::i()->fire(__CLASS__.'.add', array('order'=>$data));
        return $this->create($data)->save();
    }

    public function update($data)
    {
        BPubSub::i()->fire(__CLASS__.'.update', array('order'=>$data));
        return $this->set($data)->save();
    }

    public function paid()
    {
        $this->set('status', 'paid')->save();
    }

    /**
     * Return total UNIQUE number of items in the order
     * @param boolean $assoc
     * @return array
     */
    public function items($assoc=true)
    {
        $this->items = FCom_Sales_Model_OrderItem::factory()->where('order_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

    public function isOrderExists($productId, $customerID)
    {
        return $this->orm('o')->join(FCom_Sales_Model_OrderItem::table(), "o.id = oi.order_id", "oi")
                ->where("user_id", $customerID)->where("product_id", $productId)->find_one();

    }

}