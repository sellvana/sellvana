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
        $user = FCom_Customer_Model_Customer::i()->load(BRequest::i()->request('email'), 'email');
        if ($user) {
            $user->recoverPassword();
        }
        BSession::i()->addMessage('If the email address was correct, you should receive an email shortly with password recovery instructions.',
                'success', 'frontend');
        BResponse::i()->redirect(BApp::href('login'));
    }
    public function action_password_reset()
    {
        $token = BRequest::i()->request('token');
        if ($token && ($user = FCom_Customer_Model_Customer::i()->load($token, 'token'))) {
            $this->messages('customer/password-reset');
            $this->layout('/customer/password/reset');
        } else {
            BSession::i()->addMessage('Invalid link. It is possible your recovery link has expired.', 'error', 'frontend');
            BResponse::i()->redirect(BApp::href('login'));
        }
    }

    public function action_password_reset__POST()
    {
        $token = BRequest::i()->request('token');
        $password = BRequest::i()->post('password');
        if ($token && $password && ($user = FCom_Customer_Model_Customer::i()->load($token, 'token'))) {
            $user->resetPassword($password);
            BSession::i()->addMessage('Password has been reset', 'success', 'frontend');
            BResponse::i()->redirect(BApp::baseUrl());
        } else {
            BSession::i()->addMessage('Invalid form data', 'error', 'frontend');
            BResponse::i()->redirect(BApp::href('login'));
        }
    }

    public function action_logout()
    {
        FCom_Customer_Model_Customer::i()->logout();
        BResponse::i()->redirect(BApp::baseUrl());
    }

    public function action_register()
    {
        $this->messages('customer/register');
        $this->layout('/customer/register');
    }

    public function action_register__POST()
    {
        try {
            $r = BRequest::i()->post('model');
            $a = BRequest::i()->post('address');

            $customer = FCom_Customer_Model_Customer::i()->register($r);
            FCom_Customer_Model_Address::i()->import($a, $customer);
            BSession::i()->addMessage('Thank you for your registration', 'success', 'frontend');
            BResponse::i()->redirect(BApp::href('customer/register'));
        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
            BResponse::i()->redirect(BApp::href('customer/register'));
        }
    }
}