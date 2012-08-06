<?php

class FCom_MarketServer_Frontend_Controller_Account extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_index()
    {
        $customerId = FCom_Customer_Model_Customer::sessionUserId();
        $options = FCom_MarketServer_Model_Account::i()->getOptions($customerId);
        if (empty($options)) {
            $data = array(
                'customer_id' => $customerId,
                'token' => BUtil::randomString(40)
                    );
            $options = FCom_MarketServer_Model_Account::create($data)->save();
        }
        $this->view('market/account')->options = $options;
        $this->layout('/market/account');
    }

    public function action_index__POST()
    {
        $customerId = FCom_Customer_Model_Customer::sessionUserId();
        $post = BRequest::i()->post();
        if ($post) {
            $r = $post['model'];
            $r['customer_id'] = $customerId;
            $options = FCom_MarketServer_Model_Account::i()->getOptions($customerId);
            $options->set($r)->save();
        }
        $url = Bapp::href('market/account');
        BResponse::i()->redirect($url);
    }
}