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
            $r = BRequest::i()->post('login');
            if (!empty($r['username']) && !empty($r['password'])) {
                $user = FCom_Admin_Model_User::i()->authenticate($r['username'], $r['password']);
                if ($user) {
                    BSession::i()->regenerateId();
                    $user->login();
                    if (!empty($r['remember_me'])) {
                        $days = BConfig::i()->get('cookie/remember_days');
                        BResponse::i()->cookie('remember_me', 1, ($days ? $days : 30) * 86400);
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
        $notLocked = BLoginThrottle::i()->init('admin:password_recover', BRequest::i()->ip());
        if ($notLocked) {
            $hlp = FCom_Admin_Model_User::i();
            $user = $hlp->orm()->where(['OR' => ['email' => $form['email'], 'username' => $form['email']]])->find_one();
            if ($user) {
                BLoginThrottle::i()->success();
                $user->recoverPassword();
                sleep(1); // equalize time for success and failure
            } else {
                if (BDebug::is('DEBUG') && !$hlp->orm()->find_one()) {
                    $hlp->create(['username' => 'admin', 'email' => $form['email'], 'is_superadmin' => 1])
                        ->save()->recoverPassword();
                } else {
                    BLoginThrottle::i()->failure(1);
                }
            }
        } else {
            sleep(1); // equalize time for success and failure
        }
        $this->message('If the email address was correct, you should receive an email shortly with password recovery instructions.');
        BResponse::i()->redirect('');
    }

    public function action_password_reset()
    {
        $token = BRequest::i()->request('token');
        if ($token) {
            $sessData =& BSession::i()->dataToUpdate();
            $sessData['password_reset_token'] = $token;
            BResponse::i()->redirect('customer/password/reset');
            return;
        }
        $token = BSession::i()->get('password_reset_token');
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
        if (FCom_Admin_Model_User::i()->isLoggedIn()) {
            BResponse::i()->redirect('');
            return;
        }
        $r = BRequest::i();
        $token = BSession::i()->get('password_reset_token');
        $form = $r->post('model');

        $password = !empty($form['password']) ? $form['password'] : null;
        $confirm = !empty($form['password_confirm']) ? $form['password_confirm'] : null;
        if (!($password && $confirm && ($password === $confirm))) {
            $this->message('Invalid password or confirmation', 'error');
            BResponse::i()->redirect($returnUrl);
            return;
        }

        $returnUrl = BRequest::i()->referrer();
        $user = FCom_Admin_Model_User::i()->validateResetToken($token);
        if (!$user) {
            $this->message('Invalid token', 'error');
            BResponse::i()->redirect($returnUrl);
            return;
        }
        $sessData =& BSession::i()->dataToUpdate();
        $sessData['password_reset_token'] = null;

        $user->resetPassword($password);
        BSession::i()->regenerateId();

        $this->message('Password has been reset');
        BResponse::i()->redirect('');
    }

    public function action_logout()
    {
        $reqCsrfToken = BRequest::i()->get('X-CSRF-TOKEN');
        if (!BSession::i()->validateCsrfToken($reqCsrfToken)) {
            BResponse::i()->redirect('');
            return;
        }
        FCom_Admin_Model_User::i()->logout();
        BResponse::i()->cookie('remember_me', 0);
        BResponse::i()->redirect('');
    }
}
