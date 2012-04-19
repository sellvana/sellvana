<?php

class FCom_Customer_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_login()
    {
        $this->messages('customer/login');
        $this->layout('/customer/login');
    }

    public function action_login__POST()
    {
        try {
            $r = BRequest::i()->post('login');
            if (!empty($r['email']) && !empty($r['password'])) {
                $user = FCom_Customer_Model_Customer::i()->authenticate($r['email'], $r['password']);
                if ($user) {
                    $user->login();
                } else {
                    throw new Exception('Invalid email or password.');
                }
            }
            $url = BSession::i()->data('login_orig_url');
            BResponse::i()->redirect(!empty($url) ? $url : BApp::baseUrl());
        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
            BResponse::i()->redirect(BApp::href('login'));
        }
    }

    public function action_password_recover()
    {
        $this->messages('customer/password-recover');
        $this->layout('/customer/password/recover');
    }

    public function action_password_recover__POST()
    {

    }
    public function action_password_reset()
    {
        $this->messages('customer/password-reset');
        $this->layout('/customer/password/reset');
    }

    public function action_password_reset__POST()
    {

    }

    public function action_logout()
    {
        FCom_Customer_Model_Customer::i()->logout();
        BResponse::i()->redirect(BApp::baseUrl());
    }
}