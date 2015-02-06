<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AuthorizeNet_PaymentMethod_Aim
 *
 * @property FCom_AuthorizeNet_AimApi $FCom_AuthorizeNet_AimApi
 * @property FCom_AuthorizeNet_Model_Settings $FCom_AuthorizeNet_Model_Settings
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 */

class FCom_AuthorizeNet_PaymentMethod_Aim extends FCom_Sales_Method_Payment_Abstract
{
    static protected $_methodKey = "authorizenet_aim";

    function __construct()
    {
        $this->_name = 'Authorize.net';
        $this->_capabilities['pay_online'] = 1;
        $this->_capabilities['void_online'] = 1;
        $this->_capabilities['refund_online'] = 1;
    }

    public function getCheckoutFormView()
    {
        return $this->BLayout->view('authorizenet/aim')->set('key', static::$_methodKey);
    }

    public function payOnCheckout(FCom_Sales_Model_Order_Payment $payment)
    {
        $config = $this->config();
        if (!$config['active']) {
            // log this and eventually show a message
            return null;
        }
        $action = $config['payment_action'];

        $api = $this->FCom_AuthorizeNet_AimApi;

        $result = [];
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
        $success = $response['response_code'] == 1;
        if ($success) {
            $this->set($response['transaction_id'], $response);
            $this->set('transaction_id', $response['transaction_id']);
            $transaction->complete();
        } else {
            $result['error']['message'] = 'There has been an issue with your transaction';
        }
        $result['response'] = $response;
        $this->clear();
        $transaction->setData('result', $result)->save();
        return $result;
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
        return $this->FCom_AuthorizeNet_Model_Settings->cardTypes();
    }

    /**
     * @return array|null
     */
    public function config()
    {
        $config = $this->BConfig;
        return $config->get('modules/FCom_AuthorizeNet/aim');
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
            $data['last_four'] = $this->lastFour();
            unset($data['cc_num']);
        }
        return $data;
    }

    public function getPublicData()
    {
        return $this->getDataToSave();
    }

    protected function lastFour()
    {
        $lastFour = $this->get('last_four');
        $ccNum    = $this->get('cc_num');
        if (!$lastFour && $ccNum) {
            $this->set('last_four', substr($ccNum, -4));
        }
        return $this->get('last_four');
    }
    protected function clear()
    {
        $this->lastFour();
        unset($this->_details['cc_num']);
    }
}
