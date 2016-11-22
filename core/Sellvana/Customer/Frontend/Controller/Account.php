<?php

/**
 * Class Sellvana_Customer_Frontend_Controller_Account
 *
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */

class Sellvana_Customer_Frontend_Controller_Account extends FCom_Frontend_Controller_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        return $this->Sellvana_Customer_Model_Customer->isLoggedIn() || $this->BRequest->rawPath() == '/login';
    }

    public function action_index()
    {
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $this->layout('/customer/account');
        $this->view('customer/account')->set('customer', $customer);
        $crumbs[] = ['label' => 'Account', 'active' => true];
        $this->view('breadcrumbs')->set('crumbs', $crumbs);
    }

    public function action_edit()
    {
        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        $customer = $this->Sellvana_Customer_Model_Customer->load($customerId);
        $formId = 'account-edit';
        $this->layout('/customer/account/edit');
        $this->view('customer/account/edit')->set(['customer' => $customer, 'formId' => $formId]);
        /*$post = $this->BRequest->post();
            if ($post) {
                $r = $post['model'];
                try {
                    if (empty($r['email'])) {
                        throw new Exception('Incomplete or invalid form data.');
                    }
                    $customer->set($r)->save();

                    $url = $this->BApp->href('customer/myaccount');
                    $this->BResponse->redirect($url);
                } catch(Exception $e) {
                    $this->message($e->getMessage(), 'error');
                    $url = $this->BApp->href('customer/myaccount/edit');
                    $this->BResponse->redirect($url);
                }

            }
            $crumbs[] = array('label'=>'Account', 'href'=>$this->BApp->href('customer/myaccount'));
            $crumbs[] = array('label'=>'Edit', 'active'=>true);
            $this->view('breadcrumbs')->crumbs = $crumbs;

            $this->view('customer/account/edit')->customer = $customer;*/
    }

    public function action_edit__POST()
    {
        try {
            $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
            $r      = $this->BRequest->post('model');
            $formId = 'account-edit';

            //set rule email unique if customer update email
            $expandRules = $customer->getAccountEditRules(false);

            $r['is_new'] = false;
            $r['old_email'] = $customer->get('email');

            if ($customer->validate($r, $expandRules, $formId)) {
                //TODO: replace all Bcrypt with password_* functions
                if (empty($r['current_password']) || !$this->Bcrypt->verify($r['current_password'], $customer->get('password_hash'))) {
                    $this->message('Current password is not correct, please try again', 'error');
                    $this->BResponse->redirect('customer/myaccount/edit');
                } else {
                    $customer->set($r)->save();
                    $this->message('Your account info has been updated');
                    $this->BResponse->redirect('customer/myaccount');
                }
            } else {
                $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $formId);
                $this->formMessages($formId);
                $this->BResponse->redirect('customer/myaccount/edit');
            }

        } catch (Exception $e) {
            $this->BDebug->logException($e);
            $this->message($e->getMessage(), 'error');
            $this->BResponse->redirect('customer/myaccount/edit');
        }
    }

    public function action_editpassword()
    {
        /*$customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        $customer = $this->Sellvana_Customer_Model_Customer->load($customerId);

        $post = $this->BRequest->post();
        if ($post) {
            $r = $post['model'];
            try {

                if (!empty($r['password_confirm']) && $r['password']!=$r['password_confirm']) {
                    throw new Exception('Incomplete or invalid form data.');
                } elseif ($r['password']== $r['password_confirm']) {
                    $customer->setPassword($r['password']);
                }

                $customer->save();

                $url = $this->BApp->href('customer/myaccount');
                $this->BResponse->redirect($url);
            } catch(Exception $e) {
                $this->message($e->getMessage(), 'error');
                $url = $this->BApp->href('customer/myaccount/editpassword');
                $this->BResponse->redirect($url);
            }

        }

        $crumbs[] = array('label'=>'Account', 'href'=>$this->BApp->href('customer/myaccount'));
        $crumbs[] = array('label'=>'Edit Password', 'active'=>true);
        $this->view('breadcrumbs')->crumbs = $crumbs;
        $this->view('customer/account/editpassword')->customer = $customer;*/
        $this->layout('/customer/account/editpassword');
    }

    public function action_editpassword__POST()
    {
        try {
            $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
            $customer = $this->Sellvana_Customer_Model_Customer->load($customerId);
            $r = $this->BRequest->post('model');
            $formId = 'change-password';

            if ($customer->validate($r, $customer->getChangePasswordRules(), $formId, true)) {
                if (empty($r['current_password']) || !$this->Bcrypt->verify($r['current_password'], $customer->get('password_hash'))) {
                    $this->message('Current password is not correct, please try again', 'error');
                    $this->BResponse->redirect('customer/myaccount/editpassword');
                } else {
                    $customer->set($r)->save();
                    $this->message('Your password has been updated');
                    $this->BResponse->redirect('customer/myaccount');
                }
            } else {
                $this->formMessages($formId);
                $this->BResponse->redirect('customer/myaccount/editpassword');
            }
        } catch(Exception $e) {
            $this->message($e->getMessage(), 'error');
            $url = $this->BApp->href('customer/myaccount/editpassword');
            $this->BResponse->redirect($url);
        }
    }
}
