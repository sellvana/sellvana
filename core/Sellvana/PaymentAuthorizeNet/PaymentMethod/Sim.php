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

    protected function _specialFields()
    {
        return [
            "x_show_form"     => "PAYMENT_FORM",
            "x_showform"     => "PAYMENT_FORM",
        ];
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