<?php

/**
 * Class FCom_Admin_Controller_Google2FA
 *
 * @property FCom_LibGoogle2FA_Main $FCom_LibGoogle2FA_Main
 * @property FCom_Admin_Model_UserG2FA $FCom_Admin_Model_UserG2FA
 */
class FCom_Admin_Controller_Google2FA extends FCom_Admin_Controller_Abstract
{
    public function authenticate($args = [])
    {
        if (in_array($this->_action, ['login', 'recover', 'reset'])) {
            return true;
        }
        return parent::authenticate($args);
    }

    public function action_login()
    {
        if ($this->FCom_Admin_Model_User->isLoggedIn() || !$this->BSession->get('g2fa_user_id')) {
            $this->BResponse->redirect('');
            return;
        }
        $this->layout('/g2fa/login');
    }

    public function action_login__POST()
    {
        $post = $this->BRequest->post('g2fa');
        if (empty($post['code'])) {
            $this->message('Empty verification code', 'error');
            $this->BResponse->redirect('g2fa/login');
            return;
        }

        $userId = $this->BSession->get('g2fa_user_id');
        if ($this->FCom_Admin_Model_User->isLoggedIn() || !$userId) {
            $this->BResponse->redirect('');
            return;
        }

        $user = $this->FCom_Admin_Model_User->load($userId);
        if ($user->get('g2fa_status') != 9) {
            $this->message('Invalid user g2fa status', 'error');
            $this->BResponse->redirect('g2fa/login');
            return;
        }

        $notLocked = $this->BLoginThrottle->init('admin:g2fa_login', $userId);

        $check = false;
        if ($notLocked) {
            $check = $this->FCom_LibGoogle2FA_Main->verifyCode($user->get('g2fa_secret'), $post['code'], 2);
            if ($check) {
                $this->BLoginThrottle->success();
            }
        }
        if (!$check) {
            $this->BLoginThrottle->failure(1);
            $this->message('Invalid verification code', 'error');
            $this->BResponse->redirect('g2fa/login');
            return;
        }
        
        if (!empty($post['remember_me'])) {
            $rec = $this->FCom_Admin_Model_UserG2FA->createToken($user->id());
            $this->BResponse->cookie('g2fa_token', $rec->get('token'), 30 * 86400);
        }

        $user->login();

        $this->BSession->set('g2fa_user_id', false);

        $url = $this->BSession->get('admin_login_orig_url');
        $this->BResponse->redirect(!empty($url) ? $url : $this->BApp->href());
    }

    public function action_recover()
    {
        $this->layout('/g2fa/recover');
    }

    public function action_recover__POST()
    {
        if ($this->BConfig->get('modules/FCom_Admin/recaptcha_g2fa_recover')
            && !$this->FCom_LibRecaptcha_Main->check()
        ) {
            $this->message('Invalid or missing reCaptcha response', 'error');
            $this->BResponse->redirect('g2fa/recover');
            return;
        }

        $form = $this->BRequest->request('model');
        if (empty($form) || empty($form['email'])) {
            $this->message('Invalid or empty email', 'error');
            $this->BResponse->redirect($this->BRequest->referrer());
            return;
        }
        $notLocked = $this->BLoginThrottle->init('admin:g2fa_recover', $this->BRequest->ip());
        if ($notLocked) {
            $hlp = $this->FCom_Admin_Model_User;
            /** @var FCom_Admin_Model_User $user */
            $user = $hlp->orm()->where(['OR' => [
                'email' => (string)$form['email'],
                'username' => (string)$form['email'],
            ]])->find_one();
            if ($user) {
                $this->BLoginThrottle->success();
                $user->recoverG2FA();
                sleep(1); // equalize time for success and failure
            } else {
                $this->BLoginThrottle->failure(1);
            }
        } else {
            sleep(1); // equalize time for success and failure
        }
        $this->message('If the email address was correct, you should receive an email shortly with password recovery instructions.');
        $this->BResponse->redirect('');
    }

    public function action_reset()
    {
        $token = $this->BRequest->request('token');
        if ($token) {
            $this->BSession->set('g2fa_reset_token', $token);
            $this->BResponse->redirect('g2fa/reset');
            return;
        }
        $token = $this->BSession->get('g2fa_reset_token');
        if ($token && ($user = $this->FCom_Admin_Model_User->load($token, 'g2fa_token'))
            && ($user->get('g2fa_token') === $token)
        ) {
            $this->layout('/g2fa/reset');
        } else {
            $this->message('Invalid link. It is possible your recovery link has expired.', 'error');
            $this->BResponse->redirect('');
        }
    }

    public function action_reset__POST()
    {
        if ($this->FCom_Admin_Model_User->isLoggedIn()) {
            $this->BResponse->redirect('');
            return;
        }
        $r = $this->BRequest;
        $token = $this->BSession->get('g2fa_reset_token');
        $form = $r->post('model');
        $returnUrl = $this->BRequest->referrer();

        $password = !empty($form['password']) ? $form['password'] : null;
        if (!$password) {
            $this->message('Invalid or empty password', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }

        $user = $this->FCom_Admin_Model_User->validateResetG2FAToken($token);
        if (!$user) {
            $this->message('Invalid token', 'error');
            $this->BResponse->redirect($returnUrl);
            return;
        }
        $this->BSession->set('g2fa_reset_token', null);

        $user->resetG2FA();

        $this->message('2FA has been reset');
        $this->BResponse->redirect('');
    }

    
    public function action_enable__POST()
    {
        $user = $this->FCom_Admin_Model_User->sessionUser();
        $regenerate = $this->BRequest->request('regenerate');
        $hlp = $this->FCom_LibGoogle2FA_Main;
        $secret = $user->get('g2fa_secret');
        if (!$user->get('g2fa_secret') || $regenerate) {
            $secret = $hlp->createSecret();
            $user->set(['g2fa_secret' => $secret, 'g2fa_status' => 5])->save();
        }
        $result = [
            'qrcode_url' => $hlp->getQRCodeGoogleUrl('Sellvana-Admin-User', $secret),
        ];
        $this->BResponse->json($result);
    }
    
    public function action_verify__POST()
    {
        $user = $this->FCom_Admin_Model_User->sessionUser();
        $hlp = $this->FCom_LibGoogle2FA_Main;
        $secret = $user->get('g2fa_secret');
        $formCode = $this->BRequest->request('code');
        $check = $hlp->verifyCode($secret, $formCode, 2);
        if ($check) {
            $result = ['success' => 1];
            $user->set('g2fa_status', 9)->save();
        } else {
            $result = ['error' => 1, 'message' => $this->_(('Invalid code, please try again'))];
        }
        $this->BResponse->json($result);
    }
    
    public function action_disable__POST()
    {
        $user = $this->FCom_Admin_Model_User->sessionUser();
        $user->set(['g2fa_secret' => null, 'g2fa_status' => 0])->save();
        $result = ['success' => 1];
        $this->BResponse->json($result);
    }
}