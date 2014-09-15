<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Overall extends FCom_Sales_Model_Order_State_Abstract
{
    protected $_valueLabels = [
        'new' => 'New',
        'review' => 'Under Review',
        'processing' => 'Processing',
        'complete' => 'Complete',
        'canceled' => 'Canceled',
        'fraud' => 'Fraud',
        'archived' => 'Archived',
    ];

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setReview()
    {
        return $this->changeState('review');
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

    public function setFraud()
    {
        return $this->changeState('fraud');
    }

    public function setArchived()
    {
        return $this->changeState('archived');
    }
}
