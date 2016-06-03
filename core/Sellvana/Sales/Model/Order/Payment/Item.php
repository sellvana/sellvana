<?php

/**
 * Class Sellvana_Sales_Model_Order_Payment_Item
 */
class Sellvana_Sales_Model_Order_Payment_Item extends Sellvana_Sales_Model_Order_SubItemAbstract
{
    protected static $_table = 'fcom_sales_order_payment_item';
    protected static $_origClass = __CLASS__;

    protected $_parentClass = 'Sellvana_Sales_Model_Order_Payment';
    protected $_parentField = 'payment_id';
    protected $_allField = 'qty_in_payments';
    protected $_doneField = 'qty_paid';
    protected $_amtField = 'amt_in_payments';
    protected $_doneStates = [
        Sellvana_Sales_Model_Order_Payment_State_Overall::PAID,
    ];

    public function getOrderItemsQtys(array $items = [])
    {
        //$result = parent::getOrderItemsQtys($items);
        $result = [];
        if ($items) {
            foreach ($items as $itemId => $item) {
                /** @var Sellvana_Sales_Model_Order_Payment_Item $item */
                if (!isset($result[$item->get('order_item_id')][$this->_amtField])) {
                    $result[$item->get('order_item_id')][$this->_amtField] = 0;
                }

                $result[$item->get('order_item_id')][$this->_amtField] += $item->get('amount');
            }
        }

        $items = $this->orm('opi')
            ->join('Sellvana_Sales_Model_Order_Item', ['oi.id', '=', 'opi.order_item_id'], 'oi')
            ->select('opi.*')
            ->select(['oi.row_total', 'oi.row_discount'])
            ->find_many();
        foreach ($items as $item) {
            $calcPrice = round(($item->get('row_total') - $item->get('row_discount')) / $item->get('qty_ordered'), 2);
            $paidPrice = round($item->get('amount') / $item->get('qty'), 2);
            if (array_key_exists($item->get('order_item_id'), $result)) {
                $result[$item->get('order_item_id')] = [];
            }

            // let's consider unit in payment only if payment amount wasn't changed
            if ($calcPrice == $paidPrice) {
                $result[$item->get('order_item_id')][$this->_allField] += 1;
            }
        }
        return $result;
    }


}