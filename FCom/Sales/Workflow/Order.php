<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Order extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerPlacesOrder',

        'customerCancelsOrder',
        'customerCancelsOrderItems',

        'adminPlacesOrder',

        'adminCancelsOrder',
        'adminCancelsOrderItems',

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
        $args['order']->state()->overall()->setPlaced();
        $args['order']->addHistoryEvent('placed', 'Order was placed by a customer');

        if ($this->BConfig->get('modules/FCom_Sales/review_all_orders')) {
            $args['order']->state()->overall()->setReview();
            $args['order']->addHistoryEvent('auto_review', 'Order was sent for review as per default policy');
        }

        $args['order']->save();
    }

    public function customerCancelsOrder($args)
    {
        $args['order']->state()->overall()->setCancelRequested();
        $args['order']->addHistoryEvent('cancel_req', 'Customer has requested order cancellation');
        $args['order']->save();
    }

    public function customerCancelsOrderItems($args)
    {
        foreach ($args['items'] as $item) {
            $item->state()->overall()->setCancelRequested();
            $item->addHistoryEvent('cancel_req', 'Customer has requested item cancellation');
            $item->save();
        }
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
    }

    public function adminMarksOrderAsFraud($args)
    {
        $args['order']->state()->overall()->setFraud();
        $args['order']->addHistoryEvent('fraud', 'Admin user has marked the order as fraud');
    }

    public function adminCancelsOrder($args)
    {
        $args['order']->state()->overall()->setCanceled();
        $args['order']->save();
    }

    public function adminCancelsOrderItems($args)
    {
        foreach ($args['items'] as $item) {
            $item->state()->overall()->setCanceled();
            $item->addHistoryEvent('canceled', 'Admin has canceled item');
            $item->save();
        }
    }

    public function adminChangesCustomState($args)
    {
        $args['order']->state()->custom()->setState($args['state']);
        $args['order']->addHistoryEvent('fraud', 'Admin user has changed custom order state to "' . $args['state'] . '"');
    }
}
