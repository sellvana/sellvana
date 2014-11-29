<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Created by pp
 * Class FCom_AuthorizeNet_Frontend_Controller
 * @project fulleron
 *
 * @property FCom_AuthorizeNet_PaymentMethod_Dpm $FCom_AuthorizeNet_PaymentMethod_Dpm
 */

class FCom_AuthorizeNet_Frontend_Controller extends FCom_Core_Controller_Abstract
{
    public function action_dpm()
    {
        /* @var $paymentMethod FCom_AuthorizeNet_PaymentMethod_Dpm */
        $paymentMethod = $this->FCom_AuthorizeNet_PaymentMethod_Dpm;
        $config        = $paymentMethod->config();
        $apiResponse   = new AuthorizeNetSIM($config['login'], $config['trans_md5']);
        $response      = $this->BResponse;
        if ($apiResponse->isAuthorizeNet()) {
            $paymentMethod->processApiResponse($apiResponse);
            if ($apiResponse->approved) {
                $redirect_url = $this->BApp->href('checkout/success') . '?response_code=1&transaction_id=' . $apiResponse->transaction_id;
            } else {
                // Redirect to error page.
                $redirect_url = $this->BApp->href('checkout/checkout') . '?response_code=' . $apiResponse->response_code
                    . '&response_reason_text=' . $apiResponse->response_reason_text;
            }
            // Send the Javascript back to AuthorizeNet, which will redirect user back to your site.
            $response->set($this->BLayout->getView('authorizenet/dpm_relay')->set('redirect_url', $redirect_url)->render());

            $response->render();
        } else {
            $this->message("Error -- not AuthorizeNet. Check your MD5 Setting.", 'error');
            $response->redirect('checkout/checkout');
        }
    }
}
