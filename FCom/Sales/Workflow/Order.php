<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Order extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerPlacesOrder',

        'adminPlacesOrder',

        'adminUpdatesOrderShippingAddress',
        'adminUpdatesOrderBillingAddress',
        'adminCreatesChangeOrder',

        'adminMarksOrderForReview',
        'adminMarksOrderAsLegit',
        'adminMarksOrderAsFraud',

        'adminChangesCustomState',
    ];

    public function customerPlacesOrder($args)
    {
        $order = $args['result']['order'];
        $order->state()->overall()->setPlaced();
        $order->addHistoryEvent('placed', 'Order was placed by a customer');

        if ($this->BConfig->get('modules/FCom_Sales/review_all_orders')) {
            $order->state()->overall()->setReview();
            $order->addHistoryEvent('auto_review', 'Order was sent for review as per default policy');
        }

        $order->save();
    }

    public function adminPlacesOrder($args)
    {
        $args['order']->state()->overall()->setPlaced();
        $args['order']->addHistoryEvent('placed', 'Order was placed by an admin user');

        $args['order']->save();
    }

    public function adminUpdatesOrderShippingAddress($args)
    {
        $args['order']->importAddressFromArray($args['address'], 'shipping')->save();
        $args['order']->addHistoryEvent('cancel_req', 'Admin has updated shipping address');
    }

    public function adminUpdatesOrderBillingAddress($args)
    {
        $args['order']->importAddressFromArray($args['address'], 'billing')->save();
        $args['order']->addHistoryEvent('cancel_req', 'Admin has updated billing address');
    }

    public function adminCreatesChangeOrder($args)
    {
        throw new BException('Not implemented yet');
    }

    public function adminMarksOrderForReview($args)
    {
        $args['order']->state()->overall()->setReview();
        $args['order']->addHistoryEvent('review', 'Admin user has marked the order for review');
    }

    public function adminMarksOrderAsLegit($args)
    {
        $args['order']->state()->overall()->setLegit();
        $args['order']->addHistoryEvent('legit', 'Admin user has marked the order as legitimate');
        $args['order']->state()->overall()->setProcessing();;
        $args['order']->save();
    }

    public function adminMarksOrderAsFraud($args)
    {
        $args['order']->state()->overall()->setFraud();
        $args['order']->addHistoryEvent('fraud', 'Admin user has marked the order as fraud');
        $args['order']->save();
    }

    public function adminChangesOrderCustomState($args)
    {
        $newState = $args['order']->state()->custom()->setState($args['state']);
        $label = $newState->getValueLabel();
        $args['order']->addHistoryEvent('custom_state', 'Admin user has changed custom order state to "' . $label . '"');
        $args['order']->save();
    }
}
