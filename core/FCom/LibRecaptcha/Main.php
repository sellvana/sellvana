<?php

class FCom_LibRecaptcha_Main extends BClass
{
    protected static $_apiUrl = 'https://www.google.com/recaptcha/api/siteverify';

    public function html($error = null)
    {
        $publicKey = $this->BConfig->get('modules/FCom_LibRecaptcha/public_key');

        #require_once __DIR__ . '/recaptchalib.php';
        #return recaptcha_get_html($publicKey, $error);

        return <<<EOT
<script src='https://www.google.com/recaptcha/api.js'></script>
<div class="g-recaptcha" data-sitekey="{$publicKey}"></div>
EOT;

    }

    public function check()
    {
        $response = $this->BRequest->request('g-recaptcha-response');
        if ($response) {
            $privateKey = $this->BConfig->get('modules/FCom_LibRecaptcha/private_key');

            #require_once __DIR__ . '/recaptchalib.php';
            #$resp = recaptcha_check_answer($privateKey, $r->ip(), $r->post('recaptcha_challenge_field'), $r->post('recaptcha_response_field'));
            #return $resp->is_valid ? true : $resp->error;

            $response = $this->BUtil->remoteHttp('POST', static::$_apiUrl, [
                'secret' => $privateKey,
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
