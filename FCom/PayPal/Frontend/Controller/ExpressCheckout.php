<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PayPal_Frontend_Controller
 *
 * @property FCom_PayPal_PaymentMethod_ExpressCheckout $FCom_PayPal_PaymentMethod_ExpressCheckout
 */
class FCom_PayPal_Frontend_Controller_ExpressCheckout extends FCom_Frontend_Controller_Abstract
{
    public function action_return()
    {
        $result = $this->FCom_PayPal_PaymentMethod_ExpressCheckout->processReturnFromExternalCheckout();
        if (!empty($result['error'])) {
            $this->message($result['error']['message'], 'error');
            $this->BResponse->redirect('checkout');
            return;
        }

        $this->BResponse->redirect('checkout/success');
    }

    public function action_cancel()
    {
        $this->BResponse->redirect($this->BConfig->get('secure_url') . "/checkout");
    }

}
