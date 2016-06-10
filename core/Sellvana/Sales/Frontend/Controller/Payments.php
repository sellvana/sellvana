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
        $paymentId = (int)$this->BRequest->get('id');
        $payment = $this->Sellvana_Sales_Model_Order_Payment->load($paymentId);
        if (!$payment) {
            $this->BResponse->redirect('orders');
            $this->forward(false);
            return false;
        }

        $orderId = $payment->get('order_id');
        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        $order = $this->Sellvana_Sales_Model_Order->isOrderExists($orderId, $customerId);
        if (!$order) {
            $this->BResponse->redirect('orders');
            $this->forward(false);
            return false;
        }

        $payment->getMethodObject()->payOnCheckout($payment);
    }
}
