<?php

/**
 * Class Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm
 *
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 */

class Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm extends Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim
{
    const LIVE_URL = 'https://secure.authorize.net/gateway/transact.dll';
    const SANDBOX_URL = 'https://test.authorize.net/gateway/transact.dll';

    protected $_code = "authorizenet_dpm";

    function __construct()
    {
        parent::__construct();
        $this->_name = 'Authorize.net Direct Post';
    }

    public function getCheckoutFormView()
    {
        return $this->BLayout->getView('authorizenet/dpm')->set('key', $this->_code);
    }

    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        return [];
    }

    public function getCardNumber()
    {
        if (isset($this->_details['cc_num'])) {
            return $this->_details['cc_num'];
        }
        return null;
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = $this->BConfig;
        return $config->get('modules/Sellvana_PaymentAuthorizeNet/dpm');
    }

    public function getPublicData()
    {
        return $this->_details;
    }

    public function getDataToSave()
    {
        return $this->_details;
    }

    public function postUrl()
    {
        $config = $this->config();
        $post_url = ($config['test'] ? self::SANDBOX_URL : self::LIVE_URL);
        return $post_url;
    }

    public function getRelayUrl()
    {
        return $this->BApp->href("authorizenet/dpm");
    }

    public function hiddenFields(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $config = $this->config();
        $order = $this->getOrder();
        $time = time();
        $fields = [
            "x_version"        => "3.1",
            "x_delim_char"     => ",",
            "x_delim_data"     => "TRUE",
            'x_amount'         => $payment->get('amount_due'),
            'x_fp_sequence'    => $order->unique_id,
            'x_fp_timestamp'   => $time,
            'x_relay_response' => "TRUE",
            'x_test_request'   => "FALSE",
            'x_relay_url'      => $this->getRelayUrl(),
            'x_login'          => $config['login'],
            //'x_description'    => $order->getTextDescription(),
        ];
        if (class_exists("AuthorizeNetSIM")) {
            $form = new AuthorizeNetSIM_Form();
            $fields['x_fp_hash'] = $form->getFingerprint($config['login'],
                                                            $config['trans_key'],
                                                            $payment->get('amount_due'),
                                                            $order->get('unique_id'), $time);
        }
        return $fields;
    }

    public function ajaxData()
    {
        $order = $this->getOrder();
        $data['order'] = $order->as_array();
        //TODO: check for duplicate fields, if necessary
        $data['billing']  = $order->addressAsArray('billing');
        $data['shipping'] = $order->addressAsArray('shipping');
        $data['x_fields'] = $this->hiddenFields();
        return $data;
    }

    /**
     * @param AuthorizeNetSIM $response
     * @return AuthorizeNetSIM
     */
    public function processApiResponse($response)
    {
        $config = $this->config();
        if (!$config['enabled']) {
            // log this and eventually show a message
            return null;
        }
        $action = $config['payment_action'];
        $this->set($response->transaction_id, $response);
        $this->set('transaction_id', $response->transaction_id);
        if ($response->approved) {
            $status = $action == 'AUTH_ONLY' ? 'authorized' : 'paid';
        } else {
            $status = 'error';
        }
        $paymentData = [
            'method'           => $this->_code,
            'parent_id'        => $response->transaction_id,
            'order_id'         => $response->fp_sequence,
            'amount'           => $this->get('amount_due'),
            'status'           => $status,
            'transaction_id'   => $response->transaction_id,
            'transaction_type' => $action == 'AUTH_ONLY' ? 'authorize' : 'sale',
            'online'           => 1,
        ];
        $paymentModel = $this->Sellvana_Sales_Model_Order_Payment->addNew($paymentData);
        $paymentModel->setData('response', $response);
        $paymentModel->save();
        return $response;
    }
}
