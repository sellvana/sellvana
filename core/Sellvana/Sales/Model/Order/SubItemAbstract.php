<?php

abstract class Sellvana_Sales_Model_Order_SubItemAbstract extends FCom_Core_Model_Abstract
{
    /**
     * @var string
     */
    protected $_parentClass;

    /**
     * @var string
     */
    protected $_parentField;

    /**
     * @var string
     */
    protected $_allField;

    /**
     * @var string
     */
    protected $_doneField;

    /**
     * @var array
     */
    protected $_doneStates = [];

    protected $_sumField = 'qty';

    public function getOrderItemsQtys(array $items = [])
    {
        $parentClass = $this->_parentClass;
        $parentField = $this->_parentField;
        $allField = $this->_allField;
        $doneField = $this->_doneField;
        $doneStates = $this->_doneStates;

        $cItems = $this->orm('si')
            ->join($parentClass, ['s.id', '=', 'si.' . $parentField], 's')
            ->select('si.*')
            ->select('s.state_overall')
            ->find_many();

/*        var_dump($cItems);
        exit();*/
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
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getAllField()
    {
        return $this->_allField;
    }

    /**
     * @return string
     */
    public function getDoneField()
    {
        return $this->_doneField;
    }
}