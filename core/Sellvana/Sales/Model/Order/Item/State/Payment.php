<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Item_State_Payment extends Sellvana_Sales_Model_Order_State_Abstract
{
    const FREE = 'free',
        UNPAID = 'unpaid',
        PROCESSING = 'processing',
        PAID = 'paid',
        OUTSTANDING = 'outstanding',
        CANCELED = 'canceled',
        PARTIAL = 'partial';

    protected $_valueLabels = [
        self::FREE => 'Free',
        self::UNPAID => 'Unpaid',
        self::PROCESSING => 'Processing',
        self::PAID => 'Paid',
        self::OUTSTANDING => 'Outstanding',
        self::CANCELED => 'Canceled',
        self::PARTIAL => 'Partial',
    ];

    protected $_defaultValue = self::UNPAID;

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

    public function setPaid()
    {
        return $this->changeState(self::PAID);
    }

    public function setOutstanding()
    {
        return $this->changeState(self::OUTSTANDING);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }

    public function isComplete()
    {
        return in_array($this->getValue(), [self::FREE, self::PAID]);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Item $model */
        $model = $this->getContext()->getModel();

        if ($model->get('row_total') == 0) {
            return $this->setFree();
        }
        if ($model->get('qty_paid') == $model->get('qty_ordered')) {
            return $this->setPaid();
        }
        if ($model->get('qty_paid') > 0) {
            return $this->setPartial();
        }

        return $this;
    }
}
