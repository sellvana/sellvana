<?php

/**
 * Class Sellvana_Sales_Frontend_Controller_Payments
 *
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

    public function action_create_root_transaction()
    {
        $token = (int)$this->BRequest->get('token');

        try {
            $payment = $this->Sellvana_Sales_Model_Order_Payment->load($token, 'token');
            $tokenTtl = $this->BConfig->get('modules/Sellvana_Sales/root_transaction_token_ttl_hr', 24);

            if (!$payment) {
                throw new BException('Payment does not exist');
            }

            if (strtotime($payment->get('token_at')) < time() - $tokenTtl * 3600) {
                $payment->set(['token' => null, 'token_at' => null])->save();
                throw new BException('Token is too old');
            }

            $orderId = $payment->get('order_id');
            $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
            $order = $this->Sellvana_Sales_Model_Order->isOrderExists($orderId, $customerId);
            if (!$order) {
                throw new BException('The order either does not exist or does not belong to the customer');
            }

            $payment->getMethodObject()->payOnCheckout($payment);
        } catch (BException $e) {
            $this->BResponse->redirect('orders');
            $this->forward(false);
            return false;
        }
    }
}
