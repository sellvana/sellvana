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
        if ($this->_action === 'login') {
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

        $locked = $this->BLoginThrottle->init('G2FA-Admin', $userId);

        $check = false;
        if (!$locked) {
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
            $result = ['error' => 1, 'message' => $this->_('Invalid code, please try again')];
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