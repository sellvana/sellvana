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
        BResponse::i()->redirect(BApp::m('FCom_Admin')->baseHref());
    }

    public function action_logout()
    {
        FCom_Admin_Model_User::i()->logout();
        BResponse::i()->redirect(BApp::m('FCom_Admin')->baseHref());
    }
}