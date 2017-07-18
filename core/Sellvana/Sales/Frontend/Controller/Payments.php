<?php

/**
 * Class Sellvana_Sales_Frontend_Controller_Payments
 *
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_Sales_Frontend_Controller_Payments extends FCom_Frontend_Controller_Abstract
{
    public function onBeforeDispatch()
    {
        if (!parent::onBeforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        return $this->Sellvana_Customer_Model_Customer->isLoggedIn();
    }

    protected function _validateToken($token)
    {
        $payment = $this->Sellvana_Sales_Model_Order_Payment->load($token, 'token');
        $tokenTtl = $this->BConfig->get('modules/Sellvana_Sales/root_transaction_token_ttl_hr', 24);

        if (!$payment) {
            throw new BException('Payment does not exist');
        }

        if (strtotime($payment->get('token_at')) < time() - $tokenTtl * 3600) {
            $payment->set(['token' => null, 'token_at' => null])->save();
            throw new BException('Token is too old');
        }

        if ($payment->order()->get('customer_id') != $this->Sellvana_Customer_Model_Customer->sessionUserId()) {
            throw new BException('The order either does not exist or does not belong to the customer');
        }
    }

    public function action_create_root_transaction()
    {
        $token = $this->BRequest->get('token');

        try {
            $this->_validateToken($token);

            $payment = $this->Sellvana_Sales_Model_Order_Payment->load($token, 'token');
            $this->BSession->set('last_order_id', $payment->order()->id());
            $result = [];
            $this->Sellvana_Sales_Main->workflowAction('customerPaysByUrl', [
                'payment' => $payment,
                'result' => &$result,
            ]);

            $href = 'orders';
            if (!empty($result['payment']['redirect_to'])) {
                $href = $result['payment']['redirect_to'];
            }

            if (!empty($result['payment']['post_params']) && !empty($result['payment']['redirect_to'])) {
                $this->layout('/checkout-simple/redirect');
                $view = $this->view('checkout-simple/redirect');
                $view->set('hiddenFields', $result['payment']['post_params']);
                $view->set('postUrl', $result['payment']['redirect_to']);
                $this->BResponse->set($view->render());
                $this->BResponse->render();
            } else {
                $this->BResponse->redirect($href);
            }

        } catch (BException $e) {
            $this->BResponse->redirect('orders');
            $this->forward(false);
        }
    }

    public function action_root_transaction_return()
    {
        $token = $this->BRequest->get('root_token');

        try {
            $this->_validateToken($token);
            $payment = $this->Sellvana_Sales_Model_Order_Payment->load($token, 'token');
            $result = $payment->getMethodObject()->processReturnFromExternalCheckout();

            if (!empty($result['error'])) {
                $this->message($result['error']['message'], 'error');
            } else {
                $this->message($this->_(('Payment has been successfully approved')), 'success');
            }
        } catch (BException $e) {
            $this->forward(false);
        }

        $this->BResponse->redirect('orders');
    }

    public function action_root_transaction_cancel()
    {
        $token = $this->BRequest->get('root_token');
        try {
            $this->_validateToken($token);
        } catch (BException $e) {
            $this->forward(false);
        }
        
        $this->BResponse->redirect('orders');
    }

}
