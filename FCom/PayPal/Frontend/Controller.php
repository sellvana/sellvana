<?php

class FCom_PayPal_Frontend_Controller extends BActionController
{
    public function action_redirect()
    {
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
        if (!$salesOrder) {
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
            return;
        }

        $baseUrl = BApp::href('paypal');
        $nvpShippingAddress = array();
        if (BConfig::i()->get('modules/FCom_PayPal/show_shipping') == 'on') {
            $shippingAddress = FCom_Sales_Model_Cart_Address::i()->findByCartType($cart->id(), 'shipping');
            $nvpShippingAddress = array(
                'NOSHIPPING' => 0,
                'REQCONFIRMSHIPPING' => 0,
                'PAYMENTREQUEST_0_SHIPTONAME' => $shippingAddress->firstname . ' ' . $shippingAddress->lastname,
                'PAYMENTREQUEST_0_SHIPTOSTREET' => $shippingAddress->street1,
                'PAYMENTREQUEST_0_SHIPTOSTREET2' => $shippingAddress->street2,
                'PAYMENTREQUEST_0_SHIPTOCITY' => $shippingAddress->city,
                'PAYMENTREQUEST_0_SHIPTOSTATE' => $shippingAddress->region,
                'PAYMENTREQUEST_0_SHIPTOZIP' => $shippingAddress->postcode,
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
        $resArr = FCom_PayPal_RemoteApi::i()->call('SetExpressCheckout', $nvpArr);
//echo "<xmp>"; print_r($resArr); echo "</xmp>"; exit;
        if (false===$resArr) {
            throw new BException(FCom_PayPal_RemoteApi::i()->getError());
        }
        $sData =& BSession::i()->dataToUpdate();
        $sData['paypal']['token'] = $resArr['TOKEN'];
        BResponse::i()->redirect(FCom_PayPal_RemoteApi::i()->getExpressCheckoutUrl($resArr['TOKEN']));
    }

    public function action_return()
    {
        $sData =& BSession::i()->dataToUpdate();
        $cart = FCom_Sales_Model_Cart::i()->sessionCart();
        $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
        if (!$salesOrder) {
            $href = BApp::href('cart');
            BResponse::i()->redirect($href);
            return;
        }

        $resArr = FCom_PayPal_RemoteApi::i()->call('GetExpressCheckoutDetails',  array('TOKEN' => $sData['paypal']['token']));
        if (false===$resArr) {
            $this->message(FCom_PayPal_RemoteApi::i()->getError(), 'error');
            BResponse::i()->redirect('checkout/checkout');
            return;
        }

        if (empty($resArr['PAYERID'])) {
            $this->message('Payment action not initiated', 'error');
            BResponse::i()->redirect('checkout');
            return;
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
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'PAYMENTREQUEST_0_AMT'           => number_format($salesOrder->balance, 2),
//            'PAYMENTREQUEST_0_ITEMAMT'           => number_format($salesOrder->balance, 2),
            'PAYMENTREQUEST_0_CURRENCYCODE'  => 'USD',
            'IPADDRESS'     => $_SERVER['SERVER_NAME'],
            //'BUTTONSOURCE'  => '',
        );
        $nvpShipArr = array();
        if (BConfig::i()->get('modules/FCom_PayPal/show_shipping') == 'on') {
            $shippingAddress = FCom_Sales_Model_Cart_Address::i()->findByCartType($cart->id(), 'shipping');
            $nvpShipArr = array(
                'PAYMENTREQUEST_0_SHIPTONAME' => $shippingAddress->firstname . ' ' . $shippingAddress->lastname,
                    'PAYMENTREQUEST_0_SHIPTOSTREET' => $shippingAddress->street1,
                    'PAYMENTREQUEST_0_SHIPTOSTREET2' => $shippingAddress->street2,
                    'PAYMENTREQUEST_0_SHIPTOCITY' => $shippingAddress->city,
                    'PAYMENTREQUEST_0_SHIPTOSTATE' => $shippingAddress->region,
                    'PAYMENTREQUEST_0_SHIPTOZIP' => $shippingAddress->postcode,
                    'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $shippingAddress->country,
                    'PAYMENTREQUEST_0_SHIPTOPHONENUM' => $shippingAddress->phone
                //'BUTTONSOURCE'  => '',
            );
        }
        if (!empty($nvpShipArr)) {
            $nvpArr = array_merge($nvpArr, $nvpShipArr);
        }

         /* Make the call to PayPal to finalize payment
            If an error occured, show the resulting errors
            */
        $resArr = FCom_PayPal_RemoteApi::i()->call('DoExpressCheckoutPayment', $nvpArr);
        if (false===$resArr) {
            $this->message(FCom_PayPal_RemoteApi::i()->getError(), 'error');
            BResponse::i()->redirect('checkout');
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

        //set sales order as paid
        $salesOrder->paid();

        //unset cart
        $cart->status = 'finished';
        $cart->save();
        FCom_Sales_Model_Cart::i()->sessionCartId(null);

        $hrefUrl = BApp::href('checkout/success');
        BResponse::i()->redirect($hrefUrl);
    }

    public function action_cancel()
    {
        BResponse::i()->redirect(BConfig::i()->get('secure_url')."/checkout");
    }

}
