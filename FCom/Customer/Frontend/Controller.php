<?php

class FCom_Customer_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) {
            return false;
        }
        if (FCom_Customer_Model_Customer::i()->isLoggedIn() && in_array($this->_action, ['login', 'register', 'password_recover'])) {
            BResponse::i()->redirect('');
        }
        return true;
    }

    public function action_login()
    {
        $this->layout('/customer/login');

        $redirect = BRequest::i()->get('redirect_to');
        if (!BRequest::i()->isUrlLocal($redirect)) {
            $redirect = '';
        }
        if ($redirect === 'CURRENT') {
            $redirect = BRequest::i()->referrer();
        }
        if ($redirect) {
            BSession::i()->set('login_orig_url', $redirect);
        }
    }

    public function action_login__POST()
    {
        try {
            $r = BRequest::i();
            $customerModel = FCom_Customer_Model_Customer::i();
            $login = $r->post('login');
            if (!$login) {
                $login = $r->post();
            }
            $customerModel->setLoginRules();
            if ($customerModel->validate($login, [], 'frontend')) {
                $user = $customerModel->authenticate($login['email'], $login['password']);
                if ($user) {
                    switch ($user->status) {
                        case 'active':
                            $allowLogin = true;
                            $errorMessage = '';
                            break;
                        case 'review':
                            $allowLogin = false;
                            $errorMessage = $this->_('Your account is under review. Once approved, we\'ll notify you. Thank you for your patience.');
                            break;
                        case 'disabled':
                            $allowLogin = false;
                            $errorMessage = $this->_('Your account is disabled. Please contact us for more details.');
                            break;
                        default:
                            $allowLogin = false;
                            $errorMessage = $this->_('Your account status have problem. Please contact us for more details.');
                            break;
                    }
                    if ($allowLogin) {
                        $user->login();
                        if (!empty($login['remember_me'])) {
                            $days = BConfig::i()->get('cookie/remember_days');
                            BResponse::i()->cookie('remember_me', 1, ($days ? $days : 30) * 86400);
                        }
                    } else {
                        $this->message($errorMessage, 'error', 'frontend', ['title' => '']);
                        BResponse::i()->redirect('login');
                        return;
                    }
                } else {
                    throw new Exception($this->_('Invalid email or password.'));
                }
            } else {
                $this->formMessages();
            }
            $url = $r->request('redirect_to');
            if (!$r->isUrlLocal($url)) {
                $url = '';
            }
            if ($url) {
                if ($url === 'CURRENT') {
                    $url = $r->referrer();
                }
            } else {
                $url = BSession::i()->get('login_orig_url');
            }
            BResponse::i()->redirect(!empty($url) ? $url : '');
        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
            BResponse::i()->redirect('login');
        }
    }

    public function action_password_recover()
    {
        $this->layout('/customer/password/recover');
    }

    public function action_password_recover__POST()
    {
        try {
            $email = BRequest::i()->request('email');
            $customerModel = FCom_Customer_Model_Customer::i();
            $customerModel->setPasswordRecoverRules();
            if ($customerModel->validate(['email' => $email], [], 'frontend')) {
                $user = $customerModel->load($email, 'email');
                if ($user) {
                    $user->recoverPassword();
                }
                $this->message('If the email address was correct, you should receive an email shortly with password recovery instructions.');
                BResponse::i()->redirect('login');
            } else {
                $this->formMessages();
                BResponse::i()->redirect('/customer/password/recover');
            }
        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
            BResponse::i()->redirect('customer/password/recover');
        }
    }

    public function action_password_reset()
    {
        $token = BRequest::i()->request('token');
        if ($token) {
            $sessData =& BSession::i()->dataToUpdate();
            $sessData['password_reset_token'] = $token;
            BResponse::i()->redirect('customer/password/reset');
        }
        $token = BSession::i()->get('password_reset_token');
        if ($token && ($user = FCom_Customer_Model_Customer::i()->load($token, 'token')) && $user->token === $token) {
            $this->layout('/customer/password/reset');
        } else {
            $this->message('Invalid link. It is possible your recovery link has expired.', 'error');
            BResponse::i()->redirect('login');
        }
    }

    public function action_password_reset__POST()
    {
        $r = BRequest::i();
        $token = BSession::i()->get('password_reset_token');
        $password = $r->post('password');
        $confirm = $r->post('password_confirm');
        if ($token && $password && $password === $confirm
            && ($user = FCom_Customer_Model_Customer::i()->load($token, 'token'))
            && $user->get('token') === $token
        ) {
            $sessData =& BSession::i()->dataToUpdate();
            $sessData['password_reset_token'] = null;

            $user->resetPassword($password);
            $this->message('Password has been reset.');
            if ($user->status === 'review') {
                $this->message('You will be able to login after your account is approved', 'warning');
            }
            BResponse::i()->redirect('login');
        } else {
            $this->message('Invalid form data', 'error');
            BResponse::i()->redirect('login');
        }
    }

    public function action_logout()
    {
        FCom_Customer_Model_Customer::i()->logout();
        BResponse::i()->cookie('remember_me', 0);
        BResponse::i()->redirect(BApp::baseUrl());
    }

    public function action_register()
    {
        $this->view('customer/register')->set('formId', 'register-form');
        $this->layout('/customer/register');
    }

    public function action_register__POST()
    {
        try {
            $r = BRequest::i()->post('model');
            $a = BRequest::i()->post('address');
            $customerModel = FCom_Customer_Model_Customer::i();
            $formId = 'register-form';
            $emailUniqueRules = [['email', 'FCom_Customer_Model_Customer::ruleEmailUnique', 'An account with this email address already exists']];
            if ($customerModel->validate($r, $emailUniqueRules, $formId)) {
                $customer = $customerModel->register($r);
                if ($a) {
                    FCom_Customer_Model_Address::i()->import($a, $customer);
                }
//                $customer->login();
                $this->message('Thank you for your access request. We will be in touch shortly via email');
//                $this->message('Thank you for your registration');
//                BResponse::i()->redirect('customer/myaccount');
                BResponse::i()->redirect('customer/register');
            } else {
                $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $formId);
                $this->formMessages($formId);
                BResponse::i()->redirect('customer/register');
            }
        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
            BResponse::i()->redirect('customer/register');
        }
    }
}
