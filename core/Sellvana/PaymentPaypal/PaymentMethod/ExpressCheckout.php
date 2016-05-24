<?php

/**
 * Class Sellvana_PaymentPaypal_PaymentMethod
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 */
class Sellvana_PaymentPaypal_PaymentMethod_ExpressCheckout extends Sellvana_Sales_Method_Payment_Abstract
{
    protected static $_apiVersion = '72.0';

    protected $_code = 'paypal_express';
    protected $_name = 'PayPal Express Checkout';

    protected $_transaction;

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

    public function can($capability)
    {
        $conf = $this->getConfig();
        if (empty($conf['username']) || empty($conf['password']) || empty($conf['signature'])) {
            return false;
        }
        return parent::can($capability);
    }

    /**
     * @return BLayout|BView
     */
    public function getCheckoutFormView()
    {
        return $this->BLayout->getView('paypal/form');
    }

    public function payOnCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $result = $this->_callSetExpressCheckout($payment);

        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $token = $result['response']['TOKEN'];

        $payment->set([
            'transaction_type' => $this->getConfig("payment_action"),
            'transaction_token' => $token,
            'online' => 1,
        ])->save();

        $sData =& $this->BSession->dataToUpdate();
        $sData['paypal']['token'] = $token;

        $result['redirect_to'] = $this->getConfig('express_checkout_url') . $token;

        $this->Sellvana_Sales_Main->workflowAction('customerStartsExternalPayment', ['payment' => $payment]);

        return $result;
    }

    public function processReturnFromExternalCheckout()
    {
        $sData =& $this->BSession->dataToUpdate();
        $token = $this->BRequest->get('token');
        $payerId = $this->BRequest->get('PayerID');

        $result = ['token' => $token, 'payer_id' => $payerId];
        if (empty($sData['last_order_id'])) {
            $result['error']['message'] = 'Session Expired';
            $this->_setErrorStatus($result);
            return $result;
        }
        if ($token !== $sData['paypal']['token']) {
            $result['error']['message'] = 'Invalid PayPal Return Token';
            $this->_setErrorStatus($result);
            return $result;
        }
        $payment = $this->Sellvana_Sales_Model_Order_Payment->load($token, 'transaction_token');
        if (!$payment) {
            $result['error']['message'] = 'Payment associated with the token is not found';
            $this->_setErrorStatus($result);
            return $result;
        }
        if ($payment->get('order_id') !== $sData['last_order_id']) {
            $result['error']['message'] = "Order doesn't match the payment token";
            $this->_setErrorStatus($result);
            return $result;
        }

        $this->Sellvana_Sales_Main->workflowAction('customerReturnsFromExternalPayment', ['payment' => $payment]);

        $result = $this->_callGetExpressCheckoutDetails($payment);
        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $r = $result['response'];
        $checkoutStatus = strtoupper($r['CHECKOUTSTATUS']);
        if ($checkoutStatus === 'PAYMENTACTIONCOMPLETED') {
            $result['error']['message'] = "Order has been already paid";
            $this->_setErrorStatus($result);
            return $result;
        }

        $transData = [
            'ack'                        => strtoupper($r['ACK']),
            'checkout_status'            => $checkoutStatus,
            'payer_status'               => strtoupper($r['PAYERSTATUS']),
            'payer_id'                   => $r['PAYERID'],
            'email'                      => $r['EMAIL'],
            'firstname'                  => $r['FIRSTNAME'],
            'lastname'                   => $r['LASTNAME'],
            'correlation_id'             => $r['CORRELATIONID'],
            'country'                    => $r['COUNTRYCODE'],
            'currency'                   => $r['CURRENCYCODE'],
            'billing_agreement_accepted' => $r['BILLINGAGREEMENTACCEPTEDSTATUS'],
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

        $paymentAction = $this->getConfig('payment_action');
        switch ($paymentAction) {
            case 'Sale':
                $transType = Sellvana_Sales_Model_Order_Payment_Transaction::SALE;
                $processorState = Sellvana_Sales_Model_Order_Payment_State_Processor::CAPTURED;
                break;

            case 'Authorization':
                $transType = Sellvana_Sales_Model_Order_Payment_Transaction::AUTHORIZATION;
                $processorState = Sellvana_Sales_Model_Order_Payment_State_Processor::AUTHORIZED;
                break;

            case 'Order':
                $transType = Sellvana_Sales_Model_Order_Payment_Transaction::ORDER;
                $processorState = Sellvana_Sales_Model_Order_Payment_State_Processor::ROOT_ORDER;
                break;
        }

        $transaction = $payment->createTransaction($transType)->start();

        $result = $this->_callDoExpressCheckoutPayment($transaction);

        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $this->_saveResultToTransaction($transaction, $result['response'], 0);

        if (!$transaction->getData('result/success')) {
            $result['error']['message'] = "Your payment has not been accepted by PayPal";
            $this->Sellvana_Sales_Main->workflowAction('customerFailsCheckoutPayment', [
                'payment' => $payment,
                'transaction' => $transaction,
            ]);
            return $result;
        }

        $transaction->complete();
        if (isset($processorState)) {
            $payment->state()->processor()->invokeStateChange($processorState);
        }

        $this->Sellvana_Sales_Main->workflowAction('customerCompletesCheckoutPayment', [
            'payment' => $payment,
            'transaction' => $transaction,
            'transaction_type' => $transType,
        ]);

        return $result;
    }

    public function authorize(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $this->_transaction = $transaction;
        $result = $this->_callDoAuthorization($transaction);

        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $r = $result['response'];

        $transaction->set('transaction_id', $r['TRANSACTIONID']);
        $transaction->setData('result', [
            'payment_status' => $r['PAYMENTSTATUS'],
            'pending_reason' => $r['PENDINGREASON'],
        ], true);

        return $result;
    }

    public function reauthorize(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $this->_transaction = $transaction;
        $result = $this->_callDoReauthorization($transaction);

        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $r = $result['response'];

        $transaction->set('transaction_id', $r['AUTHORIZATIONID']);
        $transaction->setData('result', [
            'payment_status' => $r['PAYMENTSTATUS'],
            'pending_reason' => $r['PENDINGREASON'],
        ], true);

        return $result;
    }

    public function capture(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $this->_transaction = $transaction;
        $result = $this->_callDoCapture($transaction);

        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $this->_saveResultToTransaction($transaction, $result['response']);

        if (empty($result['success'])) {
            $result['error']['message'] = "Your payment has not been accepted by PayPal";
            $this->Sellvana_Sales_Main->workflowAction('customerFailsCheckoutPayment', [
                'payment' => $transaction->payment(),
            ]);
            return $result;
        }

        return $result;
    }

    public function void(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $this->_transaction = $transaction;
        $result = $this->_callDoVoid($transaction);

        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $this->_saveResultToTransaction($transaction, $result['response']);

        return $result;
    }

    public function refund(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $this->_transaction = $transaction;
        $result = $this->_callRefundTransaction($transaction);

        if (!empty($result['error'])) {
            $this->_setErrorStatus($result);
            return $result;
        }

        $r = $result['response'];
        $r['TRANSACTIONTYPE'] = 'refund';
        $r['PAYMENTSTATUS'] = 'Refunded';
        $r['PAYMENTTYPE'] = 'instant';
        $r['REASONCODE'] = 'None';
        $r['TRANSACTIONID'] = $r['REFUNDTRANSACTIONID'];

        $this->_saveResultToTransaction($transaction, $r);
        $transaction->set('transaction_id', $r['REFUNDTRANSACTIONID']);

        return $result;
    }

    protected function _saveResultToTransaction(Sellvana_Sales_Model_Order_Payment_Transaction $transaction, $r, $n = null)
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
        $transType = strtoupper($r[$p . 'TRANSACTIONTYPE']);
        $paymentStatus = strtoupper($r[$p . 'PAYMENTSTATUS']);
        $paymentType = strtoupper($r[$p . 'PAYMENTTYPE']);
        $reasonCode = strtoupper($r[$p . 'REASONCODE']);
        $transData = [
            'timestamp'        => $r['TIMESTAMP'],
            'transaction_type' => $transType,
            'payment_type'     => $paymentType,
            'payment_status'   => $paymentStatus,
            'reason_code'      => $reasonCode,
        ];

        $pendingReason = null;
        switch ($paymentStatus) {
            case 'PENDING':
                $pendingReason = strtoupper($r[$p . 'PENDINGREASON']);
                $transData['pending_reason'] = $pendingReason;
                break;
            case 'COMPLETED-FUNDS-HELD':
                $holdDecision = strtoupper($r[$p . 'HOLDDECISION']);
                $transData['hold_decision'] = $holdDecision;
                break;
        };

        $successStatuses = ['COMPLETED', 'PROCESSED', 'IN-PROGRESS', 'REFUNDED', 'PARTIALLY-REFUNDED', 'CANCELED-REVERSAL'];
        $transData['success'] = in_array($paymentStatus, $successStatuses)
            || ($paymentStatus === 'PENDING' && in_array($pendingReason, ['AUTHORIZATION', 'ORDER']));


        $transaction
            ->set([
                'transaction_id'    => $r[$p . 'TRANSACTIONID'],
                'transaction_fee'   => !empty($r[$p . 'FEEAMT']) ? $r[$p . 'FEEAMT'] : null,
            ])
            ->setData('result', $transData, true);
    }

    protected function _callSetExpressCheckout(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $order = $payment->order();

        $baseUrl = $this->BApp->href('paypal');
        $request = [
            'INVNUM'    => $order->get('unique_id'),
            'RETURNURL' => $baseUrl . '/return',
            'CANCELURL' => $baseUrl . '/cancel',
        ];

        $request = $this->_addOrderInfo($payment, $request);
        if ($this->getConfig('show_shipping')) {
            $request = $this->_addShippingInfo($payment, $request);
        } else {
            $request['NOSHIPPING'] = 1;
        }

        $result = $this->_call('SetExpressCheckout', $request);

        return $result;
    }

    protected function _callGetExpressCheckoutDetails(Sellvana_Sales_Model_Order_Payment $payment)
    {
        $token = $payment->get('transaction_token');

        $result = $this->_call('GetExpressCheckoutDetails', ['TOKEN' => $token]);
        if (!empty($result['error'])) {
            return $result;
        }

        if (empty($result['response']['PAYERID'])) {
            $result['error']['message'] = 'Payment action could not be initiated';
        }
        return $result;
    }

    protected function _callDoExpressCheckoutPayment(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $payment = $transaction->payment();
        $token = $payment->get('transaction_token');
        $payerId = $payment->getData('transaction/payer_id');

        $request = [
            'TOKEN' => $token,
            'PAYERID' => $payerId,
        ];
        $request = $this->_addOrderInfo($payment, $request);
        if ($this->getConfig('show_shipping')) {
            $request = $this->_addShippingInfo($payment, $request);
        }

        $result = $this->_call('DoExpressCheckoutPayment', $request);

        return $result;
    }

    protected function _callDoAuthorization(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $request = [
            'TRANSACTIONID' => $transaction->get('parent_transaction_id'),
            'CURRENCYCODE' => $transaction->payment()->order()->get('order_currency'),
            'AMT' => $transaction->get('amount'),
        ];

        $result = $this->_call('DoAuthorization', $request);

        return $result;
    }

    protected function _callDoVoid(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $request = [
            'AUTHORIZATIONID' => $transaction->get('parent_transaction_id'),
        ];

        $result = $this->_call('DoVoid', $request);

        return $result;
    }

    protected function _callDoReauthorization(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $request = [
            'AUTHORIZATIONID' => $transaction->get('parent_transaction_id'),
            'CURRENCYCODE' => $transaction->payment()->order()->get('order_currency'),
            'AMT' => $transaction->get('amount'),
        ];

        $result = $this->_call('DoReauthorization', $request);

        return $result;
    }

    protected function _callDoCapture(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $payment = $transaction->payment();
        $amount = $transaction->get('amount');
        $amtDue = $payment->get('amount_due');

        $request = [
            'AUTHORIZATIONID' => $transaction->get('parent_transaction_id'),
            'AMT' => $amount,
            'CURRENCYCODE' => $transaction->payment()->order()->get('order_currency'),
            'COMPLETETYPE' => $amount >= $amtDue ? 'Complete' : 'NotComplete',
        ];

        $result = $this->_call('DoCapture', $request);

        return $result;
    }

    protected function _callRefundTransaction(Sellvana_Sales_Model_Order_Payment_Transaction $transaction)
    {
        $request = [
            'TRANSACTIONID' => $transaction->get('parent_transaction_id'),
            'REFUNDTYPE' => 'Partial',
            'CURRENCYCODE' => $transaction->payment()->order()->get('order_currency'),
            'AMT' => $transaction->get('amount'),
        ];

        $result = $this->_call('RefundTransaction', $request);

        return $result;
    }

    protected function _addOrderInfo(Sellvana_Sales_Model_Order_Payment $payment, $request, $n = null)
    {
        $order = $payment->order();
        $currency = $order->get('order_currency');

        $p = null !== $n ? "PAYMENTREQUEST_{$n}_" : '';

        $request["{$p}PAYMENTACTION"] = $this->getConfig("payment_action");
        $request["{$p}AMT"]           = number_format($payment->get("amount_due"), 2);
        $request["{$p}ITEMAMT"]       = number_format($order->get("subtotal") - $order->get("discount_amount"), 2);
        $request["{$p}SHIPPINGAMT"]   = number_format($order->get("shipping_price"), 2);
        $request["{$p}TAXAMT"]        = number_format($order->get("tax_amount"), 2);
        $request["{$p}CURRENCYCODE"]  = $currency ? $currency : "USD";

        if ($order->get('discount_amount')) {
            $request["{$p}REDEEMEDOFFERNAME"] = $order->get('coupon_code');
            $request["{$p}REDEEMEDOFFERAMOUNT"] = $order->get('discount_amount');
        }

        $i = 0;
        foreach ($order->items() as $item) {
            $request["L_{$p}NAME{$i}"] = $item->get('product_name');
            $request["L_{$p}AMT{$i}"] = number_format($item->get('price'), 2);
            $request["L_{$p}QTY{$i}"] = (int)$item->get('qty_ordered');
            $request["L_{$p}TAXAMT{$i}"] = number_format($item->get('tax_amount'), 2);
            $request["L_{$p}ITEMWEIGHTVALUE{$i}"] = number_format($item->get('shipping_weight'), 2);
            //$request["L_{$p}ITEMWEIGHTUNIT{$i}"] = $item->get('');
            //$request["L_{$p}ITEMURL{$i}"] = $item->get('');
            $i++;
        }
        if ($order->get('discount_amount') > 0) {
            $request["L_{$p}NAME{$i}"] = $this->_('Discount');
            $request["L_{$p}AMT{$i}"] = -number_format($order->get('discount_amount'), 2);
            $request["L_{$p}QTY{$i}"] = 1;
            $request["L_{$p}TAXAMT{$i}"] = 0;
            $request["L_{$p}ITEMWEIGHTVALUE{$i}"] = 0;
        }
        return $request;
    }

    protected function _addShippingInfo(Sellvana_Sales_Model_Order_Payment $payment, $request, $n = null)
    {
        $order = $payment->order();

        $p = null !== $n ? "PAYMENTREQUEST_{$n}_" : '';

        $request["{$p}SHIPTONAME"]        = $order->shipping_firstname . " " . $order->shipping_lastname;
        $request["{$p}SHIPTOSTREET"]      = $order->shipping_street1;
        $request["{$p}SHIPTOSTREET2"]     = $order->shipping_street2;
        $request["{$p}SHIPTOCITY"]        = $order->shipping_city;
        $request["{$p}SHIPTOSTATE"]       = $order->shipping_region;
        $request["{$p}SHIPTOZIP"]         = $order->shipping_postcode;
        $request["{$p}SHIPTOCOUNTRYCODE"] = $order->shipping_country;
        $request["{$p}SHIPTOPHONENUM"]    = $order->shipping_phone;

        return $request;
    }

    public function getConfig($key = null)
    {
        if (empty($this->_config)) {
            $config = $this->BConfig->get('modules/Sellvana_PaymentPaypal');
            $sandbox = $config['sandbox']['mode'] == 'on'
                || $config['sandbox']['mode'] == 'ip' && in_array($this->BRequest->ip(), explode(',', $config['sandbox']['ip']));
            $this->_config = $config[$sandbox ? 'sandbox' : 'production'];
        }
        return null === $key ? $this->_config : (isset($this->_config[$key]) ? $this->_config[$key] : null);
    }

    /**
     * @param string $methodName
     * @param array $request
     * @param Sellvana_Sales_Model_Order_Payment|Sellvana_Sales_Model_Order_Payment_Transaction $entity
     * @return array
     */
    public function _call($methodName, $request, $entity = null)
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
        $this->BDebug->log(print_r($response, 1), 'paypal.log');

        if (!empty($response['ACK'])) {
            $ack = strtoupper($response['ACK']);
            if ($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING') {
                if ($entity && $this->BDebug->is('DEBUG')) {
                    $entity->setData('last_api_call', $result);
                }
                if ($methodName !== 'SetExpressCheckout') {
                    #echo "<pre>"; var_dump($result); echo "<pre>";
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
            if ($entity) {
                $entity->setData('last_api_call', $result);
            }
        }
        //echo "<pre>"; var_dump($result); echo "<pre>"; exit;
        return $result;
    }
}
