<?php

class FCom_Admin_Controller_Auth extends FCom_Admin_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return true;
    }

    public function action_login__POST()
    {
        try {
            $r = BRequest::i()->post('login');
            if (!empty($r['username']) && !empty($r['password'])) {
                $user = FCom_Admin_Model_User::i()->authenticate($r['username'], $r['password']);
                if ($user) {
                    $user->login();
                    if (!empty($r['remember_me'])) {
                        $days = BConfig::i()->get('cookie/remember_days');
                        BResponse::i()->cookie('remember_me', 1, ($days ? $days : 30)*86400);
                    }
                } else {
                    $this->message('Invalid user name or password.', 'error');
                }
            } else {
                $this->message('Username and password cannot be blank.', 'error');
            }
            $url = BSession::i()->get('admin_login_orig_url');
        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
        }
        BResponse::i()->redirect(!empty($url) ? $url : BApp::href());
    }

    public function action_password_recover()
    {
        $this->layout('/password/recover');
    }

    public function action_password_recover__POST()
    {
        $form = BRequest::i()->request('model');
        if (empty($form) || empty($form['email'])) {
            $this->message('Invalid or empty email', 'error');
            BResponse::i()->redirect(BRequest::i()->referrer());
            return;
        }
        $user = FCom_Admin_Model_User::i()->orm()
            ->where(array('OR' => array(
                'email' => $form['email'],
                'username' => $form['email'],
            )))
            ->find_one();
        if ($user) {
            $user->recoverPassword();
        }
        $this->message('If the email address was correct, you should receive an email shortly with password recovery instructions.');
        BResponse::i()->redirect('');
    }

    public function action_password_reset()
    {
        $token = BRequest::i()->request('token');
        if ($token && ($user = FCom_Admin_Model_User::i()->load($token, 'token'))
            && ($user->get('token') === $token)
        ) {
            $this->layout('/password/reset');
        } else {
            $this->message('Invalid link. It is possible your recovery link has expired.', 'error');
            BResponse::i()->redirect('');
        }
    }

    public function action_password_reset__POST()
    {
        $r = BRequest::i();
        $token = $r->request('token');
        $form = $r->post('model');
        $password = !empty($form['password']) ? $form['password'] : null;
        $confirm = !empty($form['password_confirm']) ? $form['password_confirm'] : null;
        $returnUrl = BRequest::i()->referrer();
        if (!($token && ($user = FCom_Admin_Model_User::i()->load($token, 'token')) && $user->get('token') === $token)) {
            $this->message('Invalid token', 'error');
            BResponse::i()->redirect($returnUrl);
            return;
        } elseif (!($password && $confirm && $password === $confirm)) {
            $this->message('Invalid password or confirmation', 'error');
            BResponse::i()->redirect($returnUrl);
            return;
        }
        $user->resetPassword($password);
        $this->message('Password has been reset');
        BResponse::i()->redirect('');
    }

    public function action_logout()
    {
        FCom_Admin_Model_User::i()->logout();
        BResponse::i()->redirect('');
    }
}
