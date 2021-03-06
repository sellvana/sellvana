<?php

/**
 * Class Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim
 *
 * @property Sellvana_PaymentAuthorizeNet_AimApi $Sellvana_PaymentAuthorizeNet_AimApi
 * @property Sellvana_PaymentAuthorizeNet_Model_Settings $Sellvana_PaymentAuthorizeNet_Model_Settings
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 */

class Sellvana_PaymentAuthorizeNet_PaymentMethod_Aim extends Sellvana_Sales_Method_Payment_Abstract
{
    protected $_code = "authorizenet_aim";
    protected $_manualStateManagement = false;

    /** @var  Sellvana_Sales_Model_Order */
    protected $_order;

    function __construct()
    {
        $this->_name = 'Authorize.net';
        $this->_capabilities['capture'] = 1;
        $this->_capabilities['pay_online'] = 1;
        $this->_capabilities['void'] = 1;
        $this->_capabilities['void_online'] = 1;
        $this->_capabilities['refund_online'] = 1;
        $this->_capabilities['refund'] = 1;
    }

    public function getCheckoutFormView()
    {
        return $this->BLayout->getView('authorizenet/aim')->set('key', $this->_code);
    }

    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $config = $this->config();
        if (!$config['active']) {
            // log this and eventually show a message
            return null;
        }
        $action = $config['payment_action'];

        $api = $this->Sellvana_PaymentAuthorizeNet_AimApi;

        switch ($action) {
            case 'AUTH_ONLY':
                $transaction = $payment->createTransaction('auth')->start();
                $response = $api->authorize($transaction, $this);
                break;
            case 'AUTH_CAPTURE':
                $transaction = $payment->createTransaction('sale')->start();
                $response = $api->sale($transaction, $this);
                break;
            default :
                // log and show message
                return null;
                break;
        }
        $result = $this->_processResponse($response, $transaction);

        $this->Sellvana_Sales_Main->workflowAction('customerCompletesCheckoutPayment', [
            'payment' => $payment,
            'transaction' => $transaction,
        ]);

        return $result;
    }

    public function payByUrl(Sellvana_Sales_Model_Order_Payment $payment)
    {
        return $this->payOnCheckout($payment);
    }

    public function capture(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $api = $this->Sellvana_PaymentAuthorizeNet_AimApi;
        $response = $api->capture($transaction, $this);
        $result = $this->_processResponse($response, $transaction);

        if (empty($result['success'])) {
            $result['error']['message'] = (("Your payment has not been accepted by AuthorizeNet"));
            $this->Sellvana_Sales_Main->workflowAction('customerFailsCheckoutPayment', [
                'payment' => $transaction->payment(),
            ]);
        }

        return $result;
    }

    public function refund(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $api = $this->Sellvana_PaymentAuthorizeNet_AimApi;
        $response = $api->credit($transaction, $this);
        $result = $this->_processResponse($response, $transaction);

        if (empty($result['success'])) {
            $result['error']['message'] = (("Your payment has not been accepted by AuthorizeNet"));
            $this->Sellvana_Sales_Main->workflowAction('customerFailsCheckoutPayment', [
                'payment' => $transaction->payment(),
            ]);
        }

        return $result;
    }

    public function isAllDataPresent($data)
    {
        if (!parent::isAllDataPresent($data)) {
            return false;
        }

        if (empty($data['payment'][$this->_code])) {
            return false;
        }

        $data = $data['payment'][$this->_code];
        $isExpDateOk = !empty($data['expire']['year']) && !empty($data['expire']['month']) && $data['expire']['month'] <= 12;

        return !empty($data['cc_type']) && !empty($data['cc_num']) && !empty($data['cc_cid']) && $isExpDateOk;
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

    /**
     * @return array
     */
    public function cardTypes()
    {
        return $this->Sellvana_PaymentAuthorizeNet_Model_Settings->cardTypes();
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = $this->BConfig;
        return $config->get('modules/Sellvana_PaymentAuthorizeNet/aim');
    }

    public function setPaymentFormData(array $data)
    {
        if (isset($data['expire'], $data['expire']['month'], $data['expire']['year'])) {
            $data['card_exp_date'] = $data['expire']['month'] . '/' . $data['expire']['year'];
        }

        return parent::setPaymentFormData($data);
    }

    public function getDataToSave()
    {
        $data = $this->_details;
        if (!empty($data) && isset($data['cc_num'])) {
            $data['last_four'] = $this->_lastFour();
            unset($data['cc_num']);
        }
        return $data;
    }

    public function getPublicData()
    {
        return $this->getDataToSave();
    }

    protected function _lastFour()
    {
        $lastFour = $this->get('last_four');
        $ccNum    = $this->get('cc_num');
        if (!$lastFour && $ccNum) {
            $this->set('last_four', substr($ccNum, -4));
        }
        return $this->get('last_four');
    }
    protected function _clear()
    {
        $this->_lastFour();
        unset($this->_details['cc_num']);
    }

    /**
     * @param array $response
     * @param Sellvana_Sales_Model_Order_Payment_Transaction $transaction
     * @return mixed
     */
    protected function _processResponse($response, $transaction)
    {
        $result = [];
        $success = (isset($response['response_code']) && $response['response_code'] == 1);
        if ($success) {
            //$this->set($response['transaction_id'], $response);
            $transaction->set('transaction_id', $response['transaction_id']);
            $transaction->save();
        } else {
            $result['error']['message'] = $response['response_reason_text'];
            $this->_transaction = $transaction;
            $this->_setErrorStatus($result);
        }
        $result['response'] = $response;
        $this->_clear();
        $transaction->setData('result', $result)->save();
        return $result;
    }

    public function isRootTransactionNeeded()
    {
        return false;
    }
}
