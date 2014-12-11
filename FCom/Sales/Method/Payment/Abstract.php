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
     * @var FCom_Sales_Model_Order_Transaction
     */
    protected $_transaction;

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
        'pay'             => 1,
        'pay_online'      => 0,
        'auth'            => 0,
        'auth_partial'    => 0,
        'reauth'          => 0,
        'void'            => 0,
        'void_online'     => 0,
        'capture'         => 0,
        'capture_partial' => 0,
        'refund'          => 0,
        'refund_partial'  => 0,
        'refund_online'   => 0,
        'recurring'       => 0,
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
     * @param FCom_Sales_Model_Order_Payment $payment
     * @return mixed
     */
    public function payOnCheckout(FCom_Sales_Model_Order_Payment $payment)
    {

    }

    public function authorize(FCom_Sales_Model_Order_Payment_Transaction $transaction)
    {

    }

    public function reauthorize(FCom_Sales_Model_Order_Payment_Transaction $transaction)
    {

    }

    public function void(FCom_Sales_Model_Order_Payment_Transaction $transaction)
    {

    }

    public function capture(FCom_Sales_Model_Order_Payment_Transaction $transaction)
    {

    }

    public function refund(FCom_Sales_Model_Order_Payment_Transaction $transaction)
    {

    }

    /**
     * Shortcut for payment gateway error
     *
     * @param array $result
     */
    protected function _setErrorStatus($result = null)
    {
        $this->FCom_Sales_Main->workflowAction('customerGetsPaymentError', [
            'payment' => $this->_payment,
            'result' => $result,
        ]);
    }

    public function __destruct()
    {
        unset($this->_payment, $this->_transaction);
    }
}
