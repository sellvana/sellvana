<?php

/**
 * Class Sellvana_Sales_Model_Order_Payment_State_Processor
 */
class Sellvana_Sales_Model_Order_Payment_State_Processor extends Sellvana_Sales_Model_Order_State_Abstract
{
    const NA = 'na',
        PENDING = 'pending',
        EXT_REDIRECTED = 'ext_redirected', // redirect to PayPal
        EXT_RETURNED = 'ext_returned', // redirect from PayPal
        ROOT_ORDER = 'root_order',
        AUTHORIZING = 'authorizing',
        AUTHORIZED = 'authorized',
        REAUTHORIZED = 'reauthorized',
        REFUSED = 'refused', // wrong CC
        EXPIRED = 'expired',
        CAPTURED = 'captured',
        PARTIAL_CAPTURED = 'partial_captured',
        SETTLED = 'settled',
        DECLINED = 'declined', // insufficient balance
        ERROR = 'error',
        CHARGEBACK = 'chargeback', // bank returns the money
        REFUNDED = 'refunded', // seller returns the money
        PARTIAL_REFUNDED = 'partial_refunded',
        VOID = 'void', // before settlement
        CANCELED = 'canceled'; // before capture

    protected $_valueLabels = [
        self::NA => 'N/A',
        self::PENDING => 'Pending',
        self::EXT_REDIRECTED => 'External Redirected',
        self::EXT_RETURNED => 'External Returned',
        self::ROOT_ORDER => 'Root Order', // not authorization or charge, a master payment to create authorizations from it
        self::AUTHORIZING => 'Authorizing', // while in process
        self::AUTHORIZED => 'Authorized',
        self::REAUTHORIZED => 'Re-Authorized',
        self::REFUSED => 'Refused',
        self::EXPIRED => 'Expired',
        self::CAPTURED => 'Captured',
        self::PARTIAL_CAPTURED => 'Partially Captured',
        self::DECLINED => 'Declined',
        self::ERROR => 'Error',
        self::CHARGEBACK => 'Charged Back',
        self::REFUNDED => 'Refunded',
        self::PARTIAL_REFUNDED => 'Partially Refunded',
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

    public function setReAuthorized()
    {
        return $this->changeState(self::REAUTHORIZED);
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

    public function setPartiallyCaptured()
    {
        return $this->changeState(self::PARTIAL_CAPTURED);
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

    public function setPartiallyRefunded()
    {
        return $this->changeState(self::PARTIAL_REFUNDED);
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
