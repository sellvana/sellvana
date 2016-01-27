<?php

/**
 * Class FCom_OAuth_Model_ConsumerToken
 *
 * @property FCom_OAuth_Main $FCom_OAuth_Main
 */

class FCom_OAuth_Model_ConsumerToken extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_oauth_consumer_token';
    static protected $_origClass = __CLASS__;

    public function sessionToken()
    {
        $hlp = $this->FCom_OAuth_Main;
        $providerName = $hlp->getProvider();
        if (!$providerName) {
            return false;
        }
        $consumerSess =& $hlp->getConsumerSession($providerName);
        if (empty($consumerSess['access_token'])) {
            return false;
        }
        return $this->loadWhere(['provider' => (string)$providerName, 'token' => (string)$consumerSess['access_token']]);
    }
}
