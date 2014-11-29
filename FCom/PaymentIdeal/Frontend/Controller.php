<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_PaymentIdeal_Frontend_Controller
 *
 * @property FCom_PaymentIdeal_PaymentMethod $FCom_PaymentIdeal_PaymentMethod
 */
class FCom_PaymentIdeal_Frontend_Controller
    extends FCom_Frontend_Controller_Abstract
{
    public function action_report()
    {
        $transactionId = $this->BRequest->get('transaction_id');
//        $this->BDebug->log(__METHOD__, FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
//        $this->BDebug->log($transactionId, FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
        if ($transactionId) {
            try {
                /* @var $paymentMethod FCom_PaymentIdeal_PaymentMethod */
                $paymentMethod = $this->FCom_PaymentIdeal_PaymentMethod;
//                $this->BDebug->log('Before check payment', FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $paymentMethod->checkPayment($transactionId);
//                $this->BDebug->log('After check payment', FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $paymentMethod->setOrderPaid($transactionId);
//                $this->BDebug->log('After set order id', FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
            } catch (Exception $e) {
                $this->BDebug->logException($e);
                $this->BDebug->log($e->getMessage(), FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $this->BDebug->log($transactionId, FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
            }
        }
    }

    public function action_return()
    {
        $transactionId = $this->BRequest->get('transaction_id');
        if ($transactionId) {
            /* @var $paymentMethod FCom_PaymentIdeal_PaymentMethod */
            $paymentMethod = $this->FCom_PaymentIdeal_PaymentMethod;
            $order = $paymentMethod->loadOrderByTransactionId($transactionId);
            $sData =& $this->BSession->dataToUpdate();
            $sData['last_order']['id'] = $order ? $order->id : null;
        }
        return $this->forward('success', 'FCom_Checkout_Frontend_Controller_Checkout'); // forward to success page
    }
}
