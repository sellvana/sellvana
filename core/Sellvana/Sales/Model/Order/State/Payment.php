<?php

class Sellvana_Sales_Model_Order_State_Payment extends Sellvana_Sales_Model_Order_State_Abstract
{
    const FREE = 'free',
        UNPAID = 'unpaid',
        PROCESSING = 'processing',
        PARTIAL_PAID = 'partial_paid',
        PAID = 'paid',
        OUTSTANDING = 'outstanding',
        VOID = 'void',
        REFUNDED = 'refunded',
        PARTIAL_REFUNDED = 'partial_refunded';

    protected $_valueLabels = [
        self::FREE => 'Free',
        self::UNPAID => 'Unpaid',
        self::PROCESSING => 'Processing',
        self::PARTIAL_PAID => 'Partial Paid',
        self::PAID => 'Paid',
        self::OUTSTANDING => 'Outstanding',
        self::VOID => 'Void',
        self::REFUNDED => 'Refunded',
        self::PARTIAL_REFUNDED => 'Partial Refunded',
    ];

    protected $_defaultValueWorkflow = [
        self::FREE => [],
        self::UNPAID => [self::PROCESSING, self::PAID, self::PARTIAL_PAID, self::OUTSTANDING],
        self::PROCESSING => [self::PAID, self::PARTIAL_PAID, self::OUTSTANDING, self::VOID],
        self::PARTIAL_PAID => [self::PAID, self::OUTSTANDING, self::PARTIAL_REFUNDED],
        self::OUTSTANDING => [self::PAID, self::PARTIAL_PAID, self::VOID],
        self::VOID => [],
        self::PAID => [self::REFUNDED, self::PARTIAL_REFUNDED],
        self::PARTIAL_REFUNDED => [self::REFUNDED]
    ];

    public function getDefaultValue()
    {
        /** @var Sellvana_Sales_Model_Order $model */
        $model = $this->getContext()->getModel();
        return $model->isPayable() ? self::UNPAID : self::FREE;
    }

    public function setFree()
    {
        return $this->changeState(self::FREE);
    }

    public function setUnpaid()
    {
        return $this->changeState(self::UNPAID);
    }

    public function setProcessing()
    {
        return $this->changeState(self::PROCESSING);
    }

    public function setPartialPaid()
    {
        return $this->changeState(self::PARTIAL_PAID);
    }

    public function setPaid()
    {
        return $this->changeState(self::PAID);
    }

    public function setOutstanding()
    {
        return $this->changeState(self::OUTSTANDING);
    }

    public function setVoid()
    {
        return $this->changeState(self::VOID);
    }

    public function setPartialRefunded()
    {
        return $this->changeState(self::PARTIAL_REFUNDED);
    }

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }

    public function isComplete()
    {
        return in_array($this->getValue(), [self::FREE, self::PAID]);
    }

    public function calcState()
    {
        $itemStates = $this->getItemStateStatistics('payment');

        $free        = Sellvana_Sales_Model_Order_Item_State_Payment::FREE;
        $unpaid      = Sellvana_Sales_Model_Order_Item_State_Payment::UNPAID;
        $processing  = Sellvana_Sales_Model_Order_Item_State_Payment::PROCESSING;
        $paid        = Sellvana_Sales_Model_Order_Item_State_Payment::PAID;
        $outstanding = Sellvana_Sales_Model_Order_Item_State_Payment::OUTSTANDING;
        $canceled    = Sellvana_Sales_Model_Order_Item_State_Payment::CANCELED;
        $partial     = Sellvana_Sales_Model_Order_Item_State_Payment::PARTIAL;

        if (!empty($itemStates[$free]) && sizeof($itemStates) === 1) {
            return $this->setFree();
        }
        if (!empty($itemStates[$paid]) && empty($itemStates[$processing])
            && empty($itemStates[$outstanding]) && empty($itemStates[$partial])
        ) {
            return $this->setPaid();
        }
        if (!empty($itemStates[$processing])) {
            return $this->setProcessing();
        }
        if (!empty($itemStates[$outstanding])) {
            return $this->setOutstanding();
        }
        if (!empty($itemStates[$paid]) || !empty($itemStates[$partial])) {
            return $this->setPartialPaid();
        }

        return $this;
    }
}
