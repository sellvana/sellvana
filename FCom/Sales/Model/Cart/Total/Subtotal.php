<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_Total_Subtotal extends FCom_Sales_Model_Cart_Total_Abstract
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
                $this->_cart->removeProduct($item->product_id);
            }
            $itemNum++;
            //$item->qty = $item->qty; //TODO: what's up with that
            $itemQty += $item->qty;
            $variants = $item->getData('variants');
            if (!is_null($variants)) {
                foreach($variants as $key => $variant) {
                    $item->rowtotal += $item->rowTotal($key);
                }
            } else {
                $item->rowtotal = $item->rowTotal();
            }
            $subtotal += $item->rowtotal;
        }

        $this->_value = $subtotal;
        $this->_cart->set([
            'item_num' => $itemNum,
            'item_qty' => $itemQty,
            'subtotal' => $subtotal,
        ]);
        return $this;
    }
}