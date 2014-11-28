<?php

/**
 * Class FCom_Sales_Workflow_Cart
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Order $FCom_Sales_Model_Order
 */
class FCom_Sales_Workflow_Checkout extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerChoosesGuestCheckout',
        'customerUpdatesShippingAddress',
        'customerUpdatesShippingMethod',
        'customerUpdatesBillingAddress',
        'customerUpdatesPaymentMethod',
        'customerPlacesOrder',
        'customerRegistersAfterOrder',
    ];

    public function customerChoosesGuestCheckout($args)
    {

    }

    public function customerUpdatesShippingAddress($args)
    {

    }

    public function customerUpdatesShippingMethod($args)
    {

    }

    public function customerUpdatesBillingAddress($args)
    {

    }

    public function customerUpdatesPaymentMethod($args)
    {

    }

    public function customerPlacesOrder($args)
    {
        /** @var FCom_Customer_Model_Customer $customer */
        $customer = $this->_getCustomer($args);

        /** @var FCom_Sales_Model_Cart $cart */
        $cart = $this->_getCart($args);

        /** @var FCom_Sales_Model_Order $order */
        $order = $this->FCom_Sales_Model_Order->create();

        $order->importDataFromCart($cart);

        if ($order->isPayable()) {
            $result = [];
            $this->FCom_Sales_Main->workflowAction('customerPaysOnCheckout', [
                'cart' => $cart,
                'order' => $order,
                'result' => &$result,
            ]);
        }

        $cart->setStateOrdered()->save();

        $args['result']['order'] = $order;
    }

    public function customerRegistersAfterOrder($args)
    {

    }
}