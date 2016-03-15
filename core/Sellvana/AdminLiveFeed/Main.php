<?php

/**
 * Class Sellvana_AdminLiveFeed_Main
 *
 * @property FCom_PushServer_Model_Client     $FCom_PushServer_Model_Client
 * @property FCom_PushServer_Model_Channel    $FCom_PushServer_Model_Channel
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property FCom_Admin_Model_Role            $FCom_Admin_Model_Role
 */
class Sellvana_AdminLiveFeed_Main extends BCLass
{
    /**
     * @var bool
     */
    protected $_disabled = false;

    public function bootstrap()
    {
        $this->FCom_Admin_Model_Role->createPermission([
            'settings/Sellvana_AdminLiveFeed' => 'Admin Live Feed Settings',
        ]);
    }

    public function disable($flag = true)
    {
        $this->_disabled = $flag;
        return $this;
    }

    public function onGetHeaderNotifications()
    {
        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('activities_feed');
        }
    }

    public function onProductAfterSave($args)
    {
        if ($this->_disabled) {
            return;
        }
        /** @var Sellvana_Catalog_Model_Product $model */
        $model = $args['model'];
        if ($model->isNewRecord()) {
            if ($this->BConfig->get('modules/Sellvana_AdminLiveFeed/enable_catalog')) {
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                    'href' => 'catalog/products/form?id=' . $model->id(),
                    'content' => $this->_(
                        'New %s of products have been added to catalog',
                        '#' . $model->id()
                    ),
                ]);
            }
        }
    }

    public function onPrefAfterSave($args)
    {
        if ($this->_disabled) {
            return;
        }
        /** @var Sellvana_Email_Model_Pref $model */
        $model = $args['model'];
        if ($this->BConfig->get('modules/Sellvana_AdminLiveFeed/enable_newsletter')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                'content' => $model->email . ' ' . $this->_('has subscribed to newsletter'),
            ]);
        }
    }

    public function onCustomerAfterSave($args)
    {
        if ($this->_disabled) {
            return;
        }
        /** @var Sellvana_Customer_Model_Customer $model */
        $model = $args['model'];
        if ($model->isNewRecord()) {
            if ($this->BConfig->get('modules/Sellvana_AdminLiveFeed/enable_customer')) {
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                    'href' => 'customers/form/?id=' . $model->id(),
                    'content' => $this->_(
                        '%s created an account.',
                        $model->firstname . ' ' . $model->lastname . '(' . $model->email . ')'
                    ),
                ]);
            }
        }
    }

    public function onReviewsAfterSave($args)
    {
        if ($this->_disabled) {
            return;
        }
        /** @var Sellvana_ProductReviews_Model_Review $model */
        $model = $args['model'];
        $pCustomerId = $model->customer_id;
        $customer = $this->Sellvana_Customer_Model_Customer->load($pCustomerId);
        if ($this->BConfig->get('modules/Sellvana_AdminLiveFeed/enable_product_reviews')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                'href' => 'prodreviews/form/?id=' . $model->id(),
                'content' => $this->_(
                    '%s has review the product %s',
                    [$customer->firstname . ' ' . $customer->lastname, '#' . $model->id()]
                ),
            ]);
        }
    }

    public function onOrderPlaced($args)
    {
        if ($this->_disabled) {
            return;
        }
        if (!$this->BConfig->get('modules/Sellvana_AdminLiveFeed/enable_sales')) {
            return;
        }
        /** @var Sellvana_Sales_Model_Order_State_Overall $newState */
        $newState = $args['new_state'];
        if ($newState->getValue() !== Sellvana_Sales_Model_Order_State_Overall::PLACED) {
            return;
        }
        /** @var Sellvana_Sales_Model_Order $order */
        $order = $newState->getContext()->getModel();
        $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
            'href' => 'orders/form/?id=' . $order->id(),
            'content' => $this->_(
                'Order #%s has been placed by %s',
                [$order->get('unique_id'), $order->fullName('billing')]
            ),
        ]);
    }

    public function onSearch($args)
    {
        if ($this->_disabled) {
            return;
        }
        if ($this->BConfig->get('modules/Sellvana_AdminLiveFeed/enable_catalog')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                'content' => $this->_('The term %s has been searched', $args['query']),
            ]);
        }
    }

    public function onWishlistAfterAdd($args)
    {
        if ($this->_disabled) {
            return;
        }
        /** @var Sellvana_Catalog_Model_Product $model */
        $model = $args['model'];
        if ($this->BConfig->get('modules/Sellvana_AdminLiveFeed/enable_wishlist')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                'content' => $this->_('Item %s has been added to a wishlist', $model->product_name),
            ]);
        }
    }
}
