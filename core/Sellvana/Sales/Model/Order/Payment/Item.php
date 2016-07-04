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
    protected $_allField = 'amount_in_payments';
    protected $_doneField = 'amount_paid';
    protected $_sumField = 'amount';

    public function getOrderItemsQtys(array $items = [])
    {
        /*$parentClass = $this->_parentClass;
        $parentField = $this->_parentField;
        $allField = $this->_allField;
        $doneField = $this->_doneField;
        $doneStates = $this->_doneStates;

        $cItems = $this->orm('si')
            ->join($parentClass, ['s.id', '=', 'si.' . $parentField], 's')
            ->join('Sellvana_Sales_Model_Order_Payment_Transaction', ['s.id', '=', 't.payment_id'], 't')
            ->select('si.*')
            ->select('s.state_overall')
            ->select('t.amount')
            ->find_many();

        $result = [];

        if ($items) {
            foreach ($items as $itemId => $item) {
                if ($item->get($allField) != 0) {
                    $result[$itemId][$allField] = 0;
                }
                if ($item->get($doneField) != 0) {
                    $result[$itemId][$doneField] = 0;
                }
            }
        }

        foreach ($cItems as $cItem) {
            $oiId = $cItem->get('order_item_id');
            $qty = $cItem->get($this->_sumField);
            if (empty($result[$oiId][$allField])) {
                $result[$oiId][$allField] = $qty;
            } else {
                $result[$oiId][$allField] += $qty;
            }
            if (in_array($cItem->get('state_overall'), $doneStates)) {
                if (empty($result[$oiId][$doneField])) {
                    $result[$oiId][$doneField] = $qty;
                } else {
                    $result[$oiId][$doneField] += $qty;
                }
            }
        }*/
        $result = parent::getOrderItemsQtys($items);

        $parentClass = $this->_parentClass;
        $doneField = $this->_doneField;
        $payments = $this->$parentClass->orm('s')
            ->join('Sellvana_Sales_Model_Order_Payment_Transaction', ['s.id', '=', 't.' . $this->_parentField], 't')
            ->select('s.id')
            ->select_expr('SUM(t.amount)', $this->_sumField)
            ->where('t.transaction_type', Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE)
            ->where('t.transaction_status', Sellvana_Sales_Model_Order_Payment_Transaction::COMPLETED)
            ->group_by('s.id')
            ->find_many();

        foreach ($payments as $payment) {
            $result;
        }


        return $result;
    }
}