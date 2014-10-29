<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_AuthorizeNet_AimApi extends BClass
{
    const AUTHORIZENET_LOG_FILE = "authorize.net.log";
    protected $response_vars = [];

    /**
     * @var AuthorizeNetAIM
     */
    protected $api;

    /**
     * @param FCom_AuthorizeNet_PaymentMethod_Aim $payment
     * @return array
     */
    public function sale($payment)
    {
        $api           = $this->getApi();
        /* @var $order FCom_Sales_Model_Order */
        $order         = $payment->getOrder();
        $this->setSaleDetails($api, $payment, $order);
        $response      = $api->authorizeAndCapture();
        return $this->responseAsArray($response);
    }

    /**
     * @param FCom_AuthorizeNet_PaymentMethod_Aim $payment
     * @return array
     */
    public function authorize($payment)
    {
        $api = $this->getApi();
        /* @var $order FCom_Sales_Model_Order */
        $order         = $payment->getOrder();
        $this->setSaleDetails($api, $payment, $order);

        $response = $api->authorizeOnly();
        return $this->responseAsArray($response);
    }

    /**
     * @param FCom_AuthorizeNet_PaymentMethod_Aim $payment
     * @return array
     */
    public function capture($payment)
    {
        $api = $this->getApi();
        /* @var $order FCom_Sales_Model_Order */
        $order         = $payment->getOrder();
        // if we're going to allow multiple same method transactions, then we can namespace them with trans_id
        $api->trans_id = $order->getData('payment_details/' . FCom_AuthorizeNet_PaymentMethod_Aim::PAYMENT_METHOD_KEY . '/transaction_id');
        // todo add amount to capture if needed
        $response = $api->priorAuthCapture();
        return $this->responseAsArray($response);
    }

    /**
     * @param FCom_AuthorizeNet_PaymentMethod_Aim $payment
     * @return array
     */
    public function credit($payment)
    {
        $api = $this->getApi();
        /* @var $order FCom_Sales_Model_Order */
        $order         = $payment->getOrder();
        $trId = $order->getData('payment_details/' . FCom_AuthorizeNet_PaymentMethod_Aim::PAYMENT_METHOD_KEY . '/transaction_id');
        $api->trans_id = $trId;
        // todo, get refund amount from order or credit object
        $api->amount = $order->getData('payment_details/' . FCom_AuthorizeNet_PaymentMethod_Aim::PAYMENT_METHOD_KEY . '/' . $trId . '/amount');
        $api->card_num = $order->getData('payment_details/' . FCom_AuthorizeNet_PaymentMethod_Aim::PAYMENT_METHOD_KEY . '/last_four');
        $api->exp_date = $order->getData('payment_details/' . FCom_AuthorizeNet_PaymentMethod_Aim::PAYMENT_METHOD_KEY . '/card_exp_date');
        $response = $api->credit();
        return $this->responseAsArray($response);
    }

    /**
     * @param FCom_AuthorizeNet_PaymentMethod_Aim $payment
     * @return array
     */
    public function void($payment)
    {
        $api = $this->getApi();
        $order = $payment->getOrder();
        $trId = $order->getData('payment_details/' . FCom_AuthorizeNet_PaymentMethod_Aim::PAYMENT_METHOD_KEY . '/transaction_id');
        $response = $api->void($trId);

        return $this->responseAsArray($response);
    }

    /**
     * @param AuthorizeNetAIM $api
     * @param FCom_AuthorizeNet_PaymentMethod_Aim $payment
     * @param FCom_Sales_Model_Order $order
     */
    protected function setSaleDetails($api, $payment, $order)
    {
        $api->amount      = $payment->getDetail('amount_due');
        $api->card_num    = $payment->getCardNumber();
        $api->exp_date    = $payment->getDetail('card_exp_date');
        $api->invoice_num = $order->unique_id;
        $api->description = $order->getTextDescription();

        if ($this->BConfig->get('modules/FCom_AuthorizeNet/aim/useccv')) {
            $api->card_code = $payment->getDetail('card_code');
        }
        $billing = $order->billing();
        if ($order->billing_firstname) {
            $api->first_name = $order->billing_firstname;
        }
        if ($order->billing_lastname) {
            $api->last_name = $order->billing_lastname;
        }
        if ($order->billing_company) {
            $api->company = $order->billing_company;
        }
        $api->address = $order->get('billing_street');
        if ($order->billing_city) {
            $api->city = $order->billing_city;
        }
        if ($order->billing_region) {
            $api->state = $order->billing_region;
        }
        if ($order->billing_postcode) {
            $api->zip = $order->billing_postcode;
        }
        if ($order->billing_country) {
            $api->country = $order->billing_country;
        }
        if ($order->billing_phone) {
            $api->phone = $order->billing_billing_phone;
        }
        if ($order->billing_fax) {
            $api->fax = $order->billing_fax;
        }
        if ($order->customer_email) {
            $api->email = $order->customer_email;
        }
        if ($order->customer_id) {
            $api->cust_id = $order->customer_id;
        }

        $api->po_num = $order->unique_id;
    }

    /**
     * @throws BException
     * @return AuthorizeNetAIM
     */
    public function getApi()
    {
        if (null == $this->api) {
            $conf = $this->getConfig();
            if (!$data = $conf->get('aim')) {
                throw new BException("Invalid Authorize.net settings.");
            }
            $data = new BData($data);
            $this->BDebug->log(print_r($data->as_array(), true));
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
            $this->BClassAutoload->addPath(__DIR__ . '/lib');
            $this->api = new AuthorizeNetAIM();
/* API is missing currency code !!!!
            if($data->get('currency')){
                $this->api->currency_code = $data->get('currency');
            }
*/
        }
        return $this->api;
    }

    /**
     * @return BData
     */
    public function getConfig()
    {
        return new BData($this->BConfig->get('modules/FCom_AuthorizeNet'));
    }

    /**
     * @param AuthorizeNetAIM_Response $response
     * @return array
     */
    public function responseAsArray($response)
    {
        $result = [];
        foreach ($this->getResponseVariables($response) as $name) {
            if (!empty($response-> {$name})) {
                $result[$name] = $response-> {$name};
            }
        }
        return $result;
    }

    /**
     * Get reposnse object variables
     *
     * Since they are the same for each response, try to cache them
     *
     * @param AuthorizeNetAIM_Response $response
     * @return array
     */
    protected function getResponseVariables($response)
    {
        if (empty($this->response_vars)) {
            $vars = get_object_vars($response);
            if ($vars) {
                foreach (array_keys($vars) as $k) {
                    $this->response_vars[] = $k;
                }
            }
        }
        return $this->response_vars;
    }
}
