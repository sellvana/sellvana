<?php

/**
 * Class FCom_AdminSPA_AdminSPA_Controller_Account
 *
 * @property FCom_Admin_Model_UserG2FA FCom_Admin_Model_UserG2FA
 */
class FCom_AdminSPA_AdminSPA_Controller_Account extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function authenticate($args = [])
    {
        if (in_array($this->_action, ['login', 'logout', 'password_recover', 'password_reset'], true)) {
            return true;
        }
        return parent::authenticate($args);
    }

    public function action_login()
    {
        $result = [
            'is_logged_in' => $this->FCom_Admin_Model_User->isLoggedIn(),
        ];
        $this->respond($result);
    }

    public function action_login__POST()
    {
        try {
            $r = $this->BRequest->post('login');
            if (empty($r['username']) || empty($r['password'])) {
                throw new BException('Empty username or password');
            }

            $user = $this->FCom_Admin_Model_User->authenticate($r['username'], $r['password']);
            if (!$user) {
                throw new BException($this->_('Invalid user name or password.'));
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

            $this->addResponses(['debug' => $_SESSION]);

            $this->ok()->addResponses(['_user', '_permissions', '_personalize', '_local_notifications', '_csrf_token',
                '_redirect' => '/',
            ]);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();
    }

    public function action_logout__POST()
    {
        try {
            $user = $this->FCom_Admin_Model_User->sessionUser();
            if ($user) {
                $user->logout();
            }
            $this->ok()->addResponses(['_user', '_permissions', '_personalize', '_local_notifications', '_csrf_token',
                '_redirect' => '/login',
            ]);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();
    }
}