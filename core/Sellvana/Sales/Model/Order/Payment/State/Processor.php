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
        self::PARTIAL_CAPTURED => 'Partial Captured',
        self::DECLINED => 'Declined',
        self::ERROR => 'Error',
        self::CHARGEBACK => 'Charged Back',
        self::REFUNDED => 'Refunded',
        self::PARTIAL_REFUNDED => 'Partial Refunded',
        self::VOID => 'Void',
        self::CANCELED => 'Canceled',
    ];

    protected $_defaultMethods = [
        self::NA => 'setNA',
        self::PENDING => 'setPending',
        self::EXT_REDIRECTED => 'setExtRedirected',
        self::EXT_RETURNED => 'setExtReturned',
        self::ROOT_ORDER => 'setRootOrder',
        self::AUTHORIZING => 'setAuthorizing',
        self::AUTHORIZED => 'setAuthorized',
        self::REAUTHORIZED => 'setReAuthorized',
        self::REFUSED => 'setRefused',
        self::EXPIRED => 'setExpired',
        self::CAPTURED => 'setCaptured',
        self::PARTIAL_CAPTURED => 'setPartialCaptured',
        self::DECLINED => 'setDeclined',
        self::ERROR => 'setError',
        self::CHARGEBACK => 'setChargedBack',
        self::REFUNDED => 'setRefunded',
        self::PARTIAL_REFUNDED => 'setPartialRefunded',
        self::VOID => 'setVoid',
        self::CANCELED => 'setCanceled',
    ];
    
    static protected $_transactionTypesToStates = [
        'capture' => self::CAPTURED,
        'auth' => self::AUTHORIZED,
        'reauth' => self::REAUTHORIZED,
        'refund' => self::REFUNDED,
        'void' => self::VOID,
    ];

    static protected $_transactionTypesToPartialStates = [
        'capture' => self::PARTIAL_CAPTURED,
        'refund' => self::PARTIAL_REFUNDED,
    ];

    static protected $_statesToTransactionTypes = [
        self::AUTHORIZED => [
            Sellvana_Sales_Model_Order_Payment_Transaction::REAUTHORIZATION,
            Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE,
            Sellvana_Sales_Model_Order_Payment_Transaction::VOID,
        ],
        self::REAUTHORIZED => [
            Sellvana_Sales_Model_Order_Payment_Transaction::REAUTHORIZATION,
            Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE,
            Sellvana_Sales_Model_Order_Payment_Transaction::VOID,
        ],
        self::CAPTURED => [
            Sellvana_Sales_Model_Order_Payment_Transaction::REFUND
        ],
        self::PARTIAL_CAPTURED => [
            Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE,
            Sellvana_Sales_Model_Order_Payment_Transaction::VOID,
            Sellvana_Sales_Model_Order_Payment_Transaction::REFUND,
        ],
        self::PARTIAL_REFUNDED => [
            Sellvana_Sales_Model_Order_Payment_Transaction::REFUND,
        ],
    ];

    public function invokeAction($action, $partial = false)
    {
        $map = $partial ? static::$_transactionTypesToPartialStates : static::$_transactionTypesToStates;
        if (empty($map[$action])) {
            throw new BException('Invalid transaction type: ' . $action);
        }
        return $this->invokeStateChange($map[$action]);
    }

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

    public function setPartialCaptured()
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

    public function setPartialRefunded()
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

    public function getAvailableTransactionTypes()
    {
        $types = [];
        if (array_key_exists($this->getValue(), self::$_statesToTransactionTypes)) {
            $types = self::$_statesToTransactionTypes[$this->getValue()];
        }

        return $types;
    }

    public function calcState()
    {
        /** @var Sellvana_Sales_Model_Order_Payment $payment */
        $payment = $this->getModel();
        switch ($this->getValue()) {
            case self::PENDING:
            case self::AUTHORIZED:
            case self::REAUTHORIZED:
            case self::PARTIAL_CAPTURED:
                if (!$payment->get('amount_captured')) {
                    return;
                }

                if ($payment->get('amount_due') > 0) {
                    $this->setPartialCaptured();
                } else {
                    $this->setCaptured();
                }
                break;
            case self::CAPTURED:
            case self::PARTIAL_REFUNDED:
                if (!$payment->get('amount_refunded')) {
                    return;
                }

                if ($payment->get('amount_refunded') < $payment->get('amount_captured')) {
                    $this->setPartialRefunded();
                } else {
                    $this->setRefunded();
                }
                break;
        }
    }
}
