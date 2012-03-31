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
        exit;
    }

    public function action_login_post()
    {
        $r = BRequest::i()->post('login');
        if (!empty($r['username']) && !empty($r['password'])) {
            $user = FCom_Admin_Model_User::i()->authenticate($r['username'], $r['password']);
            if ($user) {
                $user->login();
            } else {
                BSession::i()->addMessage('Invalid user name or password.', 'error', 'admin');
            }
        }
        $url = BSession::i()->data('login_orig_url');
        BResponse::i()->redirect($url ? $url : BApp::baseUrl());
    }

    public function action_logout()
    {
        FCom_Admin_Model_User::i()->logout();
        BResponse::i()->redirect(BApp::baseUrl());
    }

    public function action_my_account()
    {
        BLayout::i()->view('my_account')->model = FCom_Admin_Model_User::i()->sessionUser();
        $this->layout('/my_account');
    }

    public function action_personalize()
    {
        $r = BRequest::i()->request();
        $data = array();
        switch ($r['do']) {
        case 'grid.col.width':
            $columns = array($r['col']=>array('width'=>$r['width']));
            $data = array('grid'=>array($r['grid']=>array('columns'=>$columns)));
            break;

        case 'grid.col.order':
            $cols = BUtil::fromJson($r['cols']);
            $columns = array();
            foreach ($cols as $i=>$col) {
                if ($col['name']==='cb') continue;
                $columns[$col['name']] = array('position'=>$i, 'hidden'=>!empty($col['hidden']));
            }
            $data = array('grid'=>array($r['grid']=>array('columns'=>$columns)));
            break;
        }
        FCom_Admin_Model_User::i()->personalize($data);
        BResponse::i()->json(array('success'=>true));
    }
}