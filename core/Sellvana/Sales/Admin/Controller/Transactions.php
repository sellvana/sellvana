<?php

/**
 * Class Sellvana_Sales_Admin_Controller_Transactions
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Payment $Sellvana_Sales_Model_Order_Payment
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */

class Sellvana_Sales_Admin_Controller_Transactions extends Sellvana_Sales_Admin_Controller_Abstract
{
    protected static $_origClass = __CLASS__;

    protected static $_typeToPaymentActions = [
        Sellvana_Sales_Model_Order_Payment_Transaction::CAPTURE => 'capture',
        Sellvana_Sales_Model_Order_Payment_Transaction::REFUND => 'refund',
        Sellvana_Sales_Model_Order_Payment_Transaction::REAUTHORIZATION => 'reauthorize',
        Sellvana_Sales_Model_Order_Payment_Transaction::AUTHORIZATION => 'authorize',
        Sellvana_Sales_Model_Order_Payment_Transaction::VOID => 'void',
    ];

    public function action_create__POST()
    {
        try {
            $orderId = $this->BRequest->get('id');
            $order = $this->Sellvana_Sales_Model_Order->load($orderId);

            if (!$order) {
                throw new BException('Invalid order');
            }

            $types = $this->BRequest->post('types');
            $amounts = $this->BRequest->post('amounts');

            foreach ($types as $paymentId => $type) {
                /** @var Sellvana_Sales_Model_Order_Payment $payment */
                $payment = $this->Sellvana_Sales_Model_Order_Payment->load((int)$paymentId);
                if (!array_key_exists($type, self::$_typeToPaymentActions)) {
                    throw new BException('Unknown transaction type');
                }

                $action = self::$_typeToPaymentActions[$type];
                if (in_array($action, ['capture', 'refund'])) {
                    $amount = isset($amounts[$paymentId]) ? $amounts[$paymentId] : 0;
                    $payment->$action($amount);
                } else {
                    $payment->$action();
                }
            }

            $result = $this->_resetOrderTabs($order);
            $result['message'] = $this->_('Payment has been created');
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $result['tabs']['payments'] = (string)$this->view('order/orders-form/payments')->set('model', $order);
        $this->BResponse->json($result);

    }
}