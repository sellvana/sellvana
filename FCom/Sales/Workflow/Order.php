<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Workflow_Order extends FCom_Sales_Workflow_Abstract
{
    static protected $_origClass = __CLASS__;

    protected $_localHooks = [
        'customerPlacesOrder',

        'customerPostsOrderComment',

        'customerCancelsOrder',
        'customerCancelsOrderItems',
        'customerRequestsOrderShippingAddressUpdate',
        'customerRequestsOrderBillingAddressUpdate',
        'customerRequestsRMA',

        'adminPostsOrderComment',

        'adminUpdatesOrderShippingAddress',
        'adminUpdatesOrderBillingAddress',
        'adminCreatesChangeOrder',

        'adminCancelsAuthorization',
        'adminCapturesPayment',
        'adminRefundsPayment',
        'adminVoidsPayment',

        'adminCreatesShipment',
        'adminUpdatesShipment',
        'adminPrintsShippingLabels',

        'adminMarksOrderAsFraud',

        'adminCancelsOrder',
        'adminCancelsOrderItems',
        'adminCreatesRMA',
        'adminApprovesRMA',
        'adminCreatesReturnDocument',

        'adminChangesCustomState',
    ];

    public function customerPlacesOrder($args)
    {

    }

    public function customerPostsOrderComment($args)
    {
    }


    public function customerCancelsOrder($args)
    {
    }


    public function customerCancelsOrderItems($args)
    {
    }


    public function customerRequestsOrderShippingAddressUpdate($args)
    {
    }


    public function customerRequestsOrderBillingAddressUpdate($args)
    {
    }


    public function customerRequestsRMA($args)
    {
    }


    public function adminPostsOrderComment($args)
    {
    }


    public function adminUpdatesOrderShippingAddress($args)
    {
    }


    public function adminUpdatesOrderBillingAddress($args)
    {
    }


    public function adminCreatesChangeOrder($args)
    {
    }


    public function adminCancelsAuthorization($args)
    {
    }


    public function adminCapturesPayment($args)
    {
    }


    public function adminRefundsPayment($args)
    {
    }


    public function adminVoidsPayment($args)
    {
    }


    public function adminCreatesShipment($args)
    {
    }


    public function adminUpdatesShipment($args)
    {
    }


    public function adminPrintsShippingLabels($args)
    {
    }


    public function adminMarksOrderAsFraud($args)
    {
    }


    public function adminCancelsOrder($args)
    {
    }


    public function adminCancelsOrderItems($args)
    {
    }


    public function adminCreatesRMA($args)
    {
    }


    public function adminApprovesRMA($args)
    {
    }


    public function adminCreatesReturnDocument($args)
    {
    }


    public function adminChangesCustomState($args)
    {
    }
}
