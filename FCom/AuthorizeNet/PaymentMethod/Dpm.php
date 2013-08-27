<?php

class FCom_AuthorizeNet_PaymentMethod_Dpm extends FCom_Sales_Method_Payment_Abstract
{

    const PAYMENT_METHOD_KEY = "authorizenet_dpm";

    function __construct()
    {
        $this->_name = 'Authorize.net Direct Post';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('authorizenet/dpm');
    }

    public function payOnCheckout()
    {
        return array();
    }

    public function getOrder()
    {
        return $this->salesEntity;
    }

    public function getCardNumber()
    {
        if (isset($this->details['cc_num'])) {
            return $this->details['cc_num'];
        }
        return null;
    }

    public function getDetail($key)
    {
        if (isset($this->details[$key])) {
            return $this->details[$key];
        }
        return null;
    }

    public function setDetail($key, $value)
    {
        $this->details[$key] = $value;
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = BConfig::i();
        return $config->get('modules/FCom_AuthorizeNet/dpm');
    }

    public function setDetails($details)
    {
        $details = isset($details[static::PAYMENT_METHOD_KEY]) ? $details[static::PAYMENT_METHOD_KEY] : array();

        return parent::setDetails($details);
    }

    public function getPublicData()
    {
        return $this->details;
    }

    public function asArray()
    {
        $data = parent::asArray();
        $data = array_merge($data, $this->getPublicData());
        return array( static::PAYMENT_METHOD_KEY => $data);
    }

    public function postUrl()
    {
        $config = $this->config();
        $post_url = ($config['test'] ? AuthorizeNetDPM::SANDBOX_URL : AuthorizeNetDPM::LIVE_URL);
        return $post_url;
    }

    public function hiddenFields()
    {
        $config = $this->config();
        $order = $this->getOrder();
        $time = time();
        $fields = array(
            'x_amount'         => $this->getDetail('amount_due'),
            'x_fp_sequence'    => $order->unique_id,
            'x_fp_hash'        => AuthorizeNetSIM_Form::getFingerprint($config['login'],
                                                                       $config['trans_key'],
                                                                       $this->getDetail('amount_due'),
                                                                       $order->unique_id, $time),
            'x_fp_timestamp'   => $time,
            'x_relay_response' => "TRUE",
            'x_relay_url'      => BApp::href("authorizenet/dpm"),
            'x_login'          => $config['login'],
        );

        return $fields;
    }
}
