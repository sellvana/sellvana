<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Frontend_Controller_Abstract
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */
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

    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->view('head')->setTitle($this->BConfig->get('modules/FCom_Core/site_title'));

        return true;
    }

    /**
     * @param $msg
     * @param string $type
     * @param string $tag
     * @param array $options
     * @return $this
     */
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
     * @param string $formId
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
