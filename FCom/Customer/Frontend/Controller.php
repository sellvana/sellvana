<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Customer_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) {
            return false;
        }
        if ($this->FCom_Customer_Model_Customer->isLoggedIn() && in_array($this->_action, ['login', 'register', 'password_recover'])) {
            $this->BResponse->redirect('');
        }
        return true;
    }

    public function action_login()
    {
        $this->layout('/customer/login');

        $redirect = $this->BRequest->get('redirect_to');
        if (!$this->BRequest->isUrlLocal($redirect)) {
            $redirect = '';
        }
        if ($redirect === 'CURRENT') {
            $redirect = $this->BRequest->referrer();
        }
        if ($redirect) {
            $this->BSession->set('login_orig_url', $redirect);
        }
    }

    public function action_login__POST()
    {
        try {
            $r = $this->BRequest;
            $customerModel = $this->FCom_Customer_Model_Customer;
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
                        $this->BSession->regenerateId();
                        $user->login();
                        if (!empty($login['remember_me'])) {
                            $days = $this->BConfig->get('cookie/remember_days');
                            $this->BResponse->cookie('remember_me', 1, ($days ? $days : 30) * 86400);
                        }
                    } else {
                        $this->message($errorMessage, 'error', 'frontend', ['title' => '']);
                        $this->BResponse->redirect('login');
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
                $url = $this->BSession->get('login_orig_url');
            }
            $this->BResponse->redirect(!empty($url) ? $url : '');
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect('login');
        }
    }

    public function action_password_recover()
    {
        $this->layout('/customer/password/recover');
    }

    public function action_password_recover__POST()
    {
        try {
            $email = $this->BRequest->request('email');
            $customerModel = $this->FCom_Customer_Model_Customer;
            $customerModel->setPasswordRecoverRules();
            if ($customerModel->validate(['email' => $email], [], 'frontend')) {
                $user = $customerModel->load($email, 'email');
                if ($user) {
                    $user->recoverPassword();
                }
                $this->message('If the email address was correct, you should receive an email shortly with password recovery instructions.');
                $this->BResponse->redirect('login');
            } else {
                $this->formMessages();
                $this->BResponse->redirect('/customer/password/recover');
            }
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect('customer/password/recover');
        }
    }

    public function action_password_reset()
    {
        $token = $this->BRequest->request('token');
        if ($token) {
            $sessData =& $this->BSession->dataToUpdate();
            $sessData['password_reset_token'] = $token;
            $this->BResponse->redirect('customer/password/reset');
            return;
        }
        $token = $this->BSession->get('password_reset_token');
        if ($token && ($user = $this->FCom_Customer_Model_Customer->load($token, 'token')) && $user->token === $token) {
            $this->layout('/customer/password/reset');
        } else {
            $this->message('Invalid link. It is possible your recovery link has expired.', 'error');
            $this->BResponse->redirect('login');
        }
    }

    public function action_password_reset__POST()
    {
        if ($this->FCom_Customer_Model_Customer->isLoggedIn()) {
            $this->BResponse->redirect('');
            return;
        }
        $r = $this->BRequest;
        $token = $this->BSession->get('password_reset_token');
        $password = $r->post('password');
        $confirm = $r->post('password_confirm');
        $returnUrl = 'login';
        if (!($password && $confirm && $password === $confirm)) {
            $this->message('Invalid password or confirmation', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }

        $user = $this->FCom_Customer_Model_Customer->validateResetToken($token);
        if (!$user) {
            $this->message('Invalid token', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }
        $sessData =& $this->BSession->dataToUpdate();
        $sessData['password_reset_token'] = null;

        $user->resetPassword($password);
        $this->BSession->regenerateId();

        $this->message('Password has been reset.');
        if ($user->status === 'review') {
            $this->message('You will be able to login after your account is approved', 'warning');
        }
        $this->BResponse->redirect($returnUrl);
    }

    public function action_logout()
    {
        if ($this->BRequest->csrf('referrer', 'GET')) {
            $this->BResponse->redirect('');
            return;
        }
        $this->FCom_Customer_Model_Customer->logout();
        $this->BResponse->cookie('remember_me', 0);
        $this->BResponse->redirect($this->BApp->baseUrl());
    }

    public function action_register()
    {
        $this->view('customer/register')->set('formId', 'register-form');
        $this->layout('/customer/register');
    }

    public function action_register__POST()
    {
        try {
            $r = $this->BRequest->post('model');
            $a = $this->BRequest->post('address');
            $customerModel = $this->FCom_Customer_Model_Customer;
            $formId = 'register-form';
            $emailUniqueRules = [['email', 'FCom_Customer_Model_Customer::ruleEmailUnique', 'An account with this email address already exists']];
            if ($customerModel->validate($r, $emailUniqueRules, $formId)) {
                $customer = $customerModel->register($r);
                if ($a) {
                    $this->FCom_Customer_Model_Address->import($a, $customer);
                }
//                $customer->login();
                if ($customer->status === 'review') {
                    $this->message('Thank you for your access request. We will be in touch shortly via email');
                    $this->BResponse->redirect('customer/register');
                } else {
                    $this->message('Thank you for your registration');
                    $this->BResponse->redirect('customer/myaccount');
                }
            } else {
                $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $formId);
                $this->formMessages($formId);
                $this->BResponse->redirect('customer/register');
            }
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect('customer/register');
        }
    }
}
