<?php

class FCom_Customer_Frontend_Controller_Account extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_index()
    {
        $customerId = FCom_Customer_Model_Customer::sessionUserId();
        $customer = FCom_Customer_Model_Customer::i()->load($customerId);
        $this->view('customer/account')->customer = $customer;
        $this->layout('/customer/account');
    }

    public function action_edit()
    {
        $customerId = FCom_Customer_Model_Customer::sessionUserId();
        $customer = FCom_Customer_Model_Customer::i()->load($customerId);

        $post = BRequest::i()->post();
        if ($post) {
            $r = $post['model'];
            try {
                if (empty($r['email'])) {
                    throw new Exception('Incomplete or invalid form data.');
                }
                /*
                if (!empty($post['password_confirm']) && $post['password']!=$post['password_confirm']) {
                    throw new Exception('Incomplete or invalid form data.');
                } elseif ($post['password']== $post['password_confirm']) {
                    $customer->setPassword($post['password']);
                }
                 *
                 */
                $customer->set($r)->save();

                $url = Bapp::href('customer/myaccount');
                BResponse::i()->redirect($url);
            } catch(Exception $e) {
                BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
                $url = Bapp::href('customer/myaccount/edit');
                BResponse::i()->redirect($url);
            }

        }

        $this->messages('customer/account/edit');
        $this->view('customer/account/edit')->customer = $customer;
        $this->layout('/customer/account/edit');
    }
}