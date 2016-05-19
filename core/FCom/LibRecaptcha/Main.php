<?php

class FCom_LibRecaptcha_Main extends BClass
{
    protected static $_apiUrl = 'https://www.google.com/recaptcha/api/siteverify';

    public function isAvailable()
    {
        $c = $this->BConfig;
        $secretKey = $c->get('modules/FCom_LibRecaptcha/secret_key');
        $siteKey = $c->get('modules/FCom_LibRecaptcha/site_key');
        return !empty($secretKey) && !empty($siteKey);
    }

    public function html($error = null)
    {
        $siteKey = $this->BConfig->get('modules/FCom_LibRecaptcha/site_key');

        return <<<EOT
<script src='https://www.google.com/recaptcha/api.js'></script>
<div class="g-recaptcha" data-sitekey="{$siteKey}"></div>
EOT;

    }

    public function check()
    {
        if (!$this->isAvailable()) {
            return true; // if keys are not available, consider not enabled and pass
        }

        $response = $this->BRequest->request('g-recaptcha-response');
        if ($response) {
            $secretKey = $this->BConfig->get('modules/FCom_LibRecaptcha/secret_key');

            $response = $this->BUtil->remoteHttp('POST', static::$_apiUrl, [
                'secret' => $secretKey,
                'response' => $response,
                'remoteip' => $this->BRequest->ip(),
            ]);
            if (!$response) {
                return false;
            }
            $result = $this->BUtil->fromJson($response);
            return !empty($result['success']);
        }
        return false;
    }
}
