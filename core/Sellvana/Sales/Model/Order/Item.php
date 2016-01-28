<?php

/**
 * Class Sellvana_Sales_Model_Order_Item
 *
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $qty
 * @property float $total
 * @property string $product_info
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Item_State $Sellvana_Sales_Model_Order_Item_State
 */
class Sellvana_Sales_Model_Order_Item extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_item';
    protected static $_origClass = __CLASS__;

    protected $_state;

    protected $_product;

    /**
     * @return Sellvana_Sales_Model_Order_Item_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Item_State->factory($this);
        }
        return $this->_state;
    }

    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }

    public function product()
    {
        if (!$this->_product) {
            $this->_product = $this->Sellvana_Catalog_Model_Product->load($this->get('product_id'));
        }
        return $this->_product;
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

    public function getQtyCanPay()
    {
        return $this->get('qty_ordered') - $this->get('qty_paid') - $this->get('qty_canceled');
    }

    public function getQtyCanBackorder()
    {
        return $this->get('qty_ordered') - $this->get('qty_shipped') - $this->get('qty_canceled')
                - $this->get('qty_backordered');
    }

    public function getQtyCanShip()
    {
        return $this->get('qty_ordered') - $this->get('qty_shipped') - $this->get('qty_canceled')
                - $this->get('qty_backordered');
    }

    public function getQtyCanCancel()
    {
        return $this->get('qty_ordered') - $this->get('qty_shipped') -  $this->get('qty_canceled');
    }

    public function getQtyCanReturn()
    {
        return $this->get('qty_shipped') - $this->get('qty_returned');
    }

    public function getQtyCanRefund()
    {
        return $this->get('qty_paid') - $this->get('qty_refunded');
    }

    /**
     * @param float|null $qty
     */
    public function markAsPaid($qty = null)
    {
        if ($qty === null) {
            $qty = $this->get('qty_ordered');
        } else {
            $qty = min($this->get('qty_paid') + $qty, $this->get('qty_ordered'));
        }

        $this->set('qty_paid', $qty);
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_state, $this->_order, $this->_product);
    }
}
