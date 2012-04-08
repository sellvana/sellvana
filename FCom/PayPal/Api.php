<?php

class FCom_PayPal_Api extends BClass
{
    protected static $_apiVersion = '3.0';
    protected static $_baseUrl;

    public function __construct()
    {
        if (($sUrl = BConfig::i()->get('secure_url'))) {
            $m = BApp::m();
            $m->base_href = $sUrl.'/'.$m->url_prefix;
        }
    }

    public function call($methodName, $nvpArr)
    {
        $config = BConfig::i()->get('modules/FCom_PayPal');
        $sandbox = $config['sandbox']['mode']=='on'
            || $config['sandbox']['mode']=='ip'
                && in_array(BRequest::i()->ip(), explode(',', $config['sandbox']['ip']));
        $apiConfig = $config[$sandbox ? 'sandbox' : 'production'];

        $nvpArr = array_merge(array(
            'METHOD'    => $methodName,
            'VERSION'   => static::$_apiVersion,
            'USER'      => $apiConfig['username'],
            'PWD'       => $apiConfig['password'],
            'SIGNATURE' => $apiConfig['signature'],
        ), $nvpArr);
        $resArr = BUtil::post(self::$_apiUrl, $nvpArr);

        $ack = strtoupper($resArr['ACK']);
        if ($ack == 'SUCCESS' || $ack=='SUCCESSWITHWARNING') {
            return $resArr;
        }
        $errorArr = array(
            'type' => 'API',
            'ack' => $ack,
            'response' => $resArr,
        );
        if (isset($resArr['VERSION'])) {
            $errorArr['version'] = $resArr['VERSION'];
        }
        if (isset($nvpResArray['CORRELATIONID'])) {
            $errorArr['correlation_id'] = $resArr['CORRELATIONID'];
        }
        for ($i=0; isset($resArr['L_SHORTMESSAGE'.$i]); $i++) {
            $errorArr['code'] = $resArr['L_ERRORCODE'.$i];
            $errorArr['short_message'] = $resArr['L_SHORTMESSAGE'.$i];
            $errorArr['long_message'] = $resArr['L_LONGMESSAGE'.$i];
        }
        $sData =& BSession::i()->dataToUpdate();
        $sData['checkout_error']['message'] = "[PAYPAL ERROR {$errorArr['code']}] {$errorArr['short_message']} - {$errorArr['long_message']}";
        BResponse::i()->redirect(BApp::m('FCom_Checkout')->baseHref());
    }
}

class FCom_PayPal_Controller extends BActionController
{
    public function action_redirect()
    {
        $config = BConfig::i()->get('modules/FCom_PayPal');
        $cart = FCom_Cart::i()->sessionCart();
        $baseUrl = BApp::m('FCom_PayPal')->baseHref();

        Cart::save(array(
            'order_status' => 'paypal.started',
            'license_agreed' => $_POST['license_agreed'],
            'comments' => !empty($_POST['comments']) ? $_POST['comments'] : '',
        ));
        $nvpArr = array(
            'INVNUM'        => $cart->id,
            'AMT'           => $cart->subtotal,
            'PAYMENTACTION' => 'Sale',
            'CURRENCYCODE'  => 'USD',
            'RETURNURL'     => $baseUrl.'/return',
            'CANCELURL'     => $baseUrl.'/cancel',
            //'PAGESTYLE'     => 'paypal',
        );
        $resArr = FCom_PayPal::i()->call('SetExpressCheckout', $nvpArr);
#echo "<xmp>"; print_r($resArr); echo "</xmp>"; exit;
        if (false===$resArr) {
            throw new BException(print_r($resArr, 1));
        }
        $sData =& BSession::i()->dataToUpdate();
        $sData['paypal']['token'] = $resArr['TOKEN'];
        BResponse::i()->redirect(self::$_webUrl.'webscr?cmd=_express-checkout&useraction=commit&token='.$resArr['TOKEN']);
    }

    public function action_complete()
    {
        $sData =& BSession::i()->dataToUpdate();
        $cart = FCom_Cart::i()->sessionCart();

        $resArr = FCom_PayPal::i()->call('GetExpressCheckoutDetails',  array('TOKEN' => $sData['paypal']['token']));
        if (false===$resArr) {
            return false;
        }
        $cart->set(array(
            'order_status' => 'paypal.returned',
            'email' => $resArr['EMAIL'],
            'firstname' => $resArr['FIRSTNAME'],
            'lastname' => $resArr['LASTNAME'],
            'company' => !empty($resArr['COMPANY']) ? $resArr['COMPANY'] : '',
            'street1' => $resArr['SHIPTOSTREET'],
            'street2' => !empty($resArr['SHIPTOSTREET2']) ? $resArr['SHIPTOSTREET2'] : '',
            'city' => $resArr['SHIPTOCITY'],
            'state' => !empty($resArr['SHIPTOSTATE']) ? $resArr['SHIPTOSTATE'] : '',
            'zip' => !empty($resArr['SHIPTOZIP']) ? $resArr['SHIPTOZIP'] : '',
            'country' => $resArr['SHIPTOCOUNTRYCODE'],
            'paypal_payerid' => $resArr['PAYERID'],
            #'paypal_correlationid' => $resArr['CORRELATIONID'],
            'paypal_status' => '!/'.$resArr['PAYERSTATUS'].'/'.$resArr['ADDRESSSTATUS'],
        ))->save();

        $nvpArr = array(
            'TOKEN'         => $resArr['TOKEN'],
            'PAYERID'       => $resArr['PAYERID'],
            'PAYMENTACTION' => 'Sale',
            'AMT'           => $sData['cart']['data']['subtotal'],
            'CURRENCYCODE'  => 'USD',
            'IPADDRESS'     => $_SERVER['SERVER_NAME'],
            //'BUTTONSOURCE'  => '',
        );

         /* Make the call to PayPal to finalize payment
            If an error occured, show the resulting errors
            */
        $resArr = FCom_PayPal::i()->call('DoExpressCheckoutPayment', $nvpArr);
        if (false===$resArr) {
            return false;
        }
        $cart->set(array(
            'order_status' => 'complete',
            'paypal_transactionid' => $resArr['TRANSACTIONID'],
            'paypal_paid' => $resArr['AMT'],
            'paypal_fee' => !empty($resArr['FEEAMT']) ? $resArr['FEEAMT'] : 0,
            'paypal_tax' => !empty($resArr['TAXAMT']) ? $resArr['TAXAMT'] : 0,
            'paypal_status' => $resArr['PAYMENTSTATUS']=='Completed' ? 'Completed' : $resArr['PAYMENTSTATUS'].'/'.$resArr['PENDINGREASON'].'/'.$resArr['REASONCODE'],
        ))->save();
        //Cart::notify();

        $order = FCom_Order::i()->createFromCart($cart->id)
            ->email('admin_notify')
            ->email('customer_confirm')
            ->email('customer_license');

        $sData['last_order']['id'] = $order->id;

        FCom_FreshBooks::i()->invoice(true, true);
        Cart::reset();
        Cart::forget();
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout_success");
    }

    public function action_cancel()
    {
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout");
    }

}

