<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_PayPal_Frontend_Controller extends BActionController
{
    public function action_redirect()
    {
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        $salesOrder = $this->FCom_Sales_Model_Order->load($cart->id(), 'cart_id');
        if (!$salesOrder) {
            $href = $this->BApp->href('cart');
            $this->BResponse->redirect($href);
            return;
        }

        $baseUrl = $this->BApp->href('paypal');
        $nvpShippingAddress = [];
        if ($this->BConfig->get('modules/FCom_PayPal/show_shipping') == 'on') {
            $shippingAddress = $this->FCom_Sales_Model_Cart_Address->findByCartType($cart->id(), 'shipping');
            $nvpShippingAddress = [
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
            ];
        } else {
            $nvpShippingAddress['NOSHIPPING'] = 1;
        }

        $nvpArr = [
            'INVNUM'                            => $salesOrder->id(),
            'PAYMENTREQUEST_0_AMT'              => number_format($salesOrder->balance, 2),
            'PAYMENTREQUEST_0_PAYMENTACTION'    => 'Sale',
            'PAYMENTREQUEST_0_CURRENCYCODE'     => 'USD',
            'RETURNURL'                         => $baseUrl . '/return',
            'CANCELURL'                         => $baseUrl . '/cancel',
            //'PAGESTYLE'     => 'paypal',
        ];
        $nvpArr = array_merge($nvpArr, $nvpShippingAddress);
        //print_r($nvpArr);exit;
        $resArr = $this->FCom_PayPal_RemoteApi->call('SetExpressCheckout', $nvpArr);
//echo "<xmp>"; print_r($resArr); echo "</xmp>"; exit;
        if (false === $resArr) {
            throw new BException($this->FCom_PayPal_RemoteApi->getError());
        }
        $sData =& $this->BSession->dataToUpdate();
        $sData['paypal']['token'] = $resArr['TOKEN'];
        $this->BResponse->redirect($this->FCom_PayPal_RemoteApi->getExpressCheckoutUrl($resArr['TOKEN']));
    }

    public function action_return()
    {
        $sData =& $this->BSession->dataToUpdate();
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        $salesOrder = $this->FCom_Sales_Model_Order->load($cart->id(), 'cart_id');
        if (!$salesOrder) {
            $href = $this->BApp->href('cart');
            $this->BResponse->redirect($href);
            return;
        }

        $resArr = $this->FCom_PayPal_RemoteApi->call('GetExpressCheckoutDetails',  ['TOKEN' => $sData['paypal']['token']]);
        if (false === $resArr) {
            $this->message($this->FCom_PayPal_RemoteApi->getError(), 'error');
            $this->BResponse->redirect('checkout/checkout');
            return;
        }

        if (empty($resArr['PAYERID'])) {
            $this->message('Payment action not initiated', 'error');
            $this->BResponse->redirect('checkout');
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
        $nvpArr = [
            'TOKEN'         => $resArr['TOKEN'],
            'PAYERID'       => $resArr['PAYERID'],
            'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
            'PAYMENTREQUEST_0_AMT'           => number_format($salesOrder->balance, 2),
//            'PAYMENTREQUEST_0_ITEMAMT'           => number_format($salesOrder->balance, 2),
            'PAYMENTREQUEST_0_CURRENCYCODE'  => 'USD',
            'IPADDRESS'     => $_SERVER['SERVER_NAME'],
            //'BUTTONSOURCE'  => '',
        ];
        $nvpShipArr = [];
        if ($this->BConfig->get('modules/FCom_PayPal/show_shipping') == 'on') {
            $shippingAddress = $this->FCom_Sales_Model_Cart_Address->findByCartType($cart->id(), 'shipping');
            $nvpShipArr = [
                'PAYMENTREQUEST_0_SHIPTONAME' => $shippingAddress->firstname . ' ' . $shippingAddress->lastname,
                    'PAYMENTREQUEST_0_SHIPTOSTREET' => $shippingAddress->street1,
                    'PAYMENTREQUEST_0_SHIPTOSTREET2' => $shippingAddress->street2,
                    'PAYMENTREQUEST_0_SHIPTOCITY' => $shippingAddress->city,
                    'PAYMENTREQUEST_0_SHIPTOSTATE' => $shippingAddress->region,
                    'PAYMENTREQUEST_0_SHIPTOZIP' => $shippingAddress->postcode,
                    'PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE' => $shippingAddress->country,
                    'PAYMENTREQUEST_0_SHIPTOPHONENUM' => $shippingAddress->phone
                //'BUTTONSOURCE'  => '',
            ];
        }
        if (!empty($nvpShipArr)) {
            $nvpArr = array_merge($nvpArr, $nvpShipArr);
        }

         /* Make the call to PayPal to finalize payment
            If an error occured, show the resulting errors
            */
        $resArr = $this->FCom_PayPal_RemoteApi->call('DoExpressCheckoutPayment', $nvpArr);
        if (false === $resArr) {
            $this->message($this->FCom_PayPal_RemoteApi->getError(), 'error');
            $this->BResponse->redirect('checkout');
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
        $cart->status = 'ordered';
        $cart->save();
        $this->FCom_Sales_Model_Cart->resetSessionCart();

        $hrefUrl = $this->BApp->href('checkout/success');
        $this->BResponse->redirect($hrefUrl);
    }

    public function action_cancel()
    {
        $this->BResponse->redirect($this->BConfig->get('secure_url') . "/checkout");
    }

}
