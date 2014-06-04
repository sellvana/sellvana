<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Admin_Controller_Auth extends FCom_Admin_Controller_Abstract
{
    public function authenticate($args = [])
    {
        return true;
    }

    public function action_login__POST()
    {
        try {
            $r = $this->BRequest->post('login');
            if (!empty($r['username']) && !empty($r['password'])) {
                $user = $this->FCom_Admin_Model_User->authenticate($r['username'], $r['password']);
                if ($user) {
                    $this->BSession->regenerateId();
                    $user->login();
                    if (!empty($r['remember_me'])) {
                        $days = $this->BConfig->get('cookie/remember_days');
                        $this->BResponse->cookie('remember_me', 1, ($days ? $days : 30) * 86400);
                    }
                } else {
                    $this->message('Invalid user name or password.', 'error');
                }
            } else {
                $this->message('Username and password cannot be blank.', 'error');
            }
            $url = $this->BSession->get('admin_login_orig_url');
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->message($e->getMessage(), 'error');
        }
        $this->BResponse->redirect(!empty($url) ? $url : $this->BApp->href());
    }

    public function action_password_recover()
    {
        $this->layout('/password/recover');
    }

    public function action_password_recover__POST()
    {
        $form = $this->BRequest->request('model');
        if (empty($form) || empty($form['email'])) {
            $this->message('Invalid or empty email', 'error');
            $this->BResponse->redirect($this->BRequest->referrer());
            return;
        }
        $notLocked = $this->BLoginThrottle->init('admin:password_recover', $this->BRequest->ip());
        if ($notLocked) {
            $hlp = $this->FCom_Admin_Model_User;
            $user = $hlp->orm()->where(['OR' => ['email' => $form['email'], 'username' => $form['email']]])->find_one();
            if ($user) {
                $this->BLoginThrottle->success();
                $user->recoverPassword();
                sleep(1); // equalize time for success and failure
            } else {
                if ($this->BDebug->is('DEBUG') && !$hlp->orm()->find_one()) {
                    $hlp->create(['username' => 'admin', 'email' => $form['email'], 'is_superadmin' => 1])
                        ->save()->recoverPassword();
                } else {
                    $this->BLoginThrottle->failure(1);
                }
            }
        } else {
            sleep(1); // equalize time for success and failure
        }
        $this->message('If the email address was correct, you should receive an email shortly with password recovery instructions.');
        $this->BResponse->redirect('');
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
        if ($token && ($user = $this->FCom_Admin_Model_User->load($token, 'token'))
            && ($user->get('token') === $token)
        ) {
            $this->layout('/password/reset');
        } else {
            $this->message('Invalid link. It is possible your recovery link has expired.', 'error');
            $this->BResponse->redirect('');
        }
    }

    public function action_password_reset__POST()
    {
        if ($this->FCom_Admin_Model_User->isLoggedIn()) {
            $this->BResponse->redirect('');
            return;
        }
        $r = $this->BRequest;
        $token = $this->BSession->get('password_reset_token');
        $form = $r->post('model');

        $password = !empty($form['password']) ? $form['password'] : null;
        $confirm = !empty($form['password_confirm']) ? $form['password_confirm'] : null;
        if (!($password && $confirm && ($password === $confirm))) {
            $this->message('Invalid password or confirmation', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }

        $returnUrl = $this->BRequest->referrer();
        $user = $this->FCom_Admin_Model_User->validateResetToken($token);
        if (!$user) {
            $this->message('Invalid token', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }
        $sessData =& $this->BSession->dataToUpdate();
        $sessData['password_reset_token'] = null;

        $user->resetPassword($password);
        $this->BSession->regenerateId();

        $this->message('Password has been reset');
        $this->BResponse->redirect('');
    }

    public function action_logout()
    {
        $reqCsrfToken = $this->BRequest->get('X-CSRF-TOKEN');
        if (!$this->BSession->validateCsrfToken($reqCsrfToken)) {
            $this->BResponse->redirect('');
            return;
        }
        $this->FCom_Admin_Model_User->logout();
        $this->BResponse->cookie('remember_me', 0);
        $this->BResponse->redirect('');
    }
}
