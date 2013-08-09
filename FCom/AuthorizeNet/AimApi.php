<?php

class FCom_AuthorizeNet_AimApi extends BClass
{
    const AUTHORIZENET_LOG_FILE = "authorize.net.log";

    /**
     * @var AuthorizeNetAIM
     */
    protected $api;

    /**
     * @param FCom_AuthorizeNet_PaymentMethod $payment
     * @return AuthorizeNetAIM_Response
     */
    public function sale($payment)
    {
        $api           = $this->getApi();
        /* @var $order FCom_Sales_Model_Order */
        $order         = $payment->getOrder();
        $api->amount   = $payment->getDetail('amount_due');
        $api->card_num = $payment->getCardNumber();
        $api->exp_date = $payment->getDetail('card_exp_date');
        $api->invoice_num = $order->unique_id;
        $api->description = $order->getTextDescription();

        if(BConfig::i()->get('modules/FCom_AuthorizeNet/aim/useccv')){
            $api->card_code = $payment->getDetail('card_code');
        }
        $billing = $order->billing();
        if($billing->firstname){
            $api->first_name = $billing->firstname;
        }
        if($billing->lastname){
            $api->last_name = $billing->lastname;
        }
        if($billing->company){
            $api->company = $billing->company;
        }
        $api->address = $billing->getFullAddress();
        if ($billing->city) {
            $api->city = $billing->city;
        }
        if ($billing->region) {
            $api->state = $billing->region;
        }
        if ($billing->postcode) {
            $api->zip = $billing->postcode;
        }
        if ($billing->country) {
            $api->country = $billing->country;
        }
        if ($billing->phone) {
            $api->phone = $billing->phone;
        }
        if ($billing->fax) {
            $api->fax = $billing->fax;
        }
        if ($order->customer_email) {
            $api->email = $order->customer_email;
        }
        if ($order->customer_id) {
            $api->cust_id = $order->customer_id;
        }

        $api->po_num = $order->unique_id;
        $response      = $api->authorizeAndCapture();
        return $response;
    }

    /**
     * @param $payment
     * @return AuthorizeNetAIM_Response
     */
    public function authorize($payment)
    {
        $api = $this->getApi();
        // todo add payment details to $api
        $response = $api->authorizeOnly();
        return $response;
    }

    public function capture()
    {
        //
    }

    public function cancel()
    {
        //
    }

    public function void()
    {
        //
    }

    /**
     * @throws BException
     * @return AuthorizeNetAIM
     */
    public function getApi()
    {
        if (null == $this->api) {
            $conf = new BData(BConfig::i()->get('modules/FCom_AuthorizeNet'));
            if (!$data = $conf->get('aim')) {
                throw new BException("Invalid Authorize.net settings.");
            }
            $data = new BData($data);
            BDebug::log(print_r($data->as_array(), true));
            if (!defined('AUTHORIZENET_SANDBOX')) {
                define('AUTHORIZENET_SANDBOX', $data->get('test'));
            }

            if (!defined('AUTHORIZENET_API_LOGIN_ID')) {
                define('AUTHORIZENET_API_LOGIN_ID', $data->get('login'));
            }

            if (!defined('AUTHORIZENET_TRANSACTION_KEY')) {
                define('AUTHORIZENET_TRANSACTION_KEY', $data->get('trans_key'));
            }

            if ($data->get('debug') && !defined('AUTHORIZENET_LOG_FILE')) {
                define('AUTHORIZENET_LOG_FILE', static::AUTHORIZENET_LOG_FILE);
            }
            $this->api = new AuthorizeNetAIM();

            if($data->get('currency')){
                $this->api->currency_code = $data->get('currency');
            }
        }
        return $this->api;
    }
}
