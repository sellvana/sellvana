<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Cart_Total_Subtotal
 *
 */
class FCom_Sales_Model_Cart_Total_Subtotal extends FCom_Sales_Model_Cart_Total_Abstract
{
    protected $_code = 'subtotal';
    protected $_label = 'Subtotal';
    protected $_cartField = 'subtotal';
    protected $_sortOrder = 10;

    /**
     * @return FCom_Sales_Model_Cart_Total_Subtotal
     */
    public function calculate()
    {
        $itemNum = 0;
        $itemQty = 0;
        $subtotal = 0;
        foreach ($this->_cart->items() as $item) {
            /*
            // TODO: figure out handling cart items of products removed from catalog
            if (!$item->getProduct()) {
                $this->_cart->removeProduct($item->product_id);
            }
            */
            $itemNum++;
            $itemQty += $item->get('qty');
            $rowTotal = $item->calcRowTotal();
            $subtotal += $rowTotal;
            $item->set('row_total', $rowTotal);
        }

        $this->_value = $subtotal;
        $this->_cart->set([
            'item_num' => $itemNum,
            'item_qty' => $itemQty,
            'subtotal' => $subtotal,
        ]);

        $this->_cart->getTotalByType('grand_total')->addComponent($this->_value, 'subtotal');

        return $this;
    }
}