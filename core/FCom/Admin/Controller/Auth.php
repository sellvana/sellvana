<?php

/**
 * Class FCom_Admin_Controller_Auth
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Admin_Model_UserG2FA $FCom_Admin_Model_UserG2FA
 * @property FCom_LibRecaptcha_Main $FCom_LibRecaptcha_Main
 */

class FCom_Admin_Controller_Auth extends FCom_Admin_Controller_Abstract
{
    public function authenticate($args = [])
    {
        return true;
    }

    public function action_login()
    {
        $this->BResponse->redirect('');
    }

    public function action_login__POST()
    {
        try {
            if ($this->BConfig->get('modules/FCom_Admin/recaptcha_login')
                && !$this->FCom_LibRecaptcha_Main->check()
            ) {
                $this->message('Invalid or missing reCaptcha response', 'error');
                $this->BResponse->redirect('login');
                return;
            }

            $r = $this->BRequest->post('login');
            if (empty($r['username']) || empty($r['password'])) {
                throw new Exception($this->_(('Username and password cannot be blank.')));
            }

            $user = $this->FCom_Admin_Model_User->authenticate($r['username'], $r['password']);
            if (!$user) {
                throw new Exception($this->_(('Invalid user name or password.')));
            }

            if (!empty($r['remember_me'])) {
                $days = $this->BConfig->get('cookie/remember_days');
                $this->BResponse->cookie('remember_me', 1, ($days ? $days : 30) * 86400);
            }

            if ($user->get('g2fa_status') == 9) {
                $token = $this->BRequest->cookie('g2fa_token');
                $rec = $this->FCom_Admin_Model_UserG2FA->verifyToken($user->id(), $token);
                if (!$rec) {
                    $this->BSession->set('g2fa_user_id', $user->id());
                    $this->BResponse->redirect('g2fa/login');
                    return;
                }
            }

            $user->login();

            $url = $this->BSession->get('admin_login_orig_url');
            $result = 'success';
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            if (!$this->BRequest->xhr()) {
                $this->message($e->getMessage(), 'error');
            }
            $result = 'error';
        }

        if ($this->BRequest->xhr()) {
            $this->BResponse->json(['result' => $result]);
            return;
        }

        $this->BResponse->redirect(!empty($url) ? $url : $this->BApp->href());
    }

    public function action_password_recover()
    {
        $this->layout('/password/recover');
    }

    public function action_password_recover__POST()
    {
        if ($this->BConfig->get('modules/FCom_Admin/recaptcha_password_recover')
            && !$this->FCom_LibRecaptcha_Main->check()
        ) {
            $this->message('Invalid or missing reCaptcha response', 'error');
            $this->BResponse->redirect('password/recover');
            return;
        }

        $form = $this->BRequest->request('model');
        if (empty($form) || empty($form['email'])) {
            $this->message('Invalid or empty email', 'error');
            $this->BResponse->redirect($this->BRequest->referrer());
            return;
        }
        $notLocked = $this->BLoginThrottle->init('admin:password_recover', $this->BRequest->ip());
        if ($notLocked) {
            $hlp = $this->FCom_Admin_Model_User;
            $user = $hlp->orm()->where(['OR' => [
                'email' => (string)$form['email'],
                'username' => (string)$form['email'],
            ]])->find_one();
            if ($user) {
                $this->BLoginThrottle->success();
                $user->recoverPassword();
                sleep(1); // equalize time for success and failure
            } else {
//                if ($this->BDebug->is('DEBUG') && !$hlp->orm()->find_one()) {
//                    $hlp->create(['username' => 'admin', 'email' => $form['email'], 'is_superadmin' => 1])
//                        ->save()->recoverPassword();
//                } else {
                    $this->BLoginThrottle->failure(1);
//                }
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
            $this->BSession->set('password_reset_token', $token);
            $this->BResponse->redirect('password/reset');
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
        $returnUrl = $this->BRequest->referrer();

        $password = !empty($form['password']) ? $form['password'] : null;
        $confirm = !empty($form['password_confirm']) ? $form['password_confirm'] : null;
        if (!($password && $confirm && ($password === $confirm))) {
            $this->message('Invalid password or confirmation', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }

        $user = $this->FCom_Admin_Model_User->validateResetToken($token);
        if (!$user) {
            $this->message('Invalid token', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }
        $this->BSession->set('password_reset_token', null);

        $user->resetPassword($password);

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
