<?php


class FCom_Frontend_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    protected static $_postSanitized = false;

    public function action_unauthenticated()
    {
        $r = BRequest::i();

        $redirect = $r->get('redirect_to');
        if ($redirect === 'CURRENT') {
            $redirect = BRequest::i()->referrer();
        }

        if ($r->xhr()) {
            BSession::i()->set('login_orig_url', $redirect ? $redirect : $r->referrer());
            BResponse::i()->json(['error' => 'login']);
        } else {
            BSession::i()->set('login_orig_url', $redirect ? $redirect : $r->currentUrl());
            $this->layout('/customer/login');
            BResponse::i()->status(401, 'Unauthorized'); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = BRequest::i();

        $redirect = $r->get('redirect_to');
        if ($redirect === 'CURRENT') {
            $redirect = BRequest::i()->referrer();
        }

        if ($r->xhr()) {
            BSession::i()->set('login_orig_url', $redirect ? $redirect : $r->referrer());
            BResponse::i()->json(['error' => 'denied']);
        } else {
            BSession::i()->set('login_orig_url', $redirect ? $redirect : $r->currentUrl());
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

    public function message($msg, $type = 'success', $tag = 'frontend', $options = [])
    {
        if (is_array($msg)) {
            array_walk($msg, 'BLocale::_');
        } else {
            $msg = BLocale::_($msg);
        }
        BSession::i()->addMessage($msg, $type, $tag, $options);
        return $this;
    }

    /**
     * convert validate error messages to frontend messages to show
     */
    public function formMessages($formId = 'frontend')
    {
        //prepare error message
        $messages = BSession::i()->messages('validator-errors:' . $formId);
        if (count($messages)) {
            $msg = [];
            foreach ($messages as $m) {
                $msg[] = is_array($m['msg']) ? $m['msg']['error'] : $m['msg'];
            }
            $this->message($msg, 'error');
        }
    }
}
