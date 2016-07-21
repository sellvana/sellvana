<?php
class Sellvana_PaymentAuthorizeNet_PaymentMethod_Sim extends Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm
{
    protected $_code = "authorizenet_sim";
    protected $_manualStateManagement = false;
    protected $_postParams = [];

    public function __construct()
    {
        parent::__construct();

        $this->_name = 'Authorize.net SIM';
        $this->_capabilities['capture'] = 1;
        $this->_capabilities['pay_online'] = 1;
        $this->_capabilities['void'] = 1;
        $this->_capabilities['void_online'] = 1;
        $this->_capabilities['refund_online'] = 1;
        $this->_capabilities['refund'] = 1;
        $this->_capabilities['pay_by_url'] = 1;
    }

    public function getCheckoutFormView()
    {
        return null;
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = $this->BConfig;
        return $config->get('modules/Sellvana_PaymentAuthorizeNet/sim');
    }

    public function getRelayUrl()
    {
        return $this->BApp->href("authorizenet/sim");
    }

    public function hiddenFields(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $config = $this->config();
        $fields = parent::hiddenFields($payment);
        $fields['x_show_form'] = 'PAYMENT_FORM';
        $fields['x_showform'] = 'PAYMENT_FORM';
        $fields['x_type'] = $config['payment_action'];
        $fields['x_method'] = 'CC';
        $fields['x_relay_url'] .= '?token=' . $payment->get('transaction_token');
        unset($fields['x_delim_char']);
        unset($fields['x_delim_data']);
        return $fields;
    }

    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $this->_order = $payment->order();
        $token = $this->BUtil->randomString(16);
        $payment->set([
            'transaction_type' => $this->getConfig("payment_action"),
            'transaction_token' => $token,
            'online' => 1,
        ])->save();

        $this->Sellvana_Sales_Main->workflowAction('customerStartsExternalPayment', ['payment' => $payment]);
        $result['redirect_to'] = $this->postUrl();
        $result['post_params'] = $this->hiddenFields($payment);

        return $result;
    }

    public function processReturnFromExternalCheckout()
    {
        $config        = $this->config();
        $apiResponse   = new AuthorizeNetSIM($config['login'], $config['trans_md5']);
        $response      = $this->BResponse;
        $result        = [];
        if ($apiResponse->isAuthorizeNet()) {
            $this->processApiResponse($apiResponse);
            if ($apiResponse->approved) {
                $redirect_url = $this->BApp->href('checkout/success') . '?response_code=1&transaction_id=' . $apiResponse->transaction_id;
            } else {
                // Redirect to error page.
                $redirect_url = $this->BApp->href('checkout/checkout') . '?response_code=' . $apiResponse->response_code
                    . '&response_reason_text=' . $apiResponse->response_reason_text;
                $result['error']['message'] = 'Error -- transaction was not approved. Reason: ' . $apiResponse->response_reason_text;
            }
            // Send the Javascript back to AuthorizeNet, which will redirect user back to your site.
            $response->set($this->BLayout->getView('authorizenet/dpm_relay')->set('redirect_url', $redirect_url)->render());

            $response->render();
        } else {
            $result['error']['message'] = 'Error -- not AuthorizeNet. Check your MD5 Setting.';
            $this->_setErrorStatus($result, true);
        }

        return $result;
    }


    protected function _getPostString()
    {
        $config = $this->config();
        $this->_postParams = array_merge($this->_postParams, [
            'login' => $config['login'],
            'tran_key' => $config['trans_key'],
        ]);

        $str = '';
        foreach ($this->_postParams as $key => $value) {
            $str .= $key . '=' . $value . '&';
        }
        return rtrim($str, '&');
    }

    /**
     * Posts the request to AuthorizeNet & returns response.
     */
    protected function _sendRequest()
    {
        $url = $this->postUrl();
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_POSTFIELDS, $this->_getPostString());
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_TIMEOUT, 45);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);

        if (preg_match('/xml/',$url)) {
            curl_setopt($request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        }

        $response = curl_exec($request);
        curl_close($request);

        return $response;
    }

}