<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Item_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    const PENDING = 'pending',
        BACKORDERED = 'backordered',
        PROCESSING = 'processing',
        COMPLETE = 'complete',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::PENDING => 'Pending',
        self::BACKORDERED => 'Backordered',
        self::PROCESSING => 'Processing',
        self::COMPLETE => 'Complete',
        self::CANCELED => 'Canceled',
    ];

    protected $_setValueNotificationTemplates = [
        self::BACKORDERED => 'email/sales/order-item-state-overall-backordered',
        self::CANCELED => 'email/sales/order-item-state-overall-canceled',
    ];

    public function setPending()
    {
        return $this->changestate(self::PENDING);
    }

    public function setBackordered()
    {
        return $this->changestate(self::BACKORDERED);
    }

    public function setProcessing()
    {
        return $this->changestate(self::PROCESSING);
    }

    public function setComplete()
    {
        return $this->changestate(self::COMPLETE);
    }

    public function setCanceled()
    {
        return $this->changestate(self::CANCELED);
    }
}
