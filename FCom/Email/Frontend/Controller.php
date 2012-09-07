<?php

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
            $data = BUtil::maskFields($r->post('model'), 'id,email,create_dt,update_dt', true);
            $pref = $hlp->load($email, 'email');
            if (!$pref) {
                $pref = $hlp->create(array('email'=>$email));
            }
            if (empty($data['unsub_all'])) {
                $data['unsub_all'] = 0;
            }
            $pref->set($data)->save();
            BSession::i()->addMessage('Your preferences have been saved', 'success', 'frontend');
        } catch (Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
        }
        $url = BUtil::setUrlQuery($r->currentUrl(), array('token'=>$hlp->getToken($email)));
        BResponse::i()->redirect($url);
    }
}