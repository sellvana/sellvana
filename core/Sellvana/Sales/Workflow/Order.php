<?php

/**
 * Class Sellvana_Sales_Workflow_Order
 *
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 * @property Sellvana_Sales_Model_Order_Shipment $Sellvana_Sales_Model_Order_Shipment
 * @property Sellvana_Customer_Model_Address $Sellvana_Customer_Model_Address
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Sales_Workflow_Order extends Sellvana_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    public function action_customerPlacesOrder($args)
    {
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $args['result']['order'];
        $order->state()->overall()->setPlaced();
        $order->addHistoryEvent('placed', 'Order was placed by a customer');

        if ($this->BConfig->get('modules/Sellvana_Sales/review_all_orders')) {
            $order->state()->overall()->setReview();
            $order->addHistoryEvent('auto_review', 'Order was sent for review as per default policy');
        }

        $order->generateToken()->save();

        $this->Sellvana_Sales_Model_Order_Shipment->createShipmentFromOrder($order);
    }

    /**
     * @param $args
     * @throws BException
     * @throws Sellvana_Sales_Workflow_Exception_Recoverable
     */
    public function action_customerCreatesAccountFromOrder($args)
    {
        $order = $this->Sellvana_Sales_Model_Order->load($args['order_id']);
        $email = $order->get('customer_email');
        if (empty($args['post']['password']) || empty($args['post']['password_confirm'])
            || $args['post']['password'] !== $args['post']['password_confirm']
        ) {
            throw new Sellvana_Sales_Workflow_Exception_Recoverable('Invalid password form data');
        }

        if ($this->Sellvana_Customer_Model_Customer->load($email, 'email')) {
            throw new Sellvana_Sales_Workflow_Exception_Recoverable('Account for this email already exists');
        }

        /** @var Sellvana_Customer_Model_Customer $customer */
        $customer = $this->Sellvana_Customer_Model_Customer->create([
            'email' => $order->get('customer_email'),
            'firstname' => $order->get('shipping_firstname'),
            'lastname' => $order->get('shipping_lastname'),
            'status' => 'active',
        ])->setPassword($args['post']['password'])->save();

        $shipping = $this->Sellvana_Customer_Model_Address
            ->create(['customer_id' => $customer->id()])->set($order->addressAsArray('shipping'))->save();

        $customer->setDefaultAddress($shipping, 'shipping');

        if ($order->get('same_address')) {
            $customer->setDefaultAddress($shipping, 'billing');
        } else {
            $billing = $this->Sellvana_Customer_Model_Address
                ->create(['customer_id' => $customer->id()])->set($order->addressAsArray('billing'))->save();
            $customer->setDefaultAddress($billing, 'billing');
        }

        $order->set('customer_id', $customer->id())->save();

        $customer->login();

        $result['customer'] = $customer;
    }

    public function action_customerMergesOrderToAccount($args)
    {
        $order = $args['order'];

        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        if ($order->get('customer_id') && $order->get('customer_id') != $customer->id()) {
            throw new Sellvana_Sales_Workflow_Exception_Recoverable('This order is already associated with another account');
        }

        $order->set('customer_id', $customer->id())->save();

    }

    public function action_adminPlacesOrder($args)
    {
        $args['order']->state()->overall()->setPlaced();
        $args['order']->addHistoryEvent('placed', 'Order was placed by an admin user');

        $args['order']->save();
    }

    public function action_adminUpdatesOrderShippingAddress($args)
    {
        $args['order']->importAddressFromArray($args['address'], 'shipping')->save();
        $args['order']->addHistoryEvent('cancel_req', 'Admin has updated shipping address');
    }

    public function action_adminUpdatesOrderBillingAddress($args)
    {
        $args['order']->importAddressFromArray($args['address'], 'billing')->save();
        $args['order']->addHistoryEvent('cancel_req', 'Admin has updated billing address');
    }

    public function action_adminCreatesChangeOrder($args)
    {
        throw new BException('Not implemented yet');
    }

    public function action_adminMarksOrderForReview($args)
    {
        $args['order']->state()->overall()->setReview();
        $args['order']->addHistoryEvent('review', 'Admin user has marked the order for review');
    }

    public function action_adminMarksOrderAsLegit($args)
    {
        $args['order']->state()->overall()->setLegit();
        $args['order']->addHistoryEvent('legit', 'Admin user has marked the order as legitimate');
        $args['order']->state()->overall()->setProcessing();;
        $args['order']->save();
    }

    public function action_adminMarksOrderAsFraud($args)
    {
        $args['order']->state()->overall()->setFraud();
        $args['order']->addHistoryEvent('fraud', 'Admin user has marked the order as fraud');
        $args['order']->save();
    }

    public function action_adminChangesOrderCustomState($args)
    {
        $newState = $args['order']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['order']->addHistoryEvent('custom_state', 'Admin user has changed custom order state to "' . $label . '"');
        $args['order']->save();
    }

    /**
     * @param Sellvana_Sales_Model_Order[] $args
     */
    public function action_adminMarksOrderAsPaid($args)
    {
        $args['order']->markAsPaid();
    }

    /**
     * @param Sellvana_Sales_Model_Order[] $args
     */
    public function action_adminMarksOrderAsShipped($args)
    {
        $args['order']->shipAllShipments();
    }
}
