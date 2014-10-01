<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'new' => 'New',
        'review' => 'Under Review',
        'fraud' => 'Fraud',
        'legit' => 'Passed Verification',
        'processing' => 'Processing',
        'complete' => 'Complete',
        'canceled' => 'Canceled',
        'archived' => 'Archived',
    ];

    protected $_setValueNotificationTemplates =[
        'new' => 'email/sales/order-state-overall-new',
        'review' => 'email/sales/order-state-overall-review',
        'fraud' => 'email/sales/order-state-overall-fraud',
        'legit' => 'email/sales/order-state-overall-legit',
        'canceled' => 'email/sales/order-state-overall-canceled',
    ];

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setReview()
    {
        return $this->changeState('review');
    }

    public function setLegit()
    {
        return $this->changeState('legit');
    }

    public function setFraud()
    {
        return $this->changeState('fraud');
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

    public function setArchived()
    {
        return $this->changeState('archived');
    }
}
