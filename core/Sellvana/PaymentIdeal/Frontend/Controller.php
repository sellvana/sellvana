<?php

/**
 * Class Sellvana_PaymentIdeal_Frontend_Controller
 *
 * @property Sellvana_PaymentIdeal_PaymentMethod $Sellvana_PaymentIdeal_PaymentMethod
 */
class Sellvana_PaymentIdeal_Frontend_Controller
    extends FCom_Frontend_Controller_Abstract
{
    public function action_report()
    {
        $transactionId = $this->BRequest->get('transaction_id');
//        $this->BDebug->log(__METHOD__, Sellvana_PaymentIdeal_PaymentMethod::IDEAL_LOG);
//        $this->BDebug->log($transactionId, Sellvana_PaymentIdeal_PaymentMethod::IDEAL_LOG);
        if ($transactionId) {
            try {
                /* @var $paymentMethod Sellvana_PaymentIdeal_PaymentMethod */
                $paymentMethod = $this->Sellvana_PaymentIdeal_PaymentMethod;
//                $this->BDebug->log('Before check payment', Sellvana_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $paymentMethod->checkPayment($transactionId);
//                $this->BDebug->log('After check payment', Sellvana_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $paymentMethod->setOrderPaid($transactionId);
//                $this->BDebug->log('After set order id', Sellvana_PaymentIdeal_PaymentMethod::IDEAL_LOG);
            } catch (Exception $e) {
                $this->BDebug->logException($e);
                $this->BDebug->log($e->getMessage(), Sellvana_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $this->BDebug->log($transactionId, Sellvana_PaymentIdeal_PaymentMethod::IDEAL_LOG);
            }
        }
    }

    public function action_return()
    {
        $transactionId = $this->BRequest->get('transaction_id');
        if ($transactionId) {
            /* @var $paymentMethod Sellvana_PaymentIdeal_PaymentMethod */
            $paymentMethod = $this->Sellvana_PaymentIdeal_PaymentMethod;
            $order = $paymentMethod->loadOrderByTransactionId($transactionId);
            $sData =& $this->BSession->dataToUpdate();
            $sData['last_order']['id'] = $order ? $order->id : null;
        }
        return $this->forward('success', 'Sellvana_Checkout_Frontend_Controller_Checkout'); // forward to success page
    }
}
