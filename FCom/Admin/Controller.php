<?php

class FCom_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    public function action_index()
    {
        $this->layout('/');
        //BLayout::i()->layout('/');
    }

    public function action_blank()
    {
    }

    public function action_login_post()
    {
        $r = BRequest::i()->post('login');
        if (!empty($r['username']) && !empty($r['password'])) {
            $result = FCom_Admin_Model_User::i()->login($r['username'], $r['password']);
            if (!$result) {
                BSession::i()->addMessage('Invalid user name or password.', 'error', 'admin');
            }
        }
        $url = BSession::i()->data('login_orig_url');
        BResponse::i()->redirect($url ? $url : BApp::url('FCom_Admin'));
    }

    public function action_logout()
    {
        FCom_Admin_Model_User::i()->logout();
        BResponse::i()->redirect(BApp::url('FCom_Admin'));
    }
}