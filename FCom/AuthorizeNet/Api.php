<?php

class FCom_AuthorizeNet_Api extends BClass
{
    const AUTHORIZENET_LOG_FILE = "authorize.net.log";
    protected $api;
    public function sale($order)
    {
        $api = $this->getApi();
    }

    public function getApi($code = 'aim')
    {
        if(null == $this->api){
            $conf = new BData(BConfig::i()->get('modules/FCom_AuthorizeNet'));
            if(!$data = $conf->get($code)){
                throw new BException("Invalid Authorize.net api: $code.");
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

            if($data->get('debug') && !defined('AUTHORIZENET_LOG_FILE')){
                define('AUTHORIZENET_LOG_FILE', static::AUTHORIZENET_LOG_FILE);
            }
            $this->api = new AuthorizeNetAIM();
        }
        return $this->api;
    }
}
