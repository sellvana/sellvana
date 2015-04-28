<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Item_State_Cancel extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NONE = 'none',
        PROCESSING = 'processing',
        PARTIAL = 'partial',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::NONE => 'None',
        self::PROCESSING => 'Processing',
        self::PARTIAL => 'Partial',
        self::CANCELED => 'Canceled',
    ];

    protected $_defaultValue = self::NONE;

    public function setNone()
    {
        return $this->changeState(self::NONE);
    }

    public function setProcessing()
    {
        return $this->changeState(self::PROCESSING);
    }

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Item $model */
        $model = $this->getContext()->getModel();

        if ($model->get('qty_canceled') == $model->get('qty_ordered')) {
            return $this->setCanceled();
        }
        if ($model->get('qty_canceled') > 0) {
            return $this->setPartial();
        }

        return $this;
    }
}
