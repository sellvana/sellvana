<?php

include_once __DIR__ . '/lib/PHPGangsta/GoogleAuthenticator.php';

class FCom_LibGoogle2FA_Main extends BClass
{
    /**
     * @var PHPGangsta_GoogleAuthenticator
     */
    protected $_ga;

    /**
     * @return PHPGangsta_GoogleAuthenticator
     */
    protected function _getGA()
    {
        if (!$this->_ga) {
            $this->_ga = new PHPGangsta_GoogleAuthenticator();
        }
        return $this->_ga;
    }

    public function createSecret($secretLength = 16)
    {
        return $this->_getGA()->createSecret($secretLength);
    }

    public function getQRCodeGoogleUrl($name, $secret, $title = null)
    {
        return $this->_getGA()->getQRCodeGoogleUrl($name, $secret, $title);
    }

    public function getCode($secret, $timeSlice = null)
    {
        return $this->_getGA()->getCode($secret, $timeSlice);
    }

    public function verifyCode($secret, $code, $discrepancy = 1, $currentTimeSlice = null)
    {
        return $this->_getGA()->verifyCode($secret, $code, $discrepancy, $currentTimeSlice);
    }
}