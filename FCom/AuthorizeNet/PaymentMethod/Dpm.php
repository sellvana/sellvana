<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AuthorizeNet_PaymentMethod_Dpm
 *
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 */

class FCom_AuthorizeNet_PaymentMethod_Dpm extends FCom_AuthorizeNet_PaymentMethod_Aim
{

    const PAYMENT_METHOD_KEY = "authorizenet_dpm";

    function __construct()
    {
        parent::__construct();
        $this->_name = 'Authorize.net Direct Post';
    }

    public function getCheckoutFormView()
    {
        return $this->BLayout->view('authorizenet/dpm')->set('key', static::PAYMENT_METHOD_KEY);
    }

    public function payOnCheckout(FCom_Sales_Model_Order_Payment $payment)
    {
        return [];
    }

    public function getOrder()
    {
        return $this->_order;
    }

    public function getCardNumber()
    {
        if (isset($this->_details['cc_num'])) {
            return $this->_details['cc_num'];
        }
        return null;
    }

    public function getDetail($key)
    {
        if (isset($this->_details[$key])) {
            return $this->_details[$key];
        }
        return null;
    }

    public function setDetail($key, $value)
    {
        $this->_details[$key] = $value;
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = $this->BConfig;
        return $config->get('modules/FCom_AuthorizeNet/dpm');
    }

    public function setDetails($details)
    {
        $details = isset($details[static::PAYMENT_METHOD_KEY]) ? $details[static::PAYMENT_METHOD_KEY] : [];

        return parent::setDetails($details);
    }

    public function getPublicData()
    {
        return $this->_details;
    }

    public function asArray()
    {
        $data = parent::asArray();
        $data = array_merge($data, $this->getPublicData());
        return [static::PAYMENT_METHOD_KEY => $data];
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
        $fields = [
            "x_version"        => "3.1",
            "x_delim_char"     => ",",
            "x_delim_data"     => "TRUE",
            'x_amount'         => $this->getDetail('amount_due'),
            'x_fp_sequence'    => $order->unique_id,
            'x_fp_timestamp'   => $time,
            'x_relay_response' => "TRUE",
            'x_relay_url'      => $this->BApp->href("authorizenet/dpm"),
            'x_login'          => $config['login'],
            'x_description'    => $order->getTextDescription(),
        ];
        if (class_exists("AuthorizeNetSIM")) {
            $fields['x_fp_hash'] = AuthorizeNetSIM_Form::getFingerprint($config['login'],
                                                                        $config['trans_key'],
                                                                        $this->getDetail('amount_due'),
                                                                        $order->unique_id, $time);
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
        $this->setDetail($response->transaction_id, $response);
        $this->setDetail('transaction_id', $response->transaction_id);
        if ($response->approved) {
            $status = $action == 'AUTH_ONLY' ? 'authorized' : 'paid';
        } else {
            $status = 'error';
        }
        $paymentData = [
            'method'           => static::PAYMENT_METHOD_KEY,
            'parent_id'        => $response->transaction_id,
            'order_id'         => $response->fp_sequence,
            'amount'           => $this->getDetail('amount_due'),
            'status'           => $status,
            'transaction_id'   => $response->transaction_id,
            'transaction_type' => $action == 'AUTH_ONLY' ? 'authorize' : 'sale',
            'online'           => 1,
        ];
        $paymentModel = $this->FCom_Sales_Model_Order_Payment->addNew($paymentData);
        $paymentModel->setData('response', $response);
        $paymentModel->save();
        return $response;
    }
}
