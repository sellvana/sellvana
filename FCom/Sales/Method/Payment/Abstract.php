<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Sales_Method_Payment_Abstract
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 */
abstract class FCom_Sales_Method_Payment_Abstract extends BClass implements
    FCom_Sales_Method_Payment_Interface
{
    /**
     * @var FCom_Sales_Model_Order_Payment
     */
    protected $_payment;

    /**
     * @var array
     */
    protected $_details;

    /**
     * @var int
     */
    protected $_sortOrder = 50;

    /**
     * @var string
     */
    protected $_name;

    /**
     * @var array
     */
    protected $_config;

    /**
     * @var array
     */
    protected $_capabilities = [
        'pay'           => 1,
        'refund'        => 1,
        'void'          => 1,
        'recurring'     => 0,
        'pay_partial'   => 0,
        'pay_online'    => 0,
        'refund_online' => 0,
        'void_online'   => 0,
    ];

    public function can($capability)
    {
        if (isset($this->_capabilities[strtolower($capability)])) {
            return (bool) $this->_capabilities[strtolower($capability)];
        }
        return false;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * Set any details gathered during checkout process
     * @param array $details
     * @return $this
     */
    public function setDetails($details)
    {
        $this->_details = $details;
        return $this;
    }

    /**
     * Get public data
     *
     * Get data which can be saved, should not include any sensitive data such as credit card numbers, personal ids, etc.
     * @return array
     */
    public function getPublicData()
    {
        return $this->_details;
    }

    /**
     * @param $cart
     * @return $this
     * @internal This replaces initCart in basic payment
     */
    public function setPaymentModel(FCom_Sales_Model_Order_Payment $payment)
    {
        $this->_payment = $payment;
        return $this;
    }

    public function asArray()
    {
        return ["name" => $this->getName()];
    }

    public function set($name, $value)
    {
        return $this->_details[$name] = $value;
    }

    public function get($name, $default = null)
    {
        return isset($this->_details[$name]) ? $this->_details[$name] : $default;
    }

    /**
     * Shortcut for payment gateway error
     */
    protected function _setErrorStatus()
    {
        $this->FCom_Sales_Main->workflowAction('customerGetsPaymentError', ['payment' => $this->_payment]);
    }

    protected function _onBeforeAuthorization()
    {
        $p = $this->_payment;
        $p->state()->overall()->setPending();
        $p->state()->processor()->setAuthorizing();
        $p->save();
    }

    protected function _onSuccessfulAuthorization()
    {
        $p = $this->_payment;
        $amt = $p->get('amount_due');
        $p->set('amount_authorized', $amt);
        $p->state()->overall()->setProcessing();
        $p->state()->processor()->setAuthorized();
        $p->save();
    }

    protected function _onBeforeCapture()
    {

    }

    protected function _onSuccessfulCapture()
    {
        $p = $this->_payment;
        $amt = $p->get('amount_authorized');
        $p->set([
            'amount_captured' => $amt,
            'amount_due' => $p->get('amount_due') - $amt,
        ]);

        $p->state()->overall()->setPaid();
        $p->state()->processor()->setCaptured();
        $p->save();

        $order = $p->order();

    }

    protected function _onBeforeVoid()
    {

    }

    protected function _onSuccessfulVoid()
    {

    }

    protected function _onBeforeRefund()
    {

    }

    protected function _onSuccessfulRefund()
    {

    }
}
