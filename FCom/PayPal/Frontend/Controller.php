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
        $nvpShippingAddress = array();
        if (BConfig::i()->get('modules/FCom_PayPal/show_shipping') == 'on') {
            $shippingAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'shipping');
            $nvpShippingAddress = array(
                'NOSHIPPING' => 0,
                'PAYMENTREQUEST_0_SHIPTONAME' => $shippingAddress->firstname . ' ' . $shippingAddress->lastname,
                'PAYMENTREQUEST_0_SHIPTOSTREET' => $shippingAddress->street1,
                'PAYMENTREQUEST_0_SHIPTOSTREET2' => $shippingAddress->street2,
                'PAYMENTREQUEST_0_SHIPTOCITY' => $shippingAddress->city,
                'PAYMENTREQUEST_0_SHIPTOSTATE' => $shippingAddress->state,
                'PAYMENTREQUEST_0_SHIPTOZIP' => $shippingAddress->zip,
                'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $shippingAddress->country,
                'PAYMENTREQUEST_0_SHIPTOPHONENUM' => $shippingAddress->phone
            );
        } else {
            $nvpShippingAddress['NOSHIPPING'] = 1;
        }

        $nvpArr = array(
            'INVNUM'                            => $salesOrder->id(),
            'PAYMENTREQUEST_0_AMT'              => number_format($salesOrder->balance, 2),
            'PAYMENTREQUEST_0_PAYMENTACTION'    => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'     => 'USD',
            'RETURNURL'                         => $baseUrl.'/return',
            'CANCELURL'                         => $baseUrl.'/cancel',
            //'PAGESTYLE'     => 'paypal',
        );
        $nvpArr = array_merge($nvpArr, $nvpShippingAddress);
        //print_r($nvpArr);exit;
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

        //unset cart
        $cart->status = 'finished';
        $cart->save();
        FCom_Checkout_Model_Cart::sessionCartId(null);

        $hrefUrl = BApp::href('checkout/success');
        BResponse::i()->redirect($hrefUrl);
    }

    public function action_cancel()
    {
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout");
    }

}