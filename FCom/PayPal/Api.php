<?php

class FCom_PayPal_Api extends BClass
{
    protected static $_apiVersion = '3.0';
    protected static $_baseUrl;
    protected static $_apiUrl = 'https://api-3t.sandbox.paypal.com/nvp';

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
//print_r($resArr);exit;
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
        //return $resArr;
        BResponse::i()->redirect(BApp::m('FCom_Checkout')->baseHref());
    }
}



