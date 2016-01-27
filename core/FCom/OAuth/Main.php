<?php

include_once __DIR__ . '/lib/OAuthSimple.php';

/**
 * Class FCom_OAuth_Main
 *
 * @property FCom_OAuth_Model_ConsumerToken $FCom_OAuth_Model_ConsumerToken
 */

class FCom_OAuth_Main extends BClass
{
    protected $_providersConfig;

    protected $_currentProvider;

    protected $_request;

    public function addProvider($providerName, $providerInfo)
    {
        $providerInfo = $this->getProviderInfo($providerName);
        if ($providerInfo) {
            $this->BDebug->debug('Overriding existing provider: ' . $providerName);
        }
        $this->_providersConfig[$providerName] = $providerInfo;
        return $this;
    }

    public function getProviderInfo($providerName)
    {
        static $providersConfig;
        if (!$this->_providersConfig) {
            $this->_providersConfig = $this->BConfig->i(true)->addFile('@FCom_OAuth/providers.yml');
        }
        if (!$providerName) {
            $providerName = $this->getProvider();
        }
        return $this->_providersConfig->get($providerName);
    }

    public function setProvider($providerName)
    {
        $providerInfo = $this->getProviderInfo($providerName);
        if (!$providerInfo) {
            throw new BException('Undefined provider: ' . $providerName);
        }
        $this->_currentProvider = $providerName;
        $this->BSession->set('oauth_current_provider', $providerName);
        return $this;
    }

    public function getProvider()
    {
        if (!$this->_currentProvider) {
            $this->_currentProvider = $this->BSession->get('oauth_current_provider');
        }
        return $this->_currentProvider;
    }

    public function &getConsumerSession($providerName)
    {
        if (!$providerName) {
            $providerName = $this->getProvider();
        }
        $sessData =& $this->BSession->dataToUpdate();
        if (empty($sessData['oauth'][$providerName])) {
            $sessData['oauth'][$providerName] = [];
        }
        return $sessData['oauth'][$providerName];
    }

    public function setReturnUrl($url)
    {
        $this->BSession->set('oauth_return_url', $url);
        return $this;
    }

    public function getReturnUrl()
    {
        return $this->BSession->get('oauth_return_url');
    }

    public function getProviderInstance()
    {
        $providerName = $this->getProvider();
        $providerInfo = $this->getProviderInfo($providerName);
        $version = !empty($providerInfo['version']) ? $providerInfo['version'] : '1.0';
        if (empty($providerInfo['class'])) {
            $className = 'FCom_OAuth_Provider_' . strtoupper($providerName);
            if (!class_exists($className)) {
                $className = 'FCom_OAuth_Provider_BaseV' . $version[0];
            }
        } else {
            $className = $providerInfo['class'];
        }
        return $this->{$className};
    }

    public function loginAction()
    {
        return $this->getProviderInstance()->loginAction();
    }

    public function callbackAction()
    {
        return $this->getProviderInstance()->callbackAction();
    }

    public function onAdminUserLogin($args)
    {
        $tokenModel = $this->FCom_OAuth_Model_ConsumerToken->sessionToken();
        if ($tokenModel) {
            $tokenModel->set('admin_id', $args['user']->id())->save();
        }
    }

    public function onCustomerLogin($args)
    {
        $tokenModel = $this->FCom_OAuth_Model_ConsumerToken->sessionToken();
        if ($tokenModel) {
            $tokenModel->set('customer_id', $args['customer']->id())->save();
        }
    }
}
