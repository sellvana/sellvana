<?php

class FCom_AuthorizeNet_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{

    const PAYMENT_METHOD_KEY = "authorizenet_aim";

    function __construct()
    {
        $this->_name = 'Authorize.net';
    }

    public function getCheckoutFormView()
    {
        return BLayout::i()->view('authorizenet/form')->set('key', static::PAYMENT_METHOD_KEY);
    }

    public function payOnCheckout()
    {
        $config = $this->config();
        if (!$config['enabled']) {
            // log this and eventually show a message
            return null;
        }
        $action = $config['payment_action'];

        /* @var $api FCom_AuthorizeNet_AimApi */
        $api = FCom_AuthorizeNet_AimApi::i();
        switch ($action) {
            case 'AUTH_ONLY':
                $response = $api->authorize($this);
                break;
            case 'AUTH_CAPTURE':
                $response = $api->sale($this);
                break;
            default :
                // log and show message
                return null;
                break;
        }
        $this->setDetail($response['transaction_id'], $response);
        $this->setDetail('transaction_id', $response['transaction_id']);
        $this->clear();
        return $response;
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
     * @return array
     */
    public function cardTypes()
    {
        return FCom_AuthorizeNet_Model_Settings::i()->cardTypes();
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = BConfig::i();
        return $config->get('modules/FCom_AuthorizeNet/aim');
    }

    public function setDetails($details)
    {
        $details = isset($details[static::PAYMENT_METHOD_KEY]) ? $details[static::PAYMENT_METHOD_KEY] : array();

        return parent::setDetails($details);
    }

    public function getPublicData()
    {
        $data = $this->details;
        if (!empty($data) && isset($data['cc_num'])) {
            $ccFour = substr($data['cc_num'], -4);
            unset($data['cc_num']);
            $data['last_four'] = $ccFour;
        }
        return $data;
    }

    protected function clear()
    {
        unset($this->details['cc_num']);
    }

    public function asArray()
    {
        $data = parent::asArray();
        $data = array_merge($data, $this->getPublicData());
        return array( static::PAYMENT_METHOD_KEY => $data);
    }

}
