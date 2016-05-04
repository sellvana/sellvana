<?php

class Sellvana_Sales_Model_Order_SubItemAbstract extends FCom_Core_Model_Abstract
{
    protected function _getOrderItemsQtys(array $items, $parentClass, $parentField, $allField, $doneField, $doneStates)
    {
        $cItems = $this->orm('si')
            ->join($parentClass, ['s.id', '=', 'si.' . $parentField], 's')
            ->select('si.*')
            ->select('s.state_overall')
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
            $qty = $cItem->get('qty');
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
        }

        return $result;
    }
}