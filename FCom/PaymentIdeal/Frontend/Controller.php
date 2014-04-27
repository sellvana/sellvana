<?php
/**
 * Created by pp
 * @project fulleron
 */

class FCom_PaymentIdeal_Frontend_Controller
    extends FCom_Frontend_Controller_Abstract
{
    public function action_report()
    {
        $transactionId = BRequest::get( 'transaction_id' );
//        BDebug::log(__METHOD__, FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
//        BDebug::log($transactionId, FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
        if ( $transactionId ) {
            try {
                /* @var $paymentMethod FCom_PaymentIdeal_PaymentMethod */
                $paymentMethod = FCom_PaymentIdeal_PaymentMethod::i();
//                BDebug::log('Before check payment', FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $paymentMethod->checkPayment( $transactionId );
//                BDebug::log('After check payment', FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
                $paymentMethod->setOrderPaid( $transactionId );
//                BDebug::log('After set order id', FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG);
            } catch ( Exception $e ) {
                BDebug::logException( $e );
                BDebug::log( $e->getMessage(), FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG );
                BDebug::log( $transactionId, FCom_PaymentIdeal_PaymentMethod::IDEAL_LOG );
            }
        }
    }

    public function action_return()
    {
        $transactionId = BRequest::get( 'transaction_id' );
        if ( $transactionId ) {
            /* @var $paymentMethod FCom_PaymentIdeal_PaymentMethod */
            $paymentMethod = FCom_PaymentIdeal_PaymentMethod::i();
            $order = $paymentMethod->loadOrderByTransactionId( $transactionId );
            $sData =& BSession::i()->dataToUpdate();
            $sData[ 'last_order' ][ 'id' ] = $order ? $order->id : null;
        }
        return $this->forward( 'success', 'FCom_Checkout_Frontend_Controller_Checkout' ); // forward to success page
    }
}
