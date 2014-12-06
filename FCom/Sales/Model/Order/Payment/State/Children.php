<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Children extends FCom_Core_Model_Abstract_State_Concrete
{
    const NONE = 'none',
        PENDING = 'pending',
        PARTIAL = 'partial',
        COMPLETE = 'complete';

    protected $_valueLabels = [
        self::NONE => 'None',
        self::PENDING => 'Pending',
        self::PARTIAL => 'Partial',
        self::COMPLETE => 'Complete',
    ];

    public function setNone()
    {
        return $this->changeState(self::NONE);
    }

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setPartial()
    {
        return $this->changeState(self::PARTIAL);
    }

    public function setComplete()
    {
        return $this->changeState(self::COMPLETE);
    }
}
