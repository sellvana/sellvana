<?php

class FCom_Sales_Model_CartTotal_Subtotal extends FCom_Sales_Model_CartTotal_Abstract
{
    protected $_code = 'subtotal';
    protected $_label = 'Subtotal';
    protected $_sortOrder = 10;

    public function calculate()
    {
        $itemNum = 0;
        $itemQty = 0;
        $subtotal = 0;
        foreach ($this->_cart->items() as $item) {
            if (!$item->product()) {
                $cart->removeProduct($item->product_id);
            }
            $itemNum++;
            $item->qty = $item->qty;
            $itemQty += $item->qty;
            $item->rowtotal = $item->rowTotal();
            $subtotal += $item->rowtotal;
        }

        $this->_value = $subtotal;
        $this->_cart->set(array(
            'item_num' => $itemNum,
            'item_qty' => $itemQty,
            'subtotal' => $subtotal,
        ));
        return $this;
    }
}