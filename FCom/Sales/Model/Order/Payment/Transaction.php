<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Model_Order_Payment_Transaction
 *
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 */
class FCom_Sales_Model_Order_Payment_Transaction extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_payment_transaction';
    protected static $_origClass = __CLASS__;

    const SALE = 'sale', // immediate capture
        ORDER = 'order', // not even authorization, just confirming order transaction
        AUTHORIZATION = 'auth', // authorization of order transaction
        REAUTHORIZATION = 'reauth', // reauthorization after 3 days of authorization to secure funds
        CAPTURE = 'capture', // capture authorized transaction
        VOID = 'void', // void authorization
        REFUND = 'refund', // refund captured funds

        PENDING = 'pending', // pending processing
        STARTED = 'started', // started transaction
        COMPLETED = 'completed', // completed transaction
        EXPIRED = 'expired'; // for order and authorization

    protected static $_fieldOptions = [
        'transaction_type' => [
            self::SALE => 'Sale',
            self::ORDER => 'Order',
            self::AUTHORIZATION => 'Authorization',
            self::REAUTHORIZATION => 'Re-Authorization',
            self::CAPTURE => 'Capture',
            self::VOID => 'Void',
            self::REFUND => 'Refund',
        ],

        'transaction_status' => [
            self::PENDING => 'Pending',
            self::STARTED => 'Started',
            self::COMPLETED => 'Completed',
            self::EXPIRED => 'Expired',
        ],
    ];

    protected $_payment;

    /**
     * @return FCom_Sales_Model_Order_Payment
     * @throws BException
     */
    public function payment()
    {
        if (!$this->_payment) {
            $this->_payment = $this->FCom_Sales_Model_Order_Payment->load($this->get('payment_id'));
        }
        return $this->_payment;
    }

    public function setPayment(FCom_Sales_Model_Order_Payment $payment)
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
                $payment->state()->overall()->setProcessing();
                break;

            case self::AUTHORIZATION:
                $payment->add('amount_authorized', $amount);
                $payment->state()->overall()->setProcessing();
                break;

            case self::REAUTHORIZATION:
                $payment->state()->overall()->setProcessing();
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
                if ($order->get('amount_refunded') == $order->get('amount_captured')) {
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

    public function expireAuthorization()
    {
        $payment = $this->payment();
        $payment->add('amount_authorized', -$this->get('amount'));
        $this->set('transaction_status', self::EXPIRED)->save();
        $payment->save();
        return $this;
    }

    public function __destruct()
    {
        unset($this->_payment);
    }
}