<?php

/**
 * Class Sellvana_PaymentAuthorizeNet_AimApi
 *
 * @property Sellvana_Sales_Model_Order_Payment_Transaction $Sellvana_Sales_Model_Order_Payment_Transaction
 */
class Sellvana_PaymentAuthorizeNet_AimApi extends BClass
{
    const AUTHORIZENET_LOG_FILE = "authorize.net.log";
    protected $_responseVars = [];

    /**
     * @var AuthorizeNetAIM
     */
    protected $_api;

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @param Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim $paymentMethod
     * @return array
     */
    public function sale($transaction, $paymentMethod)
    {
        $api           = $this->getApi();
        $this->_setSaleDetails($transaction, $paymentMethod, $api);
        $response      = $api->authorizeAndCapture();
        return $this->responseAsArray($response);
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @param Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim $paymentMethod
     * @return array
     */
    public function authorize($transaction, $paymentMethod)
    {
        $api = $this->getApi();
        $this->_setSaleDetails($transaction, $paymentMethod, $api);

        $response = $api->authorizeOnly();
        return $this->responseAsArray($response);
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @param Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim $paymentMethod
     * @return array
     */
    public function capture($transaction, $paymentMethod)
    {
        $api = $this->getApi();
        // if we're going to allow multiple same method transactions, then we can namespace them with trans_id
        $api->trans_id = $transaction->get('parent_transaction_id');
        $api->amount = $transaction->get('amount');
        // todo add amount to capture if needed
        $response = $api->priorAuthCapture();
        return $this->responseAsArray($response);
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @param Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim $paymentMethod
     * @return array
     */
    public function credit($transaction, $paymentMethod)
    {
        $api = $this->getApi();
        $payment = $transaction->payment();
        $trId = $transaction->get('parent_transaction_id');
        $parentTransactions = $payment->findTransactions('capture', 'completed', null, true);
        $parentTransaction = null;
        foreach ($parentTransactions as $trans) {
            if ($trans->get('transaction_id') == $trId) {
                $parentTransaction = $trans;
                break;
            }
        }
        if ($parentTransaction === null) {
            throw new BException('Unable to find the parent transaction');
        }
        $api->trans_id = $trId;
        $api->amount = $transaction->get('amount');
        $api->card_num = $payment->getData('form/last_four');
        $api->exp_date = $payment->getData('form/card_exp_date');
        if ((strtotime(date('Y-m-d')) - strtotime($parentTransaction->get('update_at'))) > 24 * 60 * 60) {
            $response = $api->credit();
        } else {
            $response = $api->void();
        }
        return $this->responseAsArray($response);
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @param Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim $paymentMethod
     * @return array
     */
    public function void($transaction, $paymentMethod)
    {
        $api = $this->getApi();
        $trId = $transaction->get('parent_transaction_id');
        $response = $api->void($trId);

        return $this->responseAsArray($response);
    }

    /**
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @param Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim $paymentMethod
     * @param AuthorizeNetAIM $api
     */
    protected function _setSaleDetails($transaction, $paymentMethod, $api)
    {
        $payment = $transaction->payment();
        $order = $payment->order();
        $paymentData = $this->BRequest->post('payment');
        $methodData = $paymentData[$paymentMethod->getCode()];
        $paymentMethod->setPaymentFormData($methodData);
        
        $api->amount      = $transaction->get('amount');
        $api->card_num    = $paymentMethod->getCardNumber();
        $api->exp_date    = $paymentMethod->get('card_exp_date');
        $api->invoice_num = $order->unique_id;
        $api->description = $order->getTextDescription();

        if ($this->BConfig->get('modules/Sellvana_PaymentAuthorizeNet/aim/useccv')) {
            $api->card_code = $paymentMethod->get('card_code');
        }

        if ($order->billing_firstname) {
            $api->first_name = $order->billing_firstname;
        }
        if ($order->billing_lastname) {
            $api->last_name = $order->billing_lastname;
        }
        if ($order->billing_company) {
            $api->company = $order->billing_company;
        }
        $api->address = $order->get('billing_street1');
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

        if ($paymentMethod->getCardNumber()) {
            $payment->setData('form/last_four', substr($paymentMethod->getCardNumber(), -4));
            $payment->setData('form/card_exp_date', $paymentMethod->get('card_exp_date'));
        }

        $api->po_num = $order->unique_id;
    }

    /**
     * @throws BException
     * @return AuthorizeNetAIM
     */
    public function getApi()
    {
        if (null == $this->_api) {
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
            $this->_api = new AuthorizeNetAIM();
/* API is missing currency code !!!!
            if($data->get('currency')){
                $this->api->currency_code = $data->get('currency');
            }
*/
        }
        return $this->_api;
    }

    /**
     * @return BData
     */
    public function getConfig()
    {
        return new BData($this->BConfig->get('modules/Sellvana_PaymentAuthorizeNet'));
    }

    /**
     * @param AuthorizeNetAIM_Response $response
     * @return array
     */
    public function responseAsArray($response)
    {
        $result = [];
        foreach ($this->_getResponseVariables($response) as $name) {
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
    protected function _getResponseVariables($response)
    {
        if (empty($this->_responseVars)) {
            $vars = get_object_vars($response);
            if ($vars) {
                foreach (array_keys($vars) as $k) {
                    $this->_responseVars[] = $k;
                }
            }
        }
        return $this->_responseVars;
    }
}
