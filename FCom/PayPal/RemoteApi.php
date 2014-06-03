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

    public function getError()
    {
        if (empty($this->errorArr['code'])) {
            return "[PAYPAL ERROR N/A] N/A";
        }
        return "[PAYPAL ERROR {$this->errorArr['code']}] {$this->errorArr['short_message']} - {$this->errorArr['long_message']}";
    }

    public function getExpressCheckoutUrl($token)
    {
        return 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=' . $token;
    }
}



