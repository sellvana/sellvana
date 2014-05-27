<?php

class FCom_Customer_Frontend_Controller_Account extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        BResponse::i()->nocache();
    }

    public function authenticate($args = [])
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath() == '/login';
    }

    public function action_index()
    {
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $customer = FCom_Customer_Model_Customer::i()->load($customerId);
        $this->view('customer/account')->set('customer', $customer);
        $crumbs[] = ['label' => 'Account', 'active' => true];
        $this->view('breadcrumbs')->set('crumbs', $crumbs);
        $this->layout('/customer/account');
    }

    public function action_edit()
    {
        $customerId = FCom_Customer_Model_Customer::i()->sessionUserId();
        $customer = FCom_Customer_Model_Customer::i()->load($customerId);
        $formId = 'account-edit';
        $this->view('customer/account/edit')->set(['customer' => $customer, 'formId' => $formId]);
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
                    $this->message($e->getMessage(), 'error');
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
            $customer = FCom_Customer_Model_Customer::i()->sessionUser();
            $r      = BRequest::i()->post('model');
            $formId = 'account-edit';
            $customer->setAccountEditRules(false);

            //set rule email unique if customer update email
            $expandRules = [];
            if ($customer->get('email') != $r['email']) {
                $expandRules = [['email', 'FCom_Customer_Model_Customer::ruleEmailUnique', 'Email is exist']];
            }

            if ($customer->validate($r, $expandRules, $formId)) {
                if (empty($r['current_password']) || !Bcrypt::verify($r['current_password'], $customer->get('password_hash'))) {
                    $this->message('Current password is not correct, please try again', 'error');
                    BResponse::i()->redirect('customer/myaccount/edit');
                } else {
                    $customer->set($r)->save();
                    $this->message('Your account info has been updated');
                    BResponse::i()->redirect('customer/myaccount');
                }
            } else {
                $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $formId);
                $this->formMessages($formId);
                BResponse::i()->redirect('customer/myaccount/edit');
            }

        } catch (Exception $e) {
            BDebug::logException($e);
            $this->message($e->getMessage(), 'error');
            BResponse::i()->redirect('customer/myaccount/edit');
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
                $this->message($e->getMessage(), 'error');
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

            if ($customer->validate($r, [], $formId)) {
                if (empty($r['current_password']) || !Bcrypt::verify($r['current_password'], $customer->get('password_hash'))) {
                    $this->message('Current password is not correct, please try again', 'error');
                    BResponse::i()->redirect('customer/myaccount/editpassword');
                } else {
                    $customer->set($r)->save();
                    $this->message('Your password has been updated');
                    BResponse::i()->redirect('customer/myaccount');
                }
            } else {
                $this->formMessages($formId);
                BResponse::i()->redirect('customer/myaccount/editpassword');
            }
        } catch(Exception $e) {
            $this->message($e->getMessage(), 'error');
            $url = Bapp::href('customer/myaccount/editpassword');
            BResponse::i()->redirect($url);
        }
    }
}
