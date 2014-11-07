<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentBasic_PaymentMethod
 */
class FCom_PaymentBasic_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    /**
     * @var
     */
    protected $_cart;
    /**
     * @var
     */
    protected $_order;

    /**
     * construct
     */
    function __construct()
    {
        $this->_name = 'Check / Money Order';
    }

    /**
     * @param $cart
     * @return $this
     */
    public function initCart($cart)
    {
        $this->_cart = $cart;
        return $this;
    }

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->view('check_mo/form');
    }

    /**
     * @return $this
     */
    public function payOnCheckout()
    {
        $this->authorize();
        return $this;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function capture()
    {
        return true;
    }
}
