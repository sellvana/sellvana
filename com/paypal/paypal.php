<?php

class FCom_PayPal extends BClass
{
    static public function bootstrap()
    {

    }
}

class FCom_PayPal_Ctrl extends BActionController
{

}


class FCom_Paypal extends BClass
{
    protected static $_version = '3.0';
/*
    protected static $_apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';
    protected static $_webUrl = 'https://www.sandbox.paypal.com/';
    protected static $_apiUserName = 'm0sh3g_1237535915_biz_api1.gmail.com';
    protected static $_apiPassword = '1237536002';
    protected static $_apiSignature = 'ALBf.y66AU1jYhtcAmEPnWSft2J7Ad1OQnziH.yZQZAL1NgegB4HyHT0';
*/
    protected static $_apiUrl = 'https://api-3t.paypal.com/nvp';
    protected static $_webUrl = 'https://www.paypal.com/cgi-bin/';
    protected static $_apiUserName = 'paypal_api1.unirgy.com';
    protected static $_apiPassword = 'F3NZNHDQ9369Q29H';
    protected static $_apiSignature = 'Aw93-4ljEKoS7CBLEeEJe9JJpdkGAOsltfNpiQWWsj8uBcIwtX.IsoGv';

    public static function sandbox()
    {
        if (!in_array($_SERVER['REMOTE_ADDR'], array('67.170.161.89'))) {
            return;
        }
        self::$_apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';
        self::$_webUrl = 'https://www.sandbox.paypal.com/';
        self::$_apiUserName = 'm0sh3g_1237535915_biz_api1.gmail.com';
        self::$_apiPassword = '1237536002';
        self::$_apiSignature = 'ALBf.y66AU1jYhtcAmEPnWSft2J7Ad1OQnziH.yZQZAL1NgegB4HyHT0';
    }

    public static function redirect()
    {
        self::sandbox();

        Cart::save(array(
            'order_status' => 'paypal.started',
            'license_agreed' => $_POST['license_agreed'],
            'comments' => !empty($_POST['comments']) ? $_POST['comments'] : '',
        ));
        $nvpArr = array(
            'INVNUM'        => Cart::get('id'),
            'AMT'           => Cart::get('subtotal'),
            'PAYMENTACTION' => 'Sale',
            'CURRENCYCODE'  => 'USD',
            'RETURNURL'     => 'http://dev.unirgy.com/u/paypal_return',
            'CANCELURL'     => 'http://dev.unirgy.com/u/paypal_cancel',
            //'RETURNURL'     => 'https://secure.unirgy.com/checkout/process.php?paypal=return',
            //'CANCELURL'     => 'https://secure.unirgy.com/checkout/process.php?paypal=cancel',
            //'PAGESTYLE'     => 'paypal',
        );
        $resArr = self::call('SetExpressCheckout', $nvpArr);
#echo "<xmp>"; print_r($resArr); echo "</xmp>"; exit;
        if (false===$resArr) {
            throw new BException(print_r($resArr, 1));
        }
        $sData =& BSession::i()->dataToUpdate();
        $sData['paypal']['token'] = $resArr['TOKEN'];
        BResponse::i()->redirect(self::$_webUrl.'webscr?cmd=_express-checkout&useraction=commit&token='.$resArr['TOKEN']);
    }

    public static function complete()
    {
        $sData =& BSession::i()->dataToUpdate();
        self::sandbox();

        $resArr = self::call('GetExpressCheckoutDetails',  array('TOKEN' => $sData['paypal']['token']));
        if (false===$resArr) {
            return false;
        }
        Cart::save(array(
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
        ));

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
        $resArr = self::call('DoExpressCheckoutPayment', $nvpArr);
        if (false===$resArr) {
            return false;
        }
        Cart::save(array(
            'order_status' => 'complete',
            'paypal_transactionid' => $resArr['TRANSACTIONID'],
            'paypal_paid' => $resArr['AMT'],
            'paypal_fee' => !empty($resArr['FEEAMT']) ? $resArr['FEEAMT'] : 0,
            'paypal_tax' => !empty($resArr['TAXAMT']) ? $resArr['TAXAMT'] : 0,
            'paypal_status' => $resArr['PAYMENTSTATUS']=='Completed' ? 'Completed' : $resArr['PAYMENTSTATUS'].'/'.$resArr['PENDINGREASON'].'/'.$resArr['REASONCODE'],
        ));
        //Cart::notify();

        AOrder::i()->createFromCart(Cart::get('id'))->email('admin_notify')->email('customer_confirm')->email('customer_license');

        $sData['last_order']['id'] = Cart::get('id');

        FreshBooks::i()->invoice(true, true);
        Cart::reset();
        Cart::forget();
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout_success");
    }

    public static function cancel()
    {
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout");
    }

    public static function call($methodName, $nvpArr)
    {
        self::sandbox();

        $nvpArr = array_merge(array(
            'METHOD'    => $methodName,
            'VERSION'   => self::$_version,
            'USER'      => self::$_apiUserName,
            'PWD'       => self::$_apiPassword,
            'SIGNATURE' => self::$_apiSignature,
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
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout");
    }
}