<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentCC_PaymentMethod
 */
class FCom_PaymentCC_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Credit Card';
    }

    /**
     *
     */
    public function payOnCheckout()
    {
        die("Credit Card payment not imlemented yet");
    }

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->view('credit_card/form');
    }
}
