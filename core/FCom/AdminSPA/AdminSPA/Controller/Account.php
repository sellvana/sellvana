<?php

class FCom_AdminSPA_AdminSPA_Controller_Account extends FCom_AdminSPA_AdminSPA_Controller_Abstract
{
    public function authenticate($args = [])
    {
        if (in_array($this->_action, ['login', 'password_recover', 'password_reset'])) {
            return true;
        }
        return parent::authenticate($args);
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
            $user->login();
            $this->ok()->addResponses(true)->addResponses(['_redirect' => '/']);
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
            $this->ok()->addResponses(true);
        } catch (Exception $e) {
            $this->addMessage($e);
        }
        $this->respond();
    }
}