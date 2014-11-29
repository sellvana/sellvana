<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Email_Frontend_Controller
 *
 * @property FCom_Email_Model_Pref $FCom_Email_Model_Pref
 */
class FCom_Email_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_pref()
    {
        $this->layout('/email/pref');
    }

    public function action_pref__POST()
    {
        $hlp = $this->FCom_Email_Model_Pref;
        $r = $this->BRequest;
        $email = $r->request('email');
        try {
            if (!$hlp->validateToken($email, $r->request('token'))) {
                throw new Exception('Invalid token');
            }
            $data = $this->BUtil->arrayMask($r->post('model'), 'id,email,create_at,update_at', true);
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
        $url = $this->BUtil->setUrlQuery($r->currentUrl(), ['token' => $hlp->getToken($email)]);
        $this->BResponse->redirect($url);
    }

    public function action_subscribe()
    {
        $this->layout('/email/subscribe');
    }

    public function action_subscribe__POST()
    {
        $r    = $this->BRequest;
        $post = $r->post();
        try {
            $model = $this->FCom_Email_Model_Pref->load($post['email'], 'email');
            /** @var $model FCom_Email_Model_Pref */
            if (!$model) {
                $model = $this->FCom_Email_Model_Pref->create();
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
                $this->BResponse->json($result);
            } else {
                if ($valid) {
                    $this->message($successMessage);
                } else {
                    $this->formMessages('email-subscription');
                }
                $this->BResponse->redirect('email/subscribe');
            }
        } catch (Exception $e) {
            $this->BDebug->logException($e);
            if ($r->xhr()) {
                $this->BResponse->json(['status' => 'error', 'message' => $e->getMessage()]);
            } else {
                $this->message($e->getMessage(), 'error');
                $this->BResponse->redirect('email/subscribe');
            }
        }
    }

    public function getAjaxErrorMessage()
    {
        $messages      = $this->BSession->messages('validator-errors:email-subscription');
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
