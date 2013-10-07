<?php

class FCom_Customer_Frontend_Controller_Account extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }

    public function action_index()
    {
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $customer = FCom_Customer_Model_Customer::i()->load($customerId);
        $this->view('customer/account')->set('customer', $customer);
        $crumbs[] = array('label'=>'Account', 'active'=>true);
        $this->view('breadcrumbs')->set('crumbs', $crumbs);
        $this->layout('/customer/account');
    }

    public function action_edit()
    {
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $customer = FCom_Customer_Model_Customer::i()->load($customerId);

        $post = BRequest::i()->post();
        if ($post) {
            $r = $post['model'];
            try {
                if (empty($r['email'])) {
                    throw new Exception('Incomplete or invalid form data.');
                }
                $customer->set($r)->save();

                $url = Bapp::href('customer/myaccount');
                BResponse::i()->redirect($url);
            } catch(Exception $e) {
                BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
                $url = Bapp::href('customer/myaccount/edit');
                BResponse::i()->redirect($url);
            }

        }
        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'Edit', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;

        $this->messages('customer/account/edit');
        $this->view('customer/account/edit')->customer = $customer;
        $this->layout('/customer/account/edit');
    }

    public function action_editpassword()
    {
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $customer = FCom_Customer_Model_Customer::i()->load($customerId);

        $post = BRequest::i()->post();
        if ($post) {
            $r = $post['model'];
            try {

                if (!empty($r['password_confirm']) && $r['password']!=$r['password_confirm']) {
                    throw new Exception('Incomplete or invalid form data.');
                } elseif ($r['password']== $r['password_confirm']) {
                    $customer->setPassword($r['password']);
                }

                $customer->save();

                $url = Bapp::href('customer/myaccount');
                BResponse::i()->redirect($url);
            } catch(Exception $e) {
                BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
                $url = Bapp::href('customer/myaccount/editpassword');
                BResponse::i()->redirect($url);
            }

        }

        $crumbs[] = array('label'=>'Account', 'href'=>Bapp::href('customer/myaccount'));
        $crumbs[] = array('label'=>'Edit Password', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->messages('customer/account/editpassword');
        $this->view('customer/account/editpassword')->customer = $customer;
        $this->layout('/customer/account/editpassword');
    }
}
