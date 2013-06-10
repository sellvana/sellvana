<?php

class FCom_Ogone_RemoteApi extends BClass
{
    static protected $_brandsMap = array(
        'Acceptgiro' => 'Acceptgiro',
        'AIRPLUS' => 'CreditCard',
        'American Express' => 'CreditCard',
        'Aurora' => 'CreditCard',
        'Aurore' => 'CreditCard',
        'Bank transfer' => 'Bank transfer',
        'BCMC' => 'CreditCard',
        'Billy' => 'CreditCard',
        'cashU' => 'cashU',
        'CB' => 'CreditCard',
        'CBC Online' => 'CBC Online',
        'CENTEA Online' => 'CENTEA Online',
        'Cofinoga' => 'CreditCard',
        'Dankort' => 'CreditCard',
        'Dexia Direct Net' => 'Dexia Direct Net',
        'Diners Club' => 'CreditCard',
        'Direct Debits AT' => 'Direct Debits AT',
        'Direct Debits DE' => 'Direct Debits DE',
        'Direct Debits NL' => 'Direct Debits NL',
        'eDankort' => 'eDankort',
        'EPS' => 'EPS',
        'Fortis Pay Button' => 'Fortis Pay Button',
        'giropay' => 'giropay',
        'iDEAL' => 'iDEAL',
        'ING HomePay' => 'ING HomePay',
        'InterSolve' => 'InterSolve',
        'JCB' => 'CreditCard',
        'KBC Online' => 'KBC Online',
        'Maestro' => 'CreditCard',
        'MaestroUK' => 'CreditCard',
        'MasterCard' => 'CreditCard',
        'MiniTix' => 'MiniTix',
        'MPASS' => 'MPASS',
        'NetReserve' => 'CreditCard',
        'Payment on Delivery' => 'Payment on Delivery',
        'PAYPAL' => 'PAYPAL',
        'paysafecard' => 'paysafecard',
        'PingPing' => 'PingPing',
        'PostFinance + card' => 'PostFinance Card',
        'PostFinance e-finance' => 'PostFinance e-finance',
        'PRIVILEGE' => 'CreditCard',
        'Sofort Uberweisung' => 'DirectEbanking',
        'Solo' => 'CreditCard',
        'TUNZ' => 'TUNZ',
        'UATP' => 'CreditCard',
        'UNEUROCOM' => 'UNEUROCOM',
        'VISA' => 'CreditCard',
        'Wallie' => 'Wallie',
    );

    static protected $_allowedCurrencies = array(
        'AED', 'ANG', 'ARS', 'AUD', 'AWG', 'BGN', 'BRL', 'BYR', 'CAD', 'CHF',
        'CNY', 'CZK', 'DKK', 'EEK', 'EGP', 'EUR', 'GBP', 'GEL', 'HKD', 'HRK',
        'HUF', 'ILS', 'ISK', 'JPY', 'KRW', 'LTL', 'LVL', 'MAD', 'MXN', 'NOK',
        'NZD', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'SKK', 'THB', 'TRY', 'UAH',
        'USD', 'XAF', 'XOF', 'XPF', 'ZAR'
    );

    static protected $_allowedLanguages = array(
        'en_US' => 'English', 'cs_CZ' => 'Czech', 'de_DE' => 'German',
        'dk_DK' => 'Danish', 'el_GR' => 'Greek', 'es_ES' => 'Spanish',
        'fr_FR' => 'French', 'it_IT' => 'Italian', 'ja_JP' => 'Japanese',
        'nl_BE' => 'Flemish', 'nl_NL' => 'Dutch', 'no_NO' => 'Norwegian',
        'pl_PL' => 'Polish', 'pt_PT' => 'Portugese', 'ru_RU' => 'Russian',
        'se_SE' => 'Swedish', 'sk_SK' => 'Slovak', 'tr_TR' => 'Turkish'
    );

    static protected $_apiUrl = array(
        'TEST' => "https://secure.ogone.com/ncol/test/orderstandard_utf8.asp",
        'PROD' => "https://secure.ogone.com/ncol/prod/orderstandard_utf8.asp",
    );

    static public $shaMethods = array(
        'sha1' => 'SHA-1',
        'sha256' => 'SHA-256',
        'sha512' => 'SHA-512',
    );

    public function prepareRequestData()
    {
        return array();
        $conf = new BData(BConfig::i()->get('modules/FCom_Ogone'));
        $order = new BData(array()); // order
        $cust = new BData(array()); // customer
        $ogoneOrder = new BData(array()); // order
        //$ogoneOrder = FCom_Ogone_Model_Order::i()->load($order->id, 'order_id');

        $complus = '';
        $paramplus = array('amountOfProducts' => '5', 'usedCoupon' => 1); //?
        $homeUrl = FCom_Frontend_Main::i()->href('');
        $callbackUrl = FCom_Frontend_Main::i()->href('ogone/callback');
        $data = array(
            'RL' => 'ncol_2.0',
            'PSPID' => $conf->pspid,
            'ORDERID' => $order->increment_id,
            'CURRENCY' => $order->order_currency,
            'LANGUAGE' => $order->language,
            'AMOUNT' => intval($order->amount_due * 100),
            'CN' => $cust ? $cust->fullName() : null,
            'EMAIL' => $cust ? $cust->email : null,
            'COM' => $order->getTextDescription(),
            //'PM' => static::$_brandsMap[$ogoneOrder->brand],
            //'BRAND' => $ogoneOrder->brand,
            'OPERATION' => 'sales', // sales or authorization

            'OWNERADDRESS' => $conf->owner_address,
            'OWNERTOWN' => $conf->owner_town,
            'OWNERZIP' => $conf->owner_zip,
            'OWNERCTY' => $conf->owner_country,
            'OWNERTELNO' => $conf->owner_phone,

            'ACCEPTURL' => $callbackUrl,
            'DECLINEURL' => $callbackUrl,
            'EXCEPTIONURL' => $callbackUrl,
            'CANCELURL' => $callbackUrl,

            'HOMEURL' => $homeUrl,
            'CATALOGURL' => $catalogUrl,

            'TITLE' => $conf->title,
            'TP' => $conf->template ? FCom_Frontend_Main::i()->href('ogone/template') : null,
            'LOGO' => $conf->logo,
            'FONTTYPE' => $conf->fonttype,
            'BGCOLOR' => $conf->bgcolor,
            'TXTCOLOR' => $conf->txtcolor,
            'TBLBGCOLOR' => $conf->tblbgcolor,
            'TBLTXTCOLOR' => $conf->tbltxtcolor,
            'BUTTONBGCOLOR' => $conf->buttonbgcolor,
            'BUTTONTXTCOLOR' => $conf->buttontxtcolor,

            'COMPLUS' => $complus,
            'PARAMPLUS' => http_build_query($paramplus),
            //'PARAMVAR' => 'PARAMVAR',
        );
        $data['SHASIGN'] = $this->_sha($data, 'in');

        $ogoneOrder->set('shasign', $data['SHASIGN'])->save();

        $url = static::$_apiUrl[$conf->mode_prod ? 'PROD' : 'TEST'];
        return array('form_url'=>$url, 'data'=>$data);
    }

    public function processResult($data=null)
    {
        if (is_null($data)) {
            $data = BRequest::i()->request();
        }
        if (empty($data['SHASIGN']) || $this->_sha($data, 'out') != $data['SHASIGN']) {
            throw new BException('SHA-OUT missing or invalid');
        }
        if (empty($data['orderID'])) {
            throw new BException('Missing orderID');
        }
        $conf = new BData(BConfig::i()->get('modules/FCom_Ogone'));
        $orderId = $data['orderID'];
        $order = FCom_Sales_Model_Order::i()->load($orderId, 'increment_id');
        $ogoneOrder = FCom_Ogone_Model_Order::i()->load($order->id, 'order_id');

        // Process response
        $statusCode = $data['STATUS'];

        $ogoneOrder->set(array('error'=>0, 'status_code'=>$statusCode))->save();
        $update = null;
        $comment = null;
        $notifyCustomer = false;
        $status = false;

        switch ($statusCode) {
        // SUCCESS
        case 9: // Capture accepted
            $update = array('custom_status' => $conf->order_status_captured, 'payment_status' => 'CAPTURED');
            $comment = 'Ogone capture accepted';
            $notifyCustomer = true;
            $status = true;
            break;

        case 91: // Capture pending
            $update = array('custom_status' => $conf->order_status_captpend, 'payment_status' => 'CAPTURE_PENDING');
            $comment = 'Ogone capture pending';
            $notifyCustomer = true;
            $status = true;
            break;

        case 5: // Authorized
            $update = array('custom_status' => $conf->order_status_authorized, 'payment_status' => 'AUTHORIZED');
            $comment = 'Ogone authorized';
            $notifyCustomer = true;
            $status = true;
            break;

        case 51: // Authorization pending
            $update = array('custom_status' => $conf->order_status_authpend, 'payment_status' => 'AUTH_PENDING');
            $comment = 'Ogone authorization pending';
            $notifyCustomer = true;
            $status = true;
            break;

        // FAIL
        case 0: // Payment entry not completed
            $comment = 'Ogone payment entry not completed';
            break;

        case 1: // Cancelled by user
            $comment = 'Ogone payment was cancelled';
            break;

        case 2: // not authorized (number of tries exceeds payment retry setting)
            // Don't confirm the order!!
            // Transaction retry is possible, therefore return to cart.
            // Please note that in case of status 2, the transaction has been refused by Ogone.
            // This will be visible in the transaction overview in the Ogone management application.
            // If you were to resend the the same transaction details, it would be automatically refused by Ogone, even
            // if you entered proper payment details.
            // However, opencart 1.5.x generates a new orderID on checkout confirm, so no harm in resubmitting.
            $update = array('payment_status' => 'AUTH_FAILED');
            $comment = 'Ogone payment failed authorization';
            break;

        case 52:
        case 92:
            // In both cases 52 and 92 Ogone recommends not reprocessing the transaction, becos it could result in double payment
            // Therefore we are confirming the order.
            $update = array('custom_status' => $conf->order_status_uncertain, 'payment_status' => 'UNCERTAIN');
            $comment = 'Ogone payment uncertain status';
            $notifyCustomer = true;
            $status = true;
            break;

        default: // Shouldn't happen, but anyways
            $update = array('custom_status' => $conf->order_status_uncertain, 'payment_status' => 'UNCERTAIN');
            $comment = 'Ogone payment uncertain status';
            $status = true;
            break;
        }

        if ($update) {
            $order->changeStatus($update);
        }
        if ($comment) {
            $order->addComment(BLocale::i()->_($comment));
        }
        if ($notifyCustomer) {
            $order->notifyCustomer('ORDER_RECEIVED');
        }
        return $status;
    }

    protected function _sha($data, $dir)
    {
        unset($data['SHASIGN']);
        $data = array_change_key_case($data, CASE_UPPER);
        ksort($data);
        array_walk($data, 'trim');
        $data = array_filter($data, function($value) { return (bool) strlen($value); });
        $shaPass = BConfig::i()->get('modules/FCom_Ogone/passphrase_'.$dir);
        $shaMethod = BConfig::i()->get('modules/FCom_Ogone/sha_method');
        if (!$shaMethod) $shaMethod = 'sha512';
        $shaData = '';
        foreach ($data as $k=>$v) {
            $shaData .= $k.'='.$v.$shaPass;
        }
        return strtoupper(hash($shaMethod, $shaData));
    }
}