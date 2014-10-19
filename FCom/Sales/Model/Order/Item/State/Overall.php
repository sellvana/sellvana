<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Item_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'new' => 'New',
        'backordered' => 'Backordered',
        'processing' => 'Processing',
        'complete' => 'Complete',
        'canceled' => 'Canceled',
    ];

    protected $_setValueNotificationTemplates = [
        'backordered' => 'email/sales/order-item-state-overall-backordered',
        'canceled' => 'email/sales/order-item-state-overall-canceled',
    ];

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setBackordered()
    {
        return $this->changeState('backordered');
    }

    public function setProcessing()
    {
        return $this->changeState('processing');
    }

    public function setComplete()
    {
        return $this->changeState('complete');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }
}
