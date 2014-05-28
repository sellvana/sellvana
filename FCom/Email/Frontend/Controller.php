<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Email_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_pref()
    {
        $this->layout('/email/pref');
    }

    public function action_pref__POST()
    {
        $hlp = FCom_Email_Model_Pref::i();
        $r = BRequest::i();
        $email = $r->request('email');
        try {
            if (!$hlp->validateToken($email, $r->request('token'))) {
                throw new Exception('Invalid token');
            }
            $data = BUtil::arrayMask($r->post('model'), 'id,email,create_at,update_at', true);
            $pref = $hlp->load($email, 'email');
            if (!$pref) {
                $pref = $hlp->create(['email' => $email]);
            }
            if (empty($data['unsub_all'])) {
                $data['unsub_all'] = 0;
            }
            $pref->set($data)->save();
            $this->message('Your preferences have been saved');
        } catch (Exception $e) {
            $this->message($e->getMessage(), 'error');
        }
        $url = BUtil::setUrlQuery($r->currentUrl(), ['token' => $hlp->getToken($email)]);
        BResponse::i()->redirect($url);
    }

    public function action_subscribe()
    {
        $this->layout('/email/subscribe');
    }

    public function action_subscribe__POST()
    {
        $r    = BRequest::i();
        $post = $r->post();
        try {
            $model = FCom_Email_Model_Pref::i()->load($post['email'], 'email');
            /** @var $model FCom_Email_Model_Pref */
            if (!$model) {
                $model = FCom_Email_Model_Pref::i()->create();
            }
            if ($valid = $model->validate($post, [], 'email-subscription')) {
                $model->email          = $post['email'];
                $model->sub_newsletter = 1;
                $model->unsub_all      = 0;
                $model->save();
            }
            //response
            $successMessage = $this->_('Email subscribe successful.');
            if ($r->xhr()) { //ajax request
                if ($valid) {
                    $result = ['status' => 'success', 'message' => $successMessage];
                } else {
                    $result = ['status' => 'error', 'message' => $this->getAjaxErrorMessage()];
                }
                BResponse::i()->json($result);
            } else {
                if ($valid) {
                    $this->message($successMessage);
                } else {
                    $this->formMessages('email-subscription');
                }
                BResponse::i()->redirect('email/subscribe');
            }
        } catch (Exception $e) {
            BDebug::logException($e);
            if ($r->xhr()) {
                BResponse::i()->json(['status' => 'error', 'message' => $e->getMessage()]);
            } else {
                $this->message($e->getMessage(), 'error');
                BResponse::i()->redirect('email/subscribe');
            }
        }
    }

    public function getAjaxErrorMessage()
    {
        $messages      = BSession::i()->messages('validator-errors:email-subscription');
        $errorMessages = [];
        foreach ($messages as $m) {
            if (is_array($m['msg'])) {
                $errorMessages[] = $m['msg']['error'];
            } else {
                $errorMessages[] = $m['msg'];
            }
        }

        return implode("<br />", $errorMessages);
    }
}
