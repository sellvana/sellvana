<?php

class FCom_OAuth_Model_ConsumerToken extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_oauth_consumer_token';
    static protected $_origClass = __CLASS__;

    static public function sessionToken()
    {
        $hlp = FCom_OAuth_Main::i();
        $providerName = $hlp->getProvider();
        if (!$providerName) {
            return false;
        }
        $consumerSess =& $hlp->getConsumerSession($providerName);
        if (empty($consumerSess['access_token'])) {
            return false;
        }
        return static::loadWhere(['provider' => (string)$providerName, 'token' => (string)$consumerSess['access_token']]);
    }
}
