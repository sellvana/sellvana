<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PayPal_PaymentMethod
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order_Payment $FCom_Sales_Model_Order_Payment
 */
class FCom_PayPal_PaymentMethod_ExpressCheckout extends FCom_Sales_Method_Payment_Abstract
{
    protected static $_apiVersion = '72.0';
    protected $_name = 'PayPal Express Checkout';

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->view('paypal/form');
    }

    public function payOnCheckout()
    {
        if (!$this->_payment) {
            return [
                'error' => ['message' => 'No order payment provided'],
                'redirect_to' => $this->BApp->href('cart'),
            ];
        }

        $order = $this->_payment->order();
        $cart = $order->cart();

        $result = $this->_callSetExpressCheckout();

        if (!empty($result['error'])) {
            $this->_setErrorStatus();
            return $result;
        }

        $token = $result['response']['TOKEN'];

        $this->_payment->set([
            'transaction_type' => $this->getConfig("payment_action"),
            'transaction_token' => $token,
            'online' => 1,
        ])->save();

        $sData =& $this->BSession->dataToUpdate();
        $sData['paypal']['token'] = $token;

        $result['redirect_to'] = $this->getConfig('express_checkout_url') . $token;

        $this->FCom_Sales_Main->workflowAction('customerStartsExternalPayment', ['payment' => $this->_payment]);

        return $result;
    }

    public function processReturnRequest()
    {
        $sData =& $this->BSession->dataToUpdate();
        $token = $this->BRequest->get('token');
        $payerId = $this->BRequest->get('PayerID');

        $result = ['token' => $token, 'payer_id' => $payerId];
        if (empty($sData['last_order_id'])) {
            $result['error']['message'] = 'Session Expired';
            $this->_setErrorStatus();
            return $result;
        }
        if ($token !== $sData['paypal']['token']) {
            $result['error']['message'] = 'Invalid PayPal Return Token';
            $this->_setErrorStatus();
            return $result;
        }
        $payment = $this->FCom_Sales_Model_Order_Payment->load($token, 'transaction_token');
        if (!$payment) {
            $result['error']['message'] = 'Payment associated with the token is not found';
            $this->_setErrorStatus();
            return $result;
        }
        if ($payment->get('order_id') !== $sData['last_order_id']) {
            $result['error']['message'] = "Order doesn't match the payment token";
            $this->_setErrorStatus();
            return $result;
        }
        $this->setPaymentModel($payment);

        $this->FCom_Sales_Main->workflowAction('customerReturnsFromExternalPayment', ['payment' => $payment]);

        $result = $this->_callGetExpressCheckoutDetails();
        if (!empty($result['error'])) {
            $this->_setErrorStatus();
            return $result;
        }

        $r = $result['response'];
        $transData = [
            'payer_id'                   => $r['PAYERID'],
            'email'                      => $r['EMAIL'],
            'firstname'                  => $r['FIRSTNAME'],
            'lastname'                   => $r['LASTNAME'],
            'correlation_id'             => $r['CORRELATIONID'],
            'country'                    => $r['COUNTRYCODE'],
            'currency'                   => $r['CURRENCYCODE'],
            'billing_agreement_accepted' => $r['BILLINGAGREEMENTACCEPTEDSTATUS'],
            'checkout_status'            => $r['CHECKOUTSTATUS'],
            'payer_status'               => $r['PAYERSTATUS'],
        ];
        if (!empty($r['ADDRESSSTATUS'])) {
            $transData['shipping'] = [
                'address_status' => $r['ADDRESSSTATUS'],
                'company'        => !empty($r['COMPANY']) ? $r['COMPANY'] : '',
                'street1'        => $r['SHIPTOSTREET'],
                'street2'        => !empty($r['SHIPTOSTREET2']) ? $r['SHIPTOSTREET2'] : '',
                'city'           => $r['SHIPTOCITY'],
                'region'         => !empty($r['SHIPTOSTATE']) ? $r['SHIPTOSTATE'] : '',
                'postcode'       => !empty($r['SHIPTOZIP']) ? $r['SHIPTOZIP'] : '',
                'country'        => $r['SHIPTOCOUNTRYCODE'],
            ];
        }
        $payment->setData('transaction', $transData);
        $payment->save();

        $result = $this->_callDoExpressCheckoutPayment();

        if (!empty($result['error'])) {
            $this->_setErrorStatus();
            return $result;
        }
        $paymentAction = $this->getConfig('payment_action');

        switch ($paymentAction) {
            case 'Order':
                $this->_parseTransactionResponse($payment, $result['response'], 0);
                $this->authorize();
                $this->capture();
                exit;
                $payment->setupRootOrder();
                break;

            case 'Authorization':
                $this->_parseTransactionResponse($payment, $result['response'], 0);
                $this->_payment->set('amount_authorized', $this->_payment->get('amount_due'));
                $this->capture();
                exit;

                $payment->setupRootOrder();
                $child = $payment->createChildPayment();
                $child->authorize();
                break;

            case 'Sale':
                $this->_parseTransactionResponse($payment, $result['response'], 0);
                exit;

                $payment->authorize();
                $payment->capture();
                break;
        }

        $r = $result['response'];

        if ($result['success']) {
            $transData['amount_due'] = 0;
            $transData['amount_authorized'] = $r['PAYMENTINFO_0_AMT'];
            $transData['amount_captured'] = $authOnly ? 0 : $r['PAYMENTINFO_0_AMT'];
        } else {
            $result['error']['message'] = "Your payment has not been accepted by PayPal ({$status}/{$pendingReason})";
        }

        $payment->setData('transaction', $transData, true);
        $payment->save();

        if ($result['success']) {
            $this->FCom_Sales_Main->workflowAction('customerCompletesPayment', ['payment' => $payment, 'auth_only' => $authOnly]);
        } else {
            $this->FCom_Sales_Main->workflowAction('customerFailsPayment', ['payment' => $payment]);
        }

        return $result;
    }

    public function authorize($amount = null)
    {
        //$authPayment = $this->_payment->createChildPayment($amount);
        //$this->setPaymentModel($authPayment);
        $authPayment = $this->_payment;

        $this->_onBeforeAuthorization();

        $result = $this->_callDoAuthorization();

        if (!empty($result['error'])) {
            $this->_setErrorStatus();
            return $result;
        }

        $r = $result['response'];

        $authPayment->setData('transaction', [
            'authorization_id' => $r['TRANSACTIONID'],
            'payment_status' => $r['PAYMENTSTATUS'],
            'pending_reason' => $r['PENDINGREASON'],
        ]);

        $this->_onSuccessfulAuthorization();

        return $result;
    }

    public function capture($complete = false)
    {
        $this->_payment->setData('transaction/complete', $complete);

        $this->_onBeforeCapture();

        $result = $this->_callDoCapture();

        if (!empty($result['error'])) {
            $this->_setErrorStatus();
            return $result;
        }

        $this->_onSuccessfulCapture();

        return $result;
    }

    public function void()
    {

    }

    public function refund()
    {

    }

    protected function _parseTransactionResponse($payment, $r, $n = null)
    {
        /**
         * when payment_action == order:
         *
         *  DoExpressCheckoutPayment
         *      TRANSACTIONTYPE = cart
         *      PAYMENTTYPE = None
         *      PAYMENTSTATUS = Pending
         *      PENDINGREASON = order
         *      REASONCODE = None
         *      TRANSACTIONID = O-7A6278984U0648222
         *
         *  DoAuthorization
         *      TRANSACTIONID = 3R649296UH3305615
         *      PAYMENTSTATUS = Pending
         *      PENDINGREASON = authorization
         *
         *  DoCapture (AUTHORIZATIONID = O-7A6278984U0648222)
         *      AUTHORIZATIONID = O-7A6278984U0648222
         *      TRANSACTIONID = 1C839870TG745882Y
         *      PARENTTRANSACTIONID = O-7A6278984U0648222
         *      TRANSACTIONTYPE = cart
         *      PAYMENTTYPE = instant
         *
         * when payment_action == authorize
         *
         *  DoExpressCheckoutPayment
         *      PAYMENTTYPE = instant
         *      TRANSACTIONTYPE = cart
         *      PAYMENTSTATUS = Pending
         *      PENDINGREASON = authorization
         *      REASONCODE = None
         *      TRANSACTIONID = 5LF51400HN526230S
         *
         *  DoCapture (AUTHORIZATIONID = 5LF51400HN526230S)
         *      AUTHORIZATIONID = 5LF51400HN526230S
         *      TRANSACTIONID = 1B436522FM238025K
         *      PARENTTRANSACTIONID = 5LF51400HN526230S
         *      TRANSACTIONTYPE = cart
         *      PAYMENTTYPE = instant
         *      PAYMENTSTATUS = Completed
         *      PENDINGREASON = None
         *      REASONCODE = None
         *
         * when payment_action = sale
         *
         *  DoExpressCheckoutPayment
         *      TRANSACTIONID = 3B916209JT754125Y
         *      TRANSACTIONTYPE = cart
         *      PAYMENTTYPE = instant
         *      PAYMENTSTATUS = Completed
         *      PENDINGREASON = None
         *      REASONCODE = None
         *
         */
        $p = null === $n ? '' : 'PAYMENTINFO_' . $n . '_';
        $status = strtoupper($r[$p . 'PAYMENTSTATUS']);
        $paymentType = strtoupper($r[$p . 'PAYMENTTYPE']);
        $payment->set([
            'transaction_token' => null, // for security?
            'transaction_id'    => $r[$p . 'TRANSACTIONID'],
            'transaction_fee'   => !empty($r[$p . 'FEEAMT']) ? $r[$p . 'FEEAMT'] : null,
        ]);
        $transData = [
            'timestamp'        => $r['TIMESTAMP'],
            'transaction_type' => $r[$p . 'TRANSACTIONTYPE'],
            'payment_type'     => $r[$p . 'PAYMENTTYPE'],
            'payment_status'   => $r[$p . 'PAYMENTSTATUS'],
            'reason_code'      => $r[$p . 'REASONCODE'],
        ];

        $pendingReason = null;
        switch ($status) {
            case 'PENDING':
                $transData['pending_reason'] = $r[$p . 'PENDINGREASON'];
                $pendingReason = strtoupper($transData['pending_reason']);
                break;
            case 'COMPLETED-FUNDS-HELD':
                $transData['hold_decision'] = $r[$p . 'HOLDDECISION'];
                break;
        };

        $successStatuses = ['COMPLETED', 'PROCESSED', 'IN-PROGRESS', 'REFUNDED', 'PARTIALLY-REFUNDED', 'CANCELED-REVERSAL'];
        $transData['success'] = in_array($status, $successStatuses)
            || ($status === 'PENDING' && in_array($pendingReason, ['AUTHORIZATION', 'ORDER']));

        $payment->setData('transaction', $transData, true);
    }

    protected function _callSetExpressCheckout()
    {
        $order = $this->_payment->order();

        $baseUrl = $this->BApp->href('paypal');
        $request = [
            'INVNUM'    => $order->get('unique_id'),
            'RETURNURL' => $baseUrl . '/return',
            'CANCELURL' => $baseUrl . '/cancel',
        ];

        $request = $this->_addOrderInfo($request);
        if ($this->getConfig('show_shipping')) {
            $request = $this->_addShippingInfo($request);
        } else {
            $request['NOSHIPPING'] = 1;
        }

        $result = $this->_call('SetExpressCheckout', $request);

        return $result;
    }

    protected function _callGetExpressCheckoutDetails()
    {
        $token = $this->_payment->get('transaction_token');

        $result = $this->_call('GetExpressCheckoutDetails', ['TOKEN' => $token]);

        if (!empty($result['error'])) {
            return $result;
        }
        if (empty($result['response']['PAYERID'])) {
            $result['error']['message'] = 'Payment action could not be initiated';
        }
        return $result;
    }

    protected function _callDoExpressCheckoutPayment()
    {
        $token = $this->_payment->get('transaction_token');
        $payerId = $this->_payment->getData('transaction/payer_id');

        $request = [
            'TOKEN' => $token,
            'PAYERID' => $payerId,
        ];
        $request = $this->_addOrderInfo($request);
        if ($this->getConfig('show_shipping')) {
            $request = $this->_addShippingInfo($request);
        }

        $result = $this->_call('DoExpressCheckoutPayment', $request);

        return $result;
    }

    protected function _callDoAuthorization()
    {
        $request = [
            'TRANSACTIONID' => $this->_payment->get('transaction_id'),
            'CURRENCYCODE' => $this->_payment->order()->get('order_currency'),
            'AMT' => $this->_payment->get('amount_due'),
        ];

        $result = $this->_call('DoAuthorization', $request);

        return $result;
    }

    protected function _callDoCapture()
    {
        $request = [
            'AUTHORIZATIONID' => $this->_payment->get('transaction_id'),
            'AMT' => $this->_payment->get('amount_authorized'),
            'CURRENCYCODE' => $this->_payment->order()->get('order_currency'),
            'COMPLETETYPE' => $this->_payment->getData('transaction/complete') ? 'Complete' : 'NotComplete',
        ];
        $result = $this->_call('DoCapture', $request);

        return $result;
    }

    protected function _addOrderInfo($request, $n = 0)
    {
        $order = $this->_payment->order();
        $currency = $order->get('order_currency');

        $request["PAYMENTREQUEST_{$n}_PAYMENTACTION"] = $this->getConfig("payment_action");
        $request["PAYMENTREQUEST_{$n}_AMT"]           = number_format($this->_payment->get("amount_due"), 2);
        $request["PAYMENTREQUEST_{$n}_ITEMAMT"]       = number_format($order->get("subtotal"), 2);
        $request["PAYMENTREQUEST_{$n}_SHIPPINGAMT"]   = number_format($order->get("shipping_price"), 2);
        $request["PAYMENTREQUEST_{$n}_TAXAMT"]        = number_format($order->get("tax_amount"), 2);
        $request["PAYMENTREQUEST_{$n}_CURRENCYCODE"]  = $currency ? $currency : "USD";

        if ($order->get('discount_amount')) {
            $request["PAYMENTREQUEST_{$n}_REDEEMEDOFFERNAME"] = $order->get('coupon_code');
            $request["PAYMENTREQUEST_{$n}_REDEEMEDOFFERAMOUNT"] = $order->get('discount_amount');
        }

        $i = 0;
        foreach ($order->items() as $item) {
            $request["L_PAYMENTREQUEST_{$n}_NAME{$i}"] = $item->get('product_name');
            $request["L_PAYMENTREQUEST_{$n}_AMT{$i}"] = number_format($item->get('price'), 2);
            $request["L_PAYMENTREQUEST_{$n}_QTY{$i}"] = (int)$item->get('qty_ordered');
            $request["L_PAYMENTREQUEST_{$n}_TAXAMT{$i}"] = number_format($item->get('tax_amount'), 2);
            $request["L_PAYMENTREQUEST_{$n}_ITEMWEIGHTVALUE{$i}"] = number_format($item->get('shipping_weight'), 2);
            //$request["L_PAYMENTREQUEST_{$n}_ITEMWEIGHTUNIT{$i}"] = $item->get('');
            //$request["L_PAYMENTREQUEST_{$n}_ITEMURL{$i}"] = $item->get('');
            $i++;
        }
        return $request;
    }

    protected function _addShippingInfo($request, $n = 0)
    {
        $order = $this->_payment->order();

        $request["PAYMENTREQUEST_{$n}_SHIPTONAME"]        = $order->shipping_firstname . " " . $order->shipping_lastname;
        $request["PAYMENTREQUEST_{$n}_SHIPTOSTREET"]      = $order->shipping_street1;
        $request["PAYMENTREQUEST_{$n}_SHIPTOSTREET2"]     = $order->shipping_street2;
        $request["PAYMENTREQUEST_{$n}_SHIPTOCITY"]        = $order->shipping_city;
        $request["PAYMENTREQUEST_{$n}_SHIPTOSTATE"]       = $order->shipping_region;
        $request["PAYMENTREQUEST_{$n}_SHIPTOZIP"]         = $order->shipping_postcode;
        $request["PAYMENTREQUEST_{$n}_SHIPTOCOUNTRYCODE"] = $order->shipping_country;
        $request["PAYMENTREQUEST_{$n}_SHIPTOPHONENUM"]    = $order->shipping_phone;

        return $request;
    }

    public function getConfig($key = null)
    {
        if (empty($this->_config)) {
            $config = $this->BConfig->get('modules/FCom_PayPal');
            $sandbox = $config['sandbox']['mode'] == 'on'
                || $config['sandbox']['mode'] == 'ip' && in_array($this->BRequest->ip(), explode(',', $config['sandbox']['ip']));
            $this->_config = $config[$sandbox ? 'sandbox' : 'production'];
        }
        return null === $key ? $this->_config : (isset($this->_config[$key]) ? $this->_config[$key] : null);
    }

    /**
     * @param string $methodName
     * @param array $request
     * @return array
     */
    public function _call($methodName, $request)
    {
        $request = array_merge([
            'METHOD'    => $methodName,
            'VERSION'   => static::$_apiVersion,
            'USER'      => $this->getConfig('username'),
            'PWD'       => $this->getConfig('password'),
            'SIGNATURE' => $this->getConfig('signature'),
        ], $request);

        $responseRaw = $this->BUtil->remoteHttp('GET', $this->getConfig('api_url'), $request);
        if (!$responseRaw) {
            return ['request' => $request, 'response' => false, 'error' => ['message' => 'No response from grateway']];
        }

        parse_str($responseRaw, $response);
        $result = ['request' => $request, 'response' => $response/*, 'response_raw' => $responseRaw*/];

        if (!empty($response['ACK'])) {
            $ack = strtoupper($response['ACK']);
            if ($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING') {
                if ($this->BDebug->is('DEBUG')) {
                    $this->_payment->setData('last_api_call', $result);
                }
                if ($methodName !== 'SetExpressCheckout') {
                    echo "<pre>";
                    var_dump($result);
                    echo "<pre>";
                }
                return $result;
            }
        }

        $result['error']['type'] = 'API';
        $result['error']['ack'] = $response['ACK'];

        $summary = [];
        for ($i = 0; isset($response['L_SHORTMESSAGE' . $i]); $i++) {
            $result['error']['details'][$i]['code'] = $code = $response['L_ERRORCODE' . $i];
            $result['error']['details'][$i]['short_message'] = $sMsg = $response['L_SHORTMESSAGE' . $i];
            $result['error']['details'][$i]['long_message'] = $lMsg = $response['L_LONGMESSAGE' . $i];
            $summary[] = "[{$code}] {$sMsg} - {$lMsg}";
        }
        $result['error']['message'] = join("\n", $summary);

        if ($this->BDebug->is('DEBUG')) {
            $this->_payment->setData('last_api_call', $result);
        }
        echo "<pre>"; var_dump($result); echo "<pre>";
        return $result;
    }
}
