<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PayPal_PaymentMethod
 *
 * @property FCom_PayPal_RemoteApi $FCom_PayPal_RemoteApi
 */
class FCom_PayPal_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    public function __construct()
    {
        $this->_name = 'PayPal Express Checkout';
    }

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->view('paypal/form');
    }

    public function payOnCheckout()
    {
        if (!$this->_order) {
            return [
                'error' => 'No order',
                'redirect_to' => $this->BApp->href('cart'),
            ];
        }
        $result = $this->FCom_PayPal_RemoteApi->callSetExpressCheckout($this->_order);
        return $result;
    }
}
