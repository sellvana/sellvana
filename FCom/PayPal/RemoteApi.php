<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PayPal_RemoteApi extends BClass
{
    protected static $_apiVersion = '72.0';
    protected static $_baseUrl;
    protected static $_apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';

    protected $errorArr = [];

    public function __construct()
    {
        if (($sUrl = $this->BConfig->get('secure_url'))) {
            $m = $this->BApp->m();
            $m->base_href = $sUrl . '/' . $m->url_prefix;
        }
    }

    /**
     * @param $methodName
     * @param $nvpArr
     * @return bool
     */
    public function call($methodName, $nvpArr)
    {
        $config = $this->BConfig->get('modules/FCom_PayPal');
        $sandbox = $config['sandbox']['mode'] == 'on'
            || $config['sandbox']['mode'] == 'ip'
                && in_array($this->BRequest->ip(), explode(',', $config['sandbox']['ip']));
        $apiConfig = $config[$sandbox ? 'sandbox' : 'production'];

        $nvpArr = array_merge([
            //'x'         => 'y',
            'METHOD'    => $methodName,
            'VERSION'   => static::$_apiVersion,
            'USER'      => $apiConfig['username'],
            'PWD'       => $apiConfig['password'],
            'SIGNATURE' => $apiConfig['signature'],
        ], $nvpArr);

        $result = $this->BUtil->remoteHttp('GET', self::$_apiUrl, $nvpArr);
        parse_str($result, $resArr);

        $ack = 'undefined';
        if (!empty($resArr['ACK'])) {
            $ack = strtoupper($resArr['ACK']);
            if ($ack == 'SUCCESS' || $ack == 'SUCCESSWITHWARNING') {
                return $resArr;
            }
        }
        $errorArr = [
            'type' => 'API',
            'ack' => $ack,
            'response' => $resArr,
        ];
        if (isset($resArr['VERSION'])) {
            $errorArr['version'] = $resArr['VERSION'];
        }

        for ($i = 0; isset($resArr['L_SHORTMESSAGE' . $i]); $i++) {
            $errorArr['code'] = $resArr['L_ERRORCODE' . $i];
            $errorArr['short_message'] = $resArr['L_SHORTMESSAGE' . $i];
            $errorArr['long_message'] = $resArr['L_LONGMESSAGE' . $i];
        }
        //$sData =& $this->BSession->dataToUpdate();
        //$sData['checkout_error']['message'] = "[PAYPAL ERROR {$errorArr['code']}] {$errorArr['short_message']} - {$errorArr['long_message']}";
        $this->errorArr = $errorArr;
        return false;
        //$this->BResponse->redirect($this->BApp->m('FCom_Checkout')->baseHref());
    }

    /**
     * @return string
     */
    public function getError()
    {
        if (empty($this->errorArr['code'])) {
            return "[PAYPAL ERROR N/A] N/A";
        }
        return "[PAYPAL ERROR {$this->errorArr['code']}] {$this->errorArr['short_message']} - {$this->errorArr['long_message']}";
    }

    /**
     * @param $token
     * @return string
     */
    public function getExpressCheckoutUrl($token)
    {
        return 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=' . $token;
    }

    public function callSetExpressCheckout($order)
    {
        $baseUrl = $this->BApp->href('paypal');
        $nvpShippingAddress = [];
        if ($this->BConfig->get('modules/FCom_PayPal/show_shipping') == 'on') {
            $nvpShippingAddress = [
                'NOSHIPPING' => 0,
                'REQCONFIRMSHIPPING' => 0,
                'PAYMENTREQUEST_0_SHIPTONAME' => $order->shipping_firstname . ' ' . $order->shipping_lastname,
                'PAYMENTREQUEST_0_SHIPTOSTREET' => $order->shipping_street1,
                'PAYMENTREQUEST_0_SHIPTOSTREET2' => $order->shipping_street2,
                'PAYMENTREQUEST_0_SHIPTOCITY' => $order->shipping_city,
                'PAYMENTREQUEST_0_SHIPTOSTATE' => $order->shipping_region,
                'PAYMENTREQUEST_0_SHIPTOZIP' => $order->shipping_postcode,
                'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $order->shipping_country,
                'PAYMENTREQUEST_0_SHIPTOPHONENUM' => $order->shipping_phone,
            ];
        } else {
            $nvpShippingAddress['NOSHIPPING'] = 1;
        }

        $nvpArr = [
            'INVNUM'                            => $order->id(),
            'PAYMENTREQUEST_0_AMT'              => number_format($order->amount_due, 2),
            'PAYMENTREQUEST_0_PAYMENTACTION'    => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'     => 'USD',
            'RETURNURL'                         => $baseUrl . '/return',
            'CANCELURL'                         => $baseUrl . '/cancel',
            //'PAGESTYLE'     => 'paypal',
        ];
        $nvpArr = array_merge($nvpArr, $nvpShippingAddress);
        //print_r($nvpArr);exit;
        $resArr = $this->call('SetExpressCheckout', $nvpArr);
//echo "<xmp>"; print_r($resArr); echo "</xmp>"; exit;
        if (false === $resArr) {
            return ['error' =>$this->getError()];
        }

        $sData =& $this->BSession->dataToUpdate();
        $sData['paypal']['token'] = $resArr['TOKEN'];

        $result = [];
        $result['response'] = $resArr;
        $result['redirect_to'] = $this->getExpressCheckoutUrl($resArr['TOKEN']);

        return $result;
    }
}



