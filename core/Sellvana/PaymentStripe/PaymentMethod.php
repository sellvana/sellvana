<?php

/**
 * Class Sellvana_PaymentStripe_PaymentMethod
 *
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 */
class Sellvana_PaymentStripe_PaymentMethod extends Sellvana_Sales_Method_Payment_Abstract
{
    static protected $_methodKey = 'stripe';

    protected $_name = 'Stripe';

    protected $_apiUrl = 'https://api.stripe.com';
    protected $_apiVersion = '2014-12-17';

    protected $_capabilities = [
        'pay'             => 1,
        'pay_online'      => 1,
        'auth'            => 1,
        'auth_partial'    => 1,
        'reauth'          => 1,
        'void'            => 1,
        'void_online'     => 1,
        'capture'         => 1,
        'capture_partial' => 1,
        'refund'          => 1,
        'refund_partial'  => 1,
        'refund_online'   => 1,
        'recurring'       => 1,
    ];

    protected function _initialize()
    {
        require_once __DIR__ . '/lib/Stripe.php';
        Stripe::setApiKey($this->getSecretKey());
    }

    public function can($capability)
    {
        if (!$this->getConfig('active') || !$this->getSecretKey() || !$this->getPublicKey()) {
            return false;
        }
        return parent::can($capability);
    }

    public function getSecretKey()
    {
        return $this->getConfig('test') ? $this->getConfig('test_secret_key') : $this->getConfig('live_secret_key');
    }

    public function getPublicKey()
    {
        return $this->getConfig('test') ? $this->getConfig('test_public_key') : $this->getConfig('live_public_key');
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getCheckoutFormView()
    {
        $data = [
            'form_prefix' => $this->getCheckoutFormPrefix(),
            'public_key' => $this->getPublicKey(),
            'amount' => $this->Sellvana_Sales_Model_Cart->sessionCart()->get('subtotal'),
            'description' => null,//'You will have opportunity to change address and shipping method on the next step',
        ];
        return $this->BLayout->getView('stripe/form')->set($data);
    }

    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $this->_initialize();
        $result = [];

        $order = $payment->order();
        $cart = $order->cart();
        $token = $cart->getData('payment_details/stripe/token');

        try {
            $transType = Sellvana_Sales_Model_Order_Payment_Transaction::SALE;
            $transaction = $payment->createTransaction($transType)->start();
            $charge = Stripe_Charge::create([
                'amount' => round($payment->get('amount_due') * 100),
                'currency' => $order->get('order_currency') ?: 'USD',
                'card' => $token,
                'description' => 'Test',
            ]);
            $transaction
                ->set('transaction_id', $charge->id)
                ->setData('result', $charge->__toArray(true))
                ->complete();

            $this->Sellvana_Sales_Main->workflowAction('customerCompletesCheckoutPayment', [
                'payment' => $payment,
                'transaction' => $transaction,
                'transaction_type' => $transType,
            ]);

            $result['success'] = true;
        } catch (Stripe_CardError $e) {
            echo "<pre>";
            var_dump($e);
            exit;
            $result['error']['message'] = $e->getMessage();
        } catch (Stripe_InvalidRequestError $e) {
            echo "<pre>";
            var_dump($e);
            exit;
            $result['error']['message'] = $e->getMessage();
        } catch (Exception $e) {
echo "<pre>"; var_dump($e); exit;
        }

        return $result;
    }

    public function getDataToSave()
    {
        if ($this->get('token')) {
            return ['token' => $this->get('token')];
        } else {
            return null;
        }
    }

    public function getConfig($key = null)
    {
        if (!$this->_config) {
            $this->_config = $this->BConfig->get('modules/Sellvana_PaymentStripe');
        }
        return !$key ? $this->_config : (!empty($this->_config[$key]) ? $this->_config[$key] : null);
    }

    /*
    protected function _call($method, $objectId = null, array $data = [])
    {
        $request = $data;

        $headers = [
            'Stripe-Version: ' . $this->_apiVersion,
        ];

        $options = [
            'auth' => $this->getConfig('test') ? $this->getConfig('test_secret_key') : $this->getConfig('live_secret_key'),
        ];

        $httpMethod = $data ? 'POST' : 'GET';
        $url = $this->_apiUrl . '/v1/' . $method;
        if ($objectId) {
            $url .= '/' . $objectId;
        }

        $response = $this->BUtil->remoteHttp($httpMethod, $url, $request, $headers, $options);
        $status = $this->BUtil->lastRemoteHttpInfo();
        if (!$status || !$response) {
            throw new Exception('Stripe: Error during remote API call, no response');
        }
        $result = $this->BUtil->fromJson($response);

        $statusCode = $status['headers']['http']['code'];
        $error = false;
        switch ($statusCode) {
            case '400':
                $error = 'Bad request';
                break;

            case '401':
                $error = 'Unauthorized';
                break;

            case '402':
                $error = 'Request Failed';
                break;

            case '404':
                $error = 'Not Found';
                break;

            case '500': case '502': case '503': case '504':
                $error = 'Server Error (' . $statusCode .')';
                break;
        }
        if ($error) {
            $this->BDebug->log('ERROR: ' . $method . ' ' . $this->BUtil->toJson($request) . ' ' . $response, 'stripe.log');
            throw new Exception('Stripe: ' . $error);
        }
        return $result;
    }
    */
}