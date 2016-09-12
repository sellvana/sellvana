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
        return $this->get('shipping_weight') > 0
            && $this->state()->delivery()->getValue() != Sellvana_Sales_Model_Order_Item_State_Delivery::VIRTUAL;
    }

    public function getQtyCanPay()
    {
        return $this->get('qty_ordered') - $this->get('qty_in_cancels');
    }

    public function getQtyCanBackorder()
    {
        return $this->get('qty_ordered') - $this->get('qty_in_shipments') - $this->get('qty_in_cancels')
                - $this->get('qty_backordered');
    }

    public function getQtyCanShip()
    {
        return $this->get('qty_ordered') - $this->get('qty_in_shipments') - $this->get('qty_in_cancels')
                - $this->get('qty_backordered');
    }

    public function getQtyCanCancel()
    {
        return $this->get('qty_ordered') - $this->get('qty_in_shipments') -  $this->get('qty_in_cancels');
    }

    public function getQtyCanReturn()
    {
        return $this->get('qty_shipped') - $this->get('qty_in_returns');
    }

    /**
     * Get amount available to create a new refund
     *
     * @return float
     */
    public function getAmountCanRefund()
    {
        return $this->get('amount_paid') - $this->get('amount_in_refunds');
    }

    /**
     * Get amount available to complete a refund
     *
     * @return float
     */
    public function getRefundableAmount()
    {
        return $this->get('amount_paid') - $this->get('amount_refunded');
    }

    public function getCalcPrice()
    {
        return ($this->get('row_total') - $this->get('row_discount')) / $this->get('qty_ordered');
    }

    public function getAmountCanPay()
    {
        return $this->get('row_total') - $this->get('row_discount') - $this->get('amount_in_payments');
    }

    public function getBalanceAmount()
    {
        return $this->get('row_total') - $this->get('row_discount') - $this->get('amount_paid');
    }

    /**
     * @param float|null $amount
     */
    public function markAsPaid($amount = null)
    {
        if ($amount === null) {
            $amount = $this->getBalanceAmount();
        } else {
            $amount = min((float)$this->get('amount_in_payments') + $amount, $this->getBalanceAmount());
        }

        $this->set('amount_in_payments', $amount);
        $this->set('amount_paid', $amount);
        $this->save(false);
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_state, $this->_order, $this->_product);
    }
}
