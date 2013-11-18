<?php


class FCom_Frontend_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    public function action_unauthenticated()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            BSession::i()->data('login_orig_url', $r->referrer());
            BResponse::i()->json(array('error'=>'login'));
        } else {
            BSession::i()->data('login_orig_url', $r->currentUrl());
            $this->layout('/customer/login');
            BResponse::i()->status(401, 'Unauthorized'); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            BSession::i()->data('login_orig_url', $r->referrer());
            BResponse::i()->json(array('error'=>'denied'));
        } else {
            BSession::i()->data('login_orig_url', $r->currentUrl());
            $this->layout('/denied');
            BResponse::i()->status(403, 'Forbidden');
        }
    }

    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->view('head')->setTitle(BConfig::i()->get('modules/FCom_Core/site_title'));

        return true;
    }
}
