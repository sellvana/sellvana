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
        $this->_order = $payment->order();
        $token = $this->BUtil->randomString(16);
        $payment->set([
            'transaction_type' => $this->getConfig("payment_action"),
            'transaction_token' => $token,
            'online' => 1,
        ])->save();

        $this->Sellvana_Sales_Main->workflowAction('customerStartsExternalPayment', ['payment' => $payment]);
        $result['redirect_to'] = $this->postUrl();
        $result['post_params'] = $this->hiddenFields($payment);

        return $result;
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
            'x_amount'         => $payment->get('amount_due'),
            'x_fp_sequence'    => $order->get('unique_id'),
            'x_fp_timestamp'   => $time,
            'x_relay_response' => "TRUE",
            'x_test_request'   => "FALSE",
            'x_relay_url'      => $this->getRelayUrl() . '?token=' . $payment->get('transaction_token'),
            'x_login'          => $config['login'],
            'x_method'         => 'CC',
            'x_type'           => $config['payment_action'],
            //'x_description'    => $order->getTextDescription(),
        ];
        if (class_exists("AuthorizeNetSIM")) {
            $form = new AuthorizeNetSIM_Form();
            $fields['x_fp_hash'] = $form->getFingerprint($config['login'],
                                                            $config['trans_key'],
                                                            $payment->get('amount_due'),
                                                            $order->get('unique_id'), $time);
        }
        $fields = array_merge($fields, $this->_specialFields());

        return $fields;
    }

    protected function _specialFields()
    {
        $paymentData = $this->BRequest->post('payment');
        $this->setPaymentFormData($paymentData[$this->_code]);

        return [
            "x_delim_char"     => ",",
            "x_delim_data"     => "TRUE",
            'x_relay_always'   => 'TRUE',
            'x_card_num'       => $this->getCardNumber(),
            'x_exp_date'       => $this->_details['card_exp_date'],
        ];
    }

    public function processReturnFromExternalCheckout()
    {
        $config        = $this->config();
        $apiResponse   = new AuthorizeNetSIM($config['login'], $config['trans_md5']);
        $result        = [];
        if ($apiResponse->isAuthorizeNet()) {
            $this->processApiResponse($apiResponse);
            if ($apiResponse->approved) {
                $result['redirect_to'] = $this->BApp->href('checkout/success') . '?response_code=1&transaction_id=' . $apiResponse->transaction_id;
                $result['success'] = true;
            } else {
                // Redirect to error page.
                $result['redirect_to'] = $this->BApp->href('checkout/checkout') . '?response_code=' . $apiResponse->response_code
                    . '&response_reason_text=' . $apiResponse->response_reason_text;
                $result['error']['message'] = 'Error -- transaction was not approved. Reason: ' . $apiResponse->response_reason_text;
            }
        } else {
            $result['redirect_to'] = $this->BApp->href('checkout/checkout');
            $result['error']['message'] = 'Error -- not AuthorizeNet. Check your MD5 Setting.';
            $this->_setErrorStatus($result, true);
        }

        return $result;
    }

    /**
     * @param AuthorizeNetSIM $response
     * @return Sellvana_PaymentAuthorizeNet_PaymentMethod_Dpm
     * @throws BException
     */
    public function processApiResponse(AuthorizeNetSIM $response)
    {
        $config = $this->config();
        if (array_key_exists('enabled', $config) && !$config['enabled']) {
            // log this and eventually show a message
            return null;
        }
        $token = $this->BRequest->get('token');
        $payment = $this->Sellvana_Sales_Model_Order_Payment->load($token, 'transaction_token');
        $this->Sellvana_Sales_Main->workflowAction('customerReturnsFromExternalPayment', ['payment' => $payment]);

        $action = $config['payment_action'];
        switch ($action) {
            case 'AUTH_ONLY':
                $transaction = $payment->createTransaction('auth');
                break;
            case 'AUTH_CAPTURE':
                $transaction = $payment->createTransaction('sale');
                break;
            default :
                throw new BException('Invalid payment action');
                break;
        }
        $transaction->start();
        $this->set($response->transaction_id, $response);
        $this->set('transaction_id', $response->transaction_id);
        if ($response->approved) {
            $status = $action == 'AUTH_ONLY' ? 'authorized' : 'paid';
        } else {
            $status = 'error';
        }
        if (!$payment->id()) {
            throw new BException('Unable to find the payment');
        }
        $transactionData = [
            'method'           => $this->_code,
            'order_id'         => $payment->order()->id(),
            'amount'           => $payment->get('amount_due'),
            'status'           => $status,
            'transaction_id'   => $response->transaction_id,
            'transaction_type' => $action == 'AUTH_ONLY' ? 'auth' : 'capture',
            'online'           => 1,
        ];
        $transaction->set($transactionData);

        $transaction->complete();
        $this->Sellvana_Sales_Main->workflowAction('customerCompletesCheckoutPayment', [
            'payment' => $payment,
            'transaction' => $transaction,
            'transaction_type' => $action == 'AUTH_ONLY' ? 'auth' : 'capture',
        ]);

        return $this;
    }
}
