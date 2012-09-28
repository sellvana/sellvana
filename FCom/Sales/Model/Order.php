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
        $this->items = FCom_Sales_Model_OrderItem::i()->orm()->where('order_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

    public function isOrderExists($productId, $customerID)
    {
        return $this->orm('o')->join('FCom_Sales_Model_OrderItem', array('o.id','=','oi.order_id'), 'oi')
                ->where("user_id", $customerID)->where("product_id", $productId)->find_one();

    }

    public function prepareApiData($orders, $includeItems=false)
    {
        $result = array();
        foreach($orders as $i => $order) {
            $result[$i] = array(
                'id'                => $order->id,
                'customer_id'      => $order->user_id,
                'status'               => $order->status,
                'item_qty'             => $order->item_qty,
                'subtotal'               => $order->subtotal,
                'balance'            => $order->balance,
                'tax'       => $order->tax,
                'shipping_method' => $order->shipping_method,
                'shipping_service'       => $order->shipping_service,
                'payment_method'       => $order->payment_method,
                'discount_code'       => $order->discount_code
            );
            if ($includeItems) {
                $items = $order->items();
                foreach($items as $item) {
                    $result[$i]['items'][] = array(
                        'product_id'    => $item->product_id,
                        'qty'    => $item->qty,
                        'total'    => $item->total,
                        //get product info as object and prepare data for api 
                        'product_info'    => FCom_Catalog_Model_Product::i()->prepareApiData(BUtil::fromJson($item->product_info, true)),
                    );
                }
            }
        }
        return $result;
    }

    public function formatApiPost($post)
    {
        $data = array();
        if (!empty($post['customer_id'])) {
            $data['user_id'] = $post['customer_id'];
        }
        if (!empty($post['status'])) {
            $data['status'] = $post['status'];
        }
        if (!empty($post['item_qty'])) {
            $data['item_qty'] = $post['item_qty'];
        }
        if (!empty($post['subtotal'])) {
            $data['subtotal'] = $post['subtotal'];
        }
        if (!empty($post['balance'])) {
            $data['balance'] = $post['balance'];
        }
        if (!empty($post['tax'])) {
            $data['tax'] = $post['tax'];
        }
        if (!empty($post['shipping_method'])) {
            $data['shipping_method'] = $post['shipping_method'];
        }
        if (!empty($post['shipping_service'])) {
            $data['shipping_service'] = $post['shipping_service'];
        }
        if (!empty($post['payment_method'])) {
            $data['payment_method'] = $post['payment_method'];
        }
        if (!empty($post['discount_code'])) {
            $data['discount_code'] = $post['discount_code'];
        }
        return $data;
    }

}