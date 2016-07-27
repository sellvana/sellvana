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

    static protected $_actions = [
        self::ORDER => [
            self::AUTHORIZATION,
        ],
        self::AUTHORIZATION => [
            self::REAUTHORIZATION,
            self::CAPTURE,
            self::VOID,
        ],
        self::REAUTHORIZATION => [
            self::REAUTHORIZATION,
            self::CAPTURE,
            self::VOID,
        ],
        self::CAPTURE => [
            self::REFUND
        ],
        self::SALE => [
            self::REFUND
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

    public function complete()
    {
        $payment = $this->payment();
        $order = $payment->order();
        $amount = $this->get('amount');
        switch ($this->get('transaction_type')) {
            case self::SALE:
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
                $payment->add('amount_authorized', $amount);
                break;

            case self::REAUTHORIZATION:
                break;

            case self::CAPTURE:
                $payment->add('amount_captured', $amount);
                $payment->add('amount_authorized', -$amount);

                $payment->add('amount_due', -$amount);
                if ($payment->get('amount_due') == 0) {
                    $payment->state()->overall()->setPaid();
                } else {
                    $payment->state()->overall()->setPartialPaid();
                }

                $order->add('amount_paid', $amount);
                $order->add('amount_due', -$amount);
                if ($order->get('amount_due') == 0) {
                    $order->state()->payment()->setPaid();
                } else {
                    $order->state()->payment()->setPartialPaid();
                }
                break;

            case self::VOID:
                $payment->add('amount_authorized', -$amount);
                break;

            case self::REFUND:
                $payment->add('amount_refunded', $amount);
                if ($payment->get('amount_refunded') == $payment->get('amount_captured')) {
                    $payment->state()->overall()->setRefunded();
                } else {
                    $payment->state()->overall()->setPartialRefunded();
                }

                $order->add('amount_refunded', $amount);
                if ($order->get('amount_refunded') == $order->get('amount_paid')) {
                    $order->state()->payment()->setRefunded();
                } else {
                    $order->state()->payment()->setPartialRefunded();
                }
                break;
        }
        $this->set('transaction_status', self::COMPLETED)->save();
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

    /**
     * Get maximum amount available for transaction type
     *
     * @param string $type
     * @return mixed
     */
    protected function _getMaxAvailableAmountForAction($type)
    {
        $payment = $this->payment();

        if (!in_array($type, [self::AUTHORIZATION, self::CAPTURE, self::REFUND])) {
            return null;
        }

        if (!$payment->getMethodObject()->can($type . '_partial')) {
            return null;
        }

        $transactions = $payment->findTransaction(
            [$type], 'completed', null, true, $this->get('transaction_id')
        );


        $amount = $this->get('amount');
        foreach ($transactions as $transaction) {
            if ($transaction->id() == $this->id()) {
                continue;
            }

            $amount -= $transaction->get('amount');
        }

        return $amount;
    }

    public function getAvailableActions()
    {
        $currentType = $this->get('transaction_type');
        $newTypes = [];
        if (array_key_exists($currentType, self::$_actions)) {
            $newTypes = self::$_actions[$currentType];
        }

        $result = [];
        foreach ($newTypes as $type) {
            $typeLabels = self::$_fieldOptions['transaction_type'];
            if (!array_key_exists($type, $typeLabels)) {
                continue;
            }

            $types = [$type];
            if (in_array($type, [self::REAUTHORIZATION, self::VOID])) {
                $types = [self::CAPTURE];
            }
            $transactions = $this->payment()->findTransaction($types, 'completed', null, true, $this->get('transaction_id'));
            $amount = $this->get('amount');
            foreach ($transactions as $transaction) {
                $amount -= $transaction->get('amount');
            }

            if (count($transactions) == 0 || $amount > 0) {
                $result[$type] = [
                    'label' => $typeLabels[$type],
                    'maxAmount' => $this->_getMaxAvailableAmountForAction($type),
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