<?php

/**
 * Class Sellvana_Sales_Model_Order_Payment_Transaction
 *
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order_Payment_Transaction $Sellvana_Sales_Model_Order_Payment_Transaction
 */
class Sellvana_Sales_Model_Order_Payment_Transaction extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_payment_transaction';
    protected static $_origClass = __CLASS__;

    const SALE = 'sale', // immediate capture
        ORDER = 'order', // not even authorization, just confirming order transaction
        AUTHORIZATION = 'auth', // authorization of order transaction
        REAUTHORIZATION = 'reauth', // reauthorization after 3 days of authorization to secure funds
        CAPTURE = 'capture', // capture authorized transaction
        REFUND = 'refund', // refund captured funds

        PENDING = 'pending', // pending processing
        STARTED = 'started', // started transaction
        COMPLETED = 'completed', // completed transaction
        VOID = 'void', // void authorization
        EXPIRED = 'expired'; // for order and authorization

    protected static $_fieldOptions = [
        'transaction_type' => [
            self::SALE => 'Sale',
            self::ORDER => 'Order',
            self::AUTHORIZATION => 'Authorization',
            self::REAUTHORIZATION => 'Re-Authorization',
            self::CAPTURE => 'Capture',
            self::REFUND => 'Refund',
            self::VOID => 'Void',
        ],

        'transaction_status' => [
            self::PENDING => 'Pending',
            self::STARTED => 'Started',
            self::COMPLETED => 'Completed',
            self::VOID => 'Void',
            self::EXPIRED => 'Expired',
        ],
    ];

    protected $_payment;

    static protected $_availableActions = [
        self::ORDER => [
            self::AUTHORIZATION => true,
        ],
        self::AUTHORIZATION => [
            self::REAUTHORIZATION => false,
            self::CAPTURE => true,
            self::VOID => false,
        ],
        self::REAUTHORIZATION => [
            self::REAUTHORIZATION => false,
            self::CAPTURE => true,
            self::VOID => false,
        ],
        self::CAPTURE => [
            self::REFUND => true,
        ],
        self::SALE => [
            self::REFUND => true,
        ],
    ];

    /**
     * @return Sellvana_Sales_Model_Order_Payment
     * @throws BException
     */
    public function payment()
    {
        if (!$this->_payment) {
            $this->_payment = $this->Sellvana_Sales_Model_Order_Payment->load($this->get('payment_id'));
        }
        return $this->_payment;
    }

    public function setPayment(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $this->_payment = $payment;
        return $this;
    }

    /**
     * @return $this
     * @throws BException
     */
    public function start()
    {
        $payment = $this->payment();
        switch ($this->get('transaction_type')) {
            case self::SALE:
                break;

            case self::ORDER:
                break;

            case self::AUTHORIZATION:
                break;

            case self::REAUTHORIZATION:
                break;

            case self::CAPTURE:
                break;

            case self::VOID:
                break;

            case self::REFUND:
                break;
        }
        $this->set('transaction_status', self::STARTED);
        $this->save();
        return $this;
    }

    public function complete($parent = null)
    {
        $payment = $this->payment();
        $order = $payment->order();
        $amount = $this->get('amount');
        switch ($this->get('transaction_type')) {
            case self::SALE:
                if ($parent) {
                    $parent->add('amount_captured', $amount);
                }

                $payment->add('amount_captured', $amount);
                $payment->add('amount_due', -$amount);
                $payment->state()->overall()->setPaid();

                $order->add('amount_paid', $amount);
                $order->add('amount_due', -$amount);
                $order->state()->payment()->setPaid();
                break;

            case self::ORDER:
                break;

            case self::AUTHORIZATION:
                if ($parent) {
                    $parent->add('amount_authorized', $amount);
                }

                $payment->add('amount_authorized', $amount);
                break;

            case self::REAUTHORIZATION:
                break;

            case self::CAPTURE:
                if ($parent) {
                    $parent->add('amount_captured', $amount);
                }

                $payment->add('amount_captured', $amount);
                $payment->add('amount_authorized', -$amount);

                $payment->add('amount_due', -$amount);
                if ($payment->get('amount_due') < .01) {
                    $payment->state()->overall()->setPaid();
                } else {
                    $payment->state()->overall()->setPartialPaid();
                }

                $order->add('amount_paid', $amount);
                $order->add('amount_due', -$amount);
                if ($order->get('amount_due') < .01) {
                    $order->state()->payment()->setPaid();
                } else {
                    $order->state()->payment()->setPartialPaid();
                }
                break;

            case self::VOID:
                $payment->add('amount_authorized', -$amount);
                break;

            case self::REFUND:
                if ($parent) {
                    $parent->add('amount_refunded', $amount);
                }

                $payment->add('amount_refunded', $amount);
                if (abs($payment->get('amount_refunded') - $payment->get('amount_captured')) < .01) {
                    $payment->state()->overall()->setRefunded();
                } else {
                    $payment->state()->overall()->setPartialRefunded();
                }

                $order->add('amount_refunded', $amount);
                if (abs($order->get('amount_refunded') - $order->get('amount_paid')) < .01) {
                    $order->state()->payment()->setRefunded();
                } else {
                    $order->state()->payment()->setPartialRefunded();
                }
                break;
        }
        $this->set('transaction_status', self::COMPLETED)->save();
        if ($parent) {
            $parent->save();
        }
        $payment->save();
        $order->save();
        return $this;
    }

    public function void()
    {
        $this->set('transaction_status', self::VOID)->save();
        return $this;
    }

    public function expireAuthorization()
    {
        $payment = $this->payment();
        $payment->add('amount_authorized', -$this->get('amount'));
        $this->set('transaction_status', self::EXPIRED)->save();
        $payment->save();
        return $this;
    }


    public function getAvailableActions()
    {
        if ($this->get('transaction_status') !== self::COMPLETED) {
            return [];
        }

        $transType = $this->get('transaction_type');
        $payment = $this->payment();
        $types = [];
        if (array_key_exists($transType, self::$_availableActions)) {
            $types = self::$_availableActions[$transType];
        }

        $result = [];
        foreach ($types as $type => $partial) {
            $typeLabels = $this->fieldOptions('transaction_type');
            if (empty($typeLabels[$type])) {
                continue;
            }
            $paymentMethod = $payment->getMethodObject();

            $partial = $partial && $paymentMethod && !empty($paymentMethod->can($type . '_partial'));

            $curHlp     = $this->BCurrencyValue;
            $maxAmount = $curHlp->create($this->get('amount'));
            if ($partial) {
                $map = [
                    self::AUTHORIZATION => 'amount_authorized',
                    self::CAPTURE => 'amount_captured',
                    self::REFUND => 'amount_refunded',
                ];
                if (!empty($map[$type])) {
                    $maxAmount->subtract($curHlp->create($this->get($map[$type])));
                }
            }
            $maxAmount = $maxAmount->getAmount(true);

            if (!$partial || $maxAmount > 0) {
                $result[$type] = [
                    'label' => $typeLabels[$type],
                    'max_amount' => $maxAmount,
                    'partial' => $partial,
                ];
            }
        }
        return $result;
    }

    public function __destruct()
    {
        parent::__destruct();
        unset($this->_payment);
    }
}