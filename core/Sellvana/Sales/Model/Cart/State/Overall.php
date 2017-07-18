<?php

class Sellvana_Sales_Model_Cart_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    const ACTIVE = 'active',
        ORDERED = 'ordered',
        ABANDONED = 'abandoned',
        ARCHIVED = 'archived';

    protected $_valueLabels = [
        self::ACTIVE => (('Active')),
        self::ORDERED => (('Ordered')),
        self::ABANDONED => (('Abandoned')),
        self::ARCHIVED => (('Archived')),
    ];

    protected $_defaultValue = self::ACTIVE;

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
