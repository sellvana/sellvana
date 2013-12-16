<?php

class FCom_Customer_Frontend_Controller_Account extends FCom_Frontend_Frontend_Controller_Abstract
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
        $formId = 'account-edit';
        $this->view('customer/account/edit')->set(array('customer' => $customer, 'formId' => $formId));
        /*$post = BRequest::i()->post();
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

            $this->view('customer/account/edit')->customer = $customer;*/
        $this->layout('/customer/account/edit');
    }

    public function action_edit__POST()
    {
        try {
            $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
            $customer   = FCom_Customer_Model_Customer::i()->load($customerId);
            $r      = BRequest::i()->post('model');
            $formId = 'account-edit';
            $customer->setAccountEditRules(false);

            //set rule email unique if customer update email
            $expandRules = array();
            if ($customer->get('email') != $r['email']) {
                $expandRules = array(array('email', 'FCom_Customer_Model_Customer::ruleEmailUnique', 'Email is exist'));
            }

            if ($customer->validate($r, $expandRules, $formId)) {
                $customer->set($r)->save();
                BSession::i()->addMessage($this->_('Your account info has been updated'), 'success', 'frontend');
                BResponse::i()->redirect(BApp::href('customer/myaccount'));
            } else {
                BSession::i()->addMessage($this->_('Cannot save data, please fix above errors'), 'error', 'validator-errors:' . $formId);
                $this->formMessages($formId);
                BResponse::i()->redirect(BApp::href('customer/myaccount/edit'));
            }

        } catch (Exception $e) {
            BDebug::logException($e);
            BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
            BResponse::i()->redirect(Bapp::href('customer/myaccount/edit'));
        }
    }

    public function action_editpassword()
    {
        /*$customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
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
        $this->view('customer/account/editpassword')->customer = $customer;*/
        $this->layout('/customer/account/editpassword');
    }

    public function action_editpassword__POST()
    {
        try {
            $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
            $customer = FCom_Customer_Model_Customer::i()->load($customerId);
            $r = BRequest::i()->post('model');
            $formId = 'change-password';
            $customer->setChangePasswordRules();

            if ($customer->validate($r, array(), $formId)) {
                if (!Bcrypt::verify($r['current_password'], $customer->get('password_hash'))) {
                    BSession::i()->addMessage($this->_('Current password is not correct, please try again'), 'error', 'frontend');
                    BResponse::i()->redirect(BApp::href('customer/myaccount/editpassword'));
                } else {
                    $customer->set($r)->save();
                    BSession::i()->addMessage($this->_('Your password has been updated'), 'success', 'frontend');
                    BResponse::i()->redirect(BApp::href('customer/myaccount'));
                }
            } else {
                $this->formMessages($formId);
                BResponse::i()->redirect(BApp::href('customer/myaccount/editpassword'));
            }
        } catch(Exception $e) {
            BSession::i()->addMessage($e->getMessage(), 'error', 'frontend');
            $url = Bapp::href('customer/myaccount/editpassword');
            BResponse::i()->redirect($url);
        }
    }
}
