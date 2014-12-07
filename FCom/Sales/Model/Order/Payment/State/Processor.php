<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State_Processor extends FCom_Core_Model_Abstract_State_Concrete
{
    const NA = 'na',
        PENDING = 'pending',
        EXT_REDIRECTED = 'ext_redirected',
        EXT_RETURNED = 'ext_returned',
        ROOT_ORDER = 'root_order',
        AUTHORIZING = 'authorizing',
        AUTHORIZED = 'authorized',
        REFUSED = 'refused',
        EXPIRED = 'expired',
        CAPTURED = 'captured',
        DECLINED = 'declined',
        ERROR = 'error',
        CHARGEBACK = 'chargeback',
        REFUNDED = 'refunded',
        VOID = 'void',
        CANCELED = 'canceled';

    protected $_valueLabels = [
        self::NA => 'N/A',
        self::PENDING => 'Pending',
        self::EXT_REDIRECTED => 'External Redirected',
        self::EXT_RETURNED => 'External Returned',
        self::ROOT_ORDER => 'Root Order', // not authorization or charge, a master payment to create authorizations from it
        self::AUTHORIZING => 'Authorizing', // while in process
        self::AUTHORIZED => 'Authorized',
        self::REFUSED => 'Refused',
        self::EXPIRED => 'Expired',
        self::CAPTURED => 'Captured',
        self::DECLINED => 'Declined',
        self::ERROR => 'Error',
        self::CHARGEBACK => 'Charged Back',
        self::REFUNDED => 'Refunded',
        self::VOID => 'Void',
        self::CANCELED => 'Canceled',
    ];

    public function setNA()
    {
        return $this->changeState(self::NA);
    }

    public function setPending()
    {
        return $this->changeState(self::PENDING);
    }

    public function setExtRedirected()
    {
        return $this->changeState(self::EXT_REDIRECTED);
    }

    public function setExtReturned()
    {
        return $this->changeState(self::EXT_RETURNED);
    }

    public function setRootOrder()
    {
        return $this->changeState(self::ROOT_ORDER);
    }

    public function setAuthorizing()
    {
        return $this->changeState(self::AUTHORIZING);
    }

    public function setAuthorized()
    {
        return $this->changeState(self::AUTHORIZED);
    }

    public function setRefused()
    {
        return $this->changeState(self::REFUSED);
    }

    public function setExpired()
    {
        return $this->changeState(self::EXPIRED);
    }

    public function setCaptured()
    {
        return $this->changeState(self::CAPTURED);
    }

    public function setDeclined()
    {
        return $this->changeState(self::DECLINED);
    }

    public function setError()
    {
        return $this->changeState(self::ERROR);
    }

    public function setChargedBack()
    {
        return $this->changeState(self::CHARGEBACK);
    }

    public function setRefunded()
    {
        return $this->changeState(self::REFUNDED);
    }

    public function setVoid()
    {
        return $this->changeState(self::VOID);
    }

    public function setCanceled()
    {
        return $this->changeState(self::CANCELED);
    }
}
