<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Cart_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'active' => 'Active',
        'ordered' => 'Ordered',
        'abandoned' => 'Abandoned',
        'archived' => 'Archived',
    ];

    public function setActive()
    {
        return $this->changeState('active');
    }

    public function setOrdered()
    {
        return $this->changeState('ordered');
    }

    public function setAbandoned()
    {
        return $this->changeState('abandoned');
    }

    public function setArchived()
    {
        return $this->changeState('archived');
    }
}
