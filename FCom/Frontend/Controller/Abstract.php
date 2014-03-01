<?php


class FCom_Frontend_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    protected static $_postSanitized = false;
    
    public function action_unauthenticated()
    {
        $r = BRequest::i();

        $redirect = $r->get('redirect_to');
        if ($redirect==='CURRENT') {
            $redirect = BRequest::i()->referrer();
        }

        if ($r->xhr()) {
            BSession::i()->data('login_orig_url', $redirect ? $redirect : $r->referrer());
            BResponse::i()->json(array('error'=>'login'));
        } else {
            BSession::i()->data('login_orig_url', $redirect ? $redirect : $r->currentUrl());
            $this->layout('/customer/login');
            BResponse::i()->status(401, 'Unauthorized'); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = BRequest::i();

        $redirect = $r->get('redirect_to');
        if ($redirect==='CURRENT') {
            $redirect = BRequest::i()->referrer();
        }

        if ($r->xhr()) {
            BSession::i()->data('login_orig_url', $redirect ? $redirect : $r->referrer());
            BResponse::i()->json(array('error'=>'denied'));
        } else {
            BSession::i()->data('login_orig_url', $redirect ? $redirect : $r->currentUrl());
            $this->layout('/denied');
            BResponse::i()->status(403, 'Forbidden');
        }
    }
    
    public function sanitizeRecursive(&$data)
    {
        foreach ($data as $k => &$v) {
            if (is_array($v)) {
                $this->sanitizeRecursive($v);
            } elseif (!is_numeric($v)) {
                $v = strip_tags($v);
            }
        }
        unset($v);
    }

    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        if (!static::$_postSanitized && !empty($_POST)) {
            $this->sanitizeRecursive($_POST);
            static::$_postSanitized = true;
        }
        
        $this->view('head')->setTitle(BConfig::i()->get('modules/FCom_Core/site_title'));

        return true;
    }

    public function message($msg, $type='success', $tag='frontend', $options=array())
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
        $messages = BSession::i()->messages('validator-errors:'.$formId);
        if (count($messages)) {
            $msg = array();
            foreach ($messages as $m) {
                $msg[] = is_array($m['msg']) ? $m['msg']['error'] : $m['msg'];
            }
            $this->message($msg, 'error');
        }
    }
}
