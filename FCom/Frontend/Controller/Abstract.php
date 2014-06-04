<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Frontend_Controller_Abstract extends FCom_Core_Controller_Abstract
{
    protected static $_postSanitized = false;

    public function action_unauthenticated()
    {
        $r = $this->BRequest;

        $redirect = $r->get('redirect_to');
        if (!$r->isUrlLocal($redirect)) {
            $redirect = '';
        }
        if ($redirect === 'CURRENT') {
            $redirect = $this->BRequest->referrer();
        }

        if ($r->xhr()) {
            $this->BSession->set('login_orig_url', $redirect ? $redirect : $r->referrer());
            $this->BResponse->json(['error' => 'login']);
        } else {
            $this->BSession->set('login_orig_url', $redirect ? $redirect : $r->currentUrl());
            $this->layout('/customer/login');
            $this->BResponse->status(401, 'Unauthorized'); // HTTP sic
        }
    }

    public function action_unauthorized()
    {
        $r = $this->BRequest;

        $redirect = $r->get('redirect_to');
        if (!$r->isUrlLocal($redirect)) {
            $redirect = '';
        }
        if ($redirect === 'CURRENT') {
            $redirect = $this->BRequest->referrer();
        }

        if ($r->xhr()) {
            $this->BSession->set('login_orig_url', $redirect ? $redirect : $r->referrer());
            $this->BResponse->json(['error' => 'denied']);
        } else {
            $this->BSession->set('login_orig_url', $redirect ? $redirect : $r->currentUrl());
            $this->layout('/denied');
            $this->BResponse->status(403, 'Forbidden');
        }
    }

    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->view('head')->setTitle($this->BConfig->get('modules/FCom_Core/site_title'));

        return true;
    }

    public function message($msg, $type = 'success', $tag = 'frontend', $options = [])
    {
        if (is_array($msg)) {
            array_walk($msg, [$this->BLocale, '_']);
        } else {
            $msg = $this->BLocale->_($msg);
        }
        $this->BSession->addMessage($msg, $type, $tag, $options);
        return $this;
    }

    /**
     * convert validate error messages to frontend messages to show
     */
    public function formMessages($formId = 'frontend')
    {
        //prepare error message
        $messages = $this->BSession->messages('validator-errors:' . $formId);
        if (count($messages)) {
            $msg = [];
            foreach ($messages as $m) {
                $msg[] = is_array($m['msg']) ? $m['msg']['error'] : $m['msg'];
            }
            $this->message($msg, 'error');
        }
    }
}
