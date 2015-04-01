<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_LibRecaptcha_Main extends BClass
{
    public function html($error = null)
    {
        require_once __DIR__ . '/recaptchalib.php';
        $publicKey = $this->BConfig->get('modules/FCom_LibRecaptcha/public_key');
        return recaptcha_get_html($publicKey, $error);
    }

    public function check()
    {
        $r = $this->BRequest;
        if ($r->post('recaptcha_response_field')) {
            require_once __DIR__ . '/recaptchalib.php';
            $privateKey = $this->BConfig->get('modules/FCom_LibRecaptcha/private_key');
            $resp = recaptcha_check_answer($privateKey, $r->ip(), $r->post('recaptcha_challenge_field'), $r->post('recaptcha_response_field'));
            if ($resp->is_valid) {
                return true;
            } else {
                return $resp->error;
            }
        }
        return false;
    }
}
