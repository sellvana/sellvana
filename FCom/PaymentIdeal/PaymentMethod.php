<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentIdeal_PaymentMethod
 *
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */
class FCom_PaymentIdeal_PaymentMethod extends FCom_Sales_Method_Payment_Abstract
{
    const IDEAL_LOG = 'ideal.log';

    const IDEAL_TEST_BANK_ID = '9999';

    /**
     * @var string
     */
    protected $api_host = 'https://secure.mollie.nl';
    /**
     * @var int
     */
    protected $api_port = 443;
    /**
     * @var BData
     */
    protected $config;

    /**
     * @var string
     */
    protected $_name = "iDEAL";

    /**
     *
     */
    public function __construct()
    {
        $this->_capabilities['pay_online'] = 1;
        $this->_capabilities['refund_online'] = 0;
    }

    /**
     *
     */
    public function payOnCheckout()
    {
        $bankId      = $this->get('bank_id');
        $amount      = $this->_order->get('balance') * 100;
        $description = $this->_order->getTextDescription();
        $returnUrl   = $this->BApp->href("checkout/success");
        $reportUrl   = $this->BApp->href("ideal/report");

        try {
            $this->createPayment($bankId, $amount, $description, $returnUrl, $reportUrl);
            $bankUrl = $this->get('bank_url');
            if ($bankUrl) {
                $this->BSession->set('redirect_url', $bankUrl);
            }
        } catch (Exception $e) {
            $this->BDebug->log($e->getMessage(), static::IDEAL_LOG);
            $this->BDebug->log($e->getTraceAsString(), static::IDEAL_LOG);
            $this->set('error', $e->getMessage());
        }

        $success = !$this->get('error');
        if ($success) {
            $status = 'processing';
            $this->_order->set('status', $this->config()->get('order_status'));
            $this->_order->save();
        } else {
            $status = 'error';
        }
        $paymentData = [
            'method'           => 'ideal',
            'parent_id'        => $this->get('transaction_id'),
            'order_id'         => $this->_order->id(),
            'amount'           => $amount,
            'status'           => $status,
            'transaction_id'   => $this->get('transaction_id'),
            'transaction_type' => 'sale',
            'online'           => 1,
        ];

        $paymentModel = $this->FCom_Sales_Model_Order_Payment->create($paymentData)->save();
        $paymentModel->setData('response', $this->getPublicData());
        $paymentModel->save();
    }

    /**
     * @param array $details
     * @return $this
     */
    public function setDetails($details = [])
    {
        if (isset($details['ideal'])) {
            $this->_details = $details['ideal'];
        }
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getCheckoutFormView()
    {
        $banks = $this->getBanks();
        return $this->BLayout->view('form')
               ->set('banks', $banks)
               ->set('key', 'ideal');
    }

    /**
     * Get method config
     *
     * Returns method config wrapped in BData object for more convenient access
     * @return BData
     */
    public function config()
    {
        if (!$this->config) {
            $this->config = BData::i(true, [$this->BConfig->get('modules/FCom_PaymentIdeal')]);
        }
        return $this->config;
    }

// API methods

    /**
     * @return array
     * @throws Exception
     */
    protected function getBanks()
    {
        $banks_array     = [];
        $query_variables = [
            'a'          => 'banklist',
            'partner_id' => $this->config()->get('partner_id'),
        ];

        if ($this->config()->get('test')) {
            $query_variables['testmode'] = 'true';
        }

        $banks_xml = $this->_sendRequest(
            '/xml/ideal/',
            http_build_query($query_variables, '', '&')
        );

        if (!empty($banks_xml)) {
            $banks_object = static::_XMLtoObject($banks_xml);

            if (!$banks_object || $this->getResponseError($banks_object)) {
                $errors = $this->getResponseError($banks_object);
                throw new Exception(sprintf("Could not get bank list. %s, %s", $errors['error_code'], $errors['error_message']));
            }

            foreach ($banks_object->bank as $bank) {
                $banks_array["{$bank->bank_id}"] = "{$bank->bank_name}";
            }
        }
        return $banks_array;
    }

    /**
     * @param $bankId
     * @param $amount
     * @param $description
     * @param $returnUrl
     * @param $reportUrl
     * @return bool
     * @throws Exception
     */
    protected function createPayment($bankId, $amount, $description, $returnUrl, $reportUrl)
    {
        if (!$this->setBankId($bankId)) {
            throw new Exception($this->BLocale->_("Bank id: %s is not valid.", [$bankId]));
        }

        if (!$this->setDescription($description)) {
            throw new Exception($this->BLocale->_("Provided description \"%s\" cannot be used.", [$description]));
        }

        if (!$this->setAmount($amount)) {
            throw new Exception($this->BLocale->_("Invalid amount: %s", [$amount]));
        }

        if (!$returnUrl = filter_var($returnUrl, FILTER_VALIDATE_URL)) {
            throw new Exception($this->BLocale->_("Incorrect return url: %s", [$returnUrl]));
        }

        if (!$reportUrl = filter_var($reportUrl, FILTER_VALIDATE_URL)) {
            throw new Exception($this->BLocale->_("Incorrect report url: %s", [$reportUrl]));
        }

        $query_variables = [
            'a'           => 'fetch',
            'partnerid'   => $this->config()->get('partner_id'),
            'bank_id'     => $this->get('bank_id'),
            'amount'      => $this->get('amount'),
            'description' => $this->get('description'),
            'reporturl'   => $reportUrl,
            'returnurl'   => $returnUrl,
        ];

        if ($key = $this->config()->get('profile_key')) {
            $query_variables['profile_key'] = $key;
        }

        $create_xml = $this->_sendRequest(
            '/xml/ideal/',
            http_build_query($query_variables, '', '&')
        );

        $create_object = static::_XMLtoObject($create_xml);

        if ($this->getResponseError($create_object)) {
            $errors = $this->getResponseError($create_object);
            throw new Exception(sprintf("Could not perform payment. %s, %s", $errors['error_code'], $errors['error_message']));
        }

        $this->set('transaction_id', (string)$create_object->order->transaction_id);
        $this->set('bank_url', (string)$create_object->order->URL);

        return true;
    }

    /**
     * @param $transaction_id
     * @return bool
     * @throws BException
     * @throws Exception
     */
    public function checkPayment($transaction_id)
    {
        if (empty($transaction_id)) {
            throw new Exception("Transaction ID missing");
        }

        $query_variables = [
            'a'              => 'check',
            'partnerid'      => $this->config()->get('partner_id'),
            'transaction_id' => $transaction_id,
        ];

        if ($this->config()->get('test')) {
            $query_variables['testmode'] = 'true';
        }

        $check_xml = $this->_sendRequest(
            '/xml/ideal/',
            http_build_query($query_variables, '', '&')
        );

        $check_object = static::_XMLtoObject($check_xml);

        if ($this->getResponseError($check_object)) {
            $errors = $this->getResponseError($check_object);
            throw new Exception(sprintf("Could not check payment. %s, %s", $errors['error_code'], $errors['error_message']));
        }

        $this->set('paid_status', (bool)($check_object->order->payed == 'true'));
        $this->set('status', (string)$check_object->order->status);
        $this->set('amount', (int)$check_object->order->amount);
        $this->set('consumer_info', (isset($check_object->order->consumer)) ? (array)$check_object->order->consumer : []);
        $payment = $this->loadPaymentByTransactionId($transaction_id);
        $payment->set('status', 'paid')->save();
        return true;
    }

    /**
     * @param $description
     * @param $amount
     * @return bool
     * @throws Exception
     */
    public function createPaymentLink($description, $amount)
    {
        if (!$this->setDescription($description) || !$this->setAmount($amount)) {
            throw new Exception("Invalid description or amount");
        }

        $query_variables = [
            'a'           => 'create-link',
            'partnerid'   => $this->config()->get('partner_id'),
            'amount'      => $this->get('amount'),
            'description' => $this->get('description'),
            'profile_key' => $this->config()->get('profile_key'),
        ];

        $create_xml = $this->_sendRequest(
            '/xml/ideal/',
            http_build_query($query_variables, '', '&')
        );

        $create_object = static::_XMLtoObject($create_xml);

        if ($this->getResponseError($create_object)) {
            $errors = $this->getResponseError($create_object);
            throw new Exception(sprintf("Could not create payment link. %s, %s", $errors['error_code'], $errors['error_message']));
        }

        $this->set('payment_url', (string)$create_object->link->URL);
        return true;
    }

    /**
     * @param $transactionId
     * @throws BException
     */
    public function setOrderPaid($transactionId)
    {
        $order = $this->loadOrderByTransactionId($transactionId);
        // update order
        if ($this->get('paid_status')) {
            $order->set('status', 'paid')->save();
        }
    }

    /**
     * @param $bank_id
     * @return bool
     */
    protected function setBankId($bank_id)
    {
        if (!is_numeric($bank_id) || (!$this->config()->get('test') && $bank_id == static::IDEAL_TEST_BANK_ID)) {
            return false;
        }

        return ($this->set('bank_id', $bank_id));
    }

    /**
     * @param $description
     * @return mixed
     */
    protected function setDescription($description)
    {
        $description = substr($description, 0, 29);

        return ($this->set('description', $description));
    }

    /**
     * @param $amount
     * @return bool
     */
    public function setAmount($amount)
    {
        if (!is_numeric($amount)) {
            return false;
        }

        if (is_float($amount)) {
            $amount = round($amount);
        }

        return ($this->set('amount', $amount));
    }

    /**
     * @param $path
     * @param $query
     * @return string
     * @throws Exception
     */
    protected function _sendRequest($path, $query)
    {
        $url      = rtrim($this->api_host, '/') . "{$path}";
        $response = $this->BUtil->remoteHttp('GET', $url, $query);
        if (!$response) {
            $info       = $this->BUtil->lastRemoteHttpInfo();
            $error_code = isset($info['errno']) ? $info['errno'] : -1;
            $error_msg  = isset($info['error']) ? $info['error'] : $this->BLocale->_("An error occurred");
            throw new Exception($error_msg, $error_code);
        }

        return $response;
    }

    /**
     * @param $xml
     * @return SimpleXMLElement
     * @throws Exception
     */
    protected function _XMLtoObject($xml)
    {
        $errorHandling = libxml_use_internal_errors(true);
        $xml_object    = simplexml_load_string($xml);
        if (!$xml_object) {
            $error_code = -2;
            $error_msg  = $this->BLocale->_("There was an error processing XML.");
            $errors     = libxml_get_errors();
            $debugError = '';
            foreach ($errors as $error) {
                $debugError .= $this->displayXmlError($error);
            }
            $this->BDebug->log($debugError, static::IDEAL_LOG);
            $this->BDebug->log($xml, static::IDEAL_LOG);
            libxml_clear_errors();
            throw new Exception($error_msg, $error_code);
        }

        libxml_use_internal_errors($errorHandling);
        return $xml_object;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return array|bool
     */
    protected function getResponseError($xml)
    {
        if (empty($xml)) {
            return [
                'error_message' => "Empty response",
                'error_code'    => 100,
            ];
        }
        /*
         * Normal API errors
         */
        if (isset($xml->item)) {
            $attributes = $xml->item->attributes();
            if ($attributes['type'] == 'error') {
                return [
                    'error_message' => (string)$xml->item->message,
                    'error_code'    => (string)$xml->item->errorcode
                ];
            }
        }

        if (isset($xml->order->error) && (string)$xml->order->error == "true") {
            return [
                'error_message' => (string)$xml->order->message,
                'error_code'    => -1
            ];
        }

        return false;
    }

    /**
     * @param libXMLError $error
     * @return string
     */
    protected function displayXmlError($error)
    {
        $return = "XML, ";
        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) .
                   "\n  Line: $error->line" .
                   "\n  Column: $error->column";

        if ($error->file) {
            $return .= "\n  File: $error->file";
        }

        return $return . PHP_EOL;
    }

    /**
     * @param $transactionId
     * @return FCom_Sales_Model_Order
     */
    public function loadOrderByTransactionId($transactionId)
    {
        // load payment info from transaction id
        $payment = $this->loadPaymentByTransactionId($transactionId);
        // load order from payment method order_id
        $orderId = $payment->get('order_id');
        $order   = $this->FCom_Sales_Model_Order->load($orderId);
        return $order;
    }

    /**
     * @param string $transactionId
     * @return FCom_Sales_Model_Order_Payment
     */
    public function loadPaymentByTransactionId($transactionId)
    {
        $payment = $this->FCom_Sales_Model_Order_Payment->load($transactionId, 'transaction_id');
        return $payment;
    }
}
