<?php

class FCom_PayPal_Frontend_Controller extends BActionController
{
    public function action_redirect()
    {
        $cart = FCom_Checkout_Model_Cart::sessionCart();
        $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
        if (!$salesOrder) {
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
        }

        $baseUrl = BApp::href('paypal');

        $nvpArr = array(
            'INVNUM'                            => $salesOrder->id(),
            'PAYMENTREQUEST_0_AMT'              => number_format($salesOrder->balance, 2),
            'PAYMENTREQUEST_0_PAYMENTACTION'    => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'     => 'USD',
            'RETURNURL'                         => $baseUrl.'/return',
            'CANCELURL'                         => $baseUrl.'/cancel',
            //'PAGESTYLE'     => 'paypal',
        );

        $resArr = FCom_PayPal_Api::i()->call('SetExpressCheckout', $nvpArr);
//echo "<xmp>"; print_r($resArr); echo "</xmp>"; exit;
        if (false===$resArr) {
            throw new BException(FCom_PayPal_Api::i()->getError());
        }
        $sData =& BSession::i()->dataToUpdate();
        $sData['paypal']['token'] = $resArr['TOKEN'];
        BResponse::i()->redirect(FCom_PayPal_Api::getExpressCheckoutUrl($resArr['TOKEN']));
    }

    public function action_return()
    {
        $sData =& BSession::i()->dataToUpdate();
        $cart = FCom_Checkout_Model_Cart::sessionCart();
        $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
        if (!$salesOrder) {
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
        }

        $resArr = FCom_PayPal_Api::i()->call('GetExpressCheckoutDetails',  array('TOKEN' => $sData['paypal']['token']));
        if (false===$resArr) {
            BSession::i()->addMessage(FCom_PayPal_Api::i()->getError(), 'error', 'frontend');
            BResponse::i()->redirect(BApp::href('checkout/checkout'));
        }

        if (empty($resArr['PAYERID'])) {
            BSession::i()->addMessage('Payment action not initiated', 'error', 'frontend');
            BResponse::i()->redirect(BApp::href('checkout'));
        }
        /*
        $cart->set(array(
            'order_status' => 'paypal.returned',
            'email' => $resArr['EMAIL'],
            'firstname' => $resArr['FIRSTNAME'],
            'lastname' => $resArr['LASTNAME'],
            'company' => !empty($resArr['COMPANY']) ? $resArr['COMPANY'] : '',
            'street1' => $resArr['SHIPTOSTREET'],
            'street2' => !empty($resArr['SHIPTOSTREET2']) ? $resArr['SHIPTOSTREET2'] : '',
            'city' => $resArr['SHIPTOCITY'],
            'state' => !empty($resArr['SHIPTOSTATE']) ? $resArr['SHIPTOSTATE'] : '',
            'zip' => !empty($resArr['SHIPTOZIP']) ? $resArr['SHIPTOZIP'] : '',
            'country' => $resArr['SHIPTOCOUNTRYCODE'],
            'paypal_payerid' => $resArr['PAYERID'],
            #'paypal_correlationid' => $resArr['CORRELATIONID'],
            'paypal_status' => '!/'.$resArr['PAYERSTATUS'].'/'.$resArr['ADDRESSSTATUS'],
        ))->save();
*/
        $nvpArr = array(
            'TOKEN'         => $resArr['TOKEN'],
            'PAYERID'       => $resArr['PAYERID'],
            'PAYMENTACTION' => 'Sale',
            'AMT'           => number_format($salesOrder->balance, 2),
            'CURRENCYCODE'  => 'USD',
            'IPADDRESS'     => $_SERVER['SERVER_NAME'],
            //'BUTTONSOURCE'  => '',
        );

         /* Make the call to PayPal to finalize payment
            If an error occured, show the resulting errors
            */
        $resArr = FCom_PayPal_Api::i()->call('DoExpressCheckoutPayment', $nvpArr);
        if (false===$resArr) {
            BSession::i()->addMessage(FCom_PayPal_Api::i()->getError(), 'error', 'frontend');
            BResponse::i()->redirect(BApp::href('checkout'));
        }
        /*
        $cart->set(array(
            'order_status' => 'complete',
            'paypal_transactionid' => $resArr['TRANSACTIONID'],
            'paypal_paid' => $resArr['AMT'],
            'paypal_fee' => !empty($resArr['FEEAMT']) ? $resArr['FEEAMT'] : 0,
            'paypal_tax' => !empty($resArr['TAXAMT']) ? $resArr['TAXAMT'] : 0,
            'paypal_status' => $resArr['PAYMENTSTATUS']=='Completed' ? 'Completed' : $resArr['PAYMENTSTATUS'].'/'.$resArr['PENDINGREASON'].'/'.$resArr['REASONCODE'],
        ))->save();
         *
         */
        //Cart::notify();

        $sData['last_order']['id'] = $salesOrder->id();

        FCom_Checkout_Model_Cart::sessionCartId(null);

        $hrefUrl = BApp::href('checkout/success');
        BResponse::i()->redirect($hrefUrl);
    }

    public function action_cancel()
    {
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout");
    }

}