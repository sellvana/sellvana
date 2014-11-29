<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_AdminLiveFeed_Main
 *
 * @property FCom_PushServer_Model_Client $FCom_PushServer_Model_Client
 * @property FCom_PushServer_Model_Channel $FCom_PushServer_Model_Channel
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */
class FCom_AdminLiveFeed_Main extends BCLass
{
    public function onGetHeaderNotifications()
    {
        if ($this->BModuleRegistry->isLoaded('FCom_PushServer')) {
            $this->FCom_PushServer_Model_Client->sessionClient()->subscribe('activities_feed');
        }
    }

    public function onProductAfterSave($args)
    {
        /** @var FCom_Catalog_Model_Product $model */
        $model = $args['model'];
        if ($model->isNewRecord()) {
            if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_catalog')) {
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                    [
                        'href' => 'catalog/products/form?id=' . $model->id(),
                        'text' => $this->BLocale->_(
                                'New %s of products have been added to catalog',
                                '#' . $model->id()
                            ),
                    ]
                );
            }
        }
    }

    public function onPrefAfterSave($args)
    {
        /** @var FCom_Email_Model_Pref $model */
        $model = $args['model'];
        if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_newsletter')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                [
                    'text' => $model->email . ' ' . $this->BLocale->_('has subscribed to newsletter'),
                ]
            );
        }
    }

    public function onCustomerAfterSave($args)
    {
        /** @var FCom_Customer_Model_Customer $model */
        $model = $args['model'];
        if ($model->isNewRecord()) {
            if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_customer')) {
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                    [
                        'href' => 'customers/form/?id=' . $model->id(),
                        'text' => $this->BLocale->_(
                                '%s created an account.',
                                $model->firstname . ' ' . $model->lastname . '(' . $model->email . ')'
                            )
                    ]
                );
            }
        }
    }

    public function onReviewsAfterSave($args)
    {
        /** @var FCom_ProductReviews_Model_Review $model */
        $model = $args['model'];
        $pCustomerId = $model->customer_id;
        $customer = $this->FCom_Customer_Model_Customer->load($pCustomerId);
        if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_product_reviews')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                [
                    'href' => 'prodreviews/form/?id=' . $model->id(),
                    'text' => $this->BLocale->_(
                            '%s has review the product %s',
                            [$customer->firstname . ' ' . $customer->lastname, '#' . $model->id()]
                        )
                ]
            );
        }
    }

    public function onOrderAfterSave($args)
    {
        /** @var FCom_Sales_Model_Order $model */
        $model = $args['model'];
        if ($model->isNewRecord()) {
            if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_sales')) {
                $customer = $this->FCom_Customer_Model_Customer->load($model->get('customer_id'));
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                    [
                        'href' => 'orders/form/?id=' . $model->id(),
                        'text' => $this->BLocale->_(
                                'Order %s has been placed by %s',
                                ['#' . $model->id(), $customer->firstname . ' ' . $customer->lastname]
                            ),
                    ]
                );
            }
        }
    }

    public function onSearch($args)
    {
        if ( $this->BConfig->get('modules/FCom_AdminLiveFeed/enable_catalog')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                    'text' => $this->BLocale->_('The term %s has been searched', $args['query']),
                ]);
        }
    }

    public function onWishlistAfterAdd($args)
    {
        /** @var FCom_Catalog_Model_Product $model */
        $model = $args['model'];
        if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_wishlist')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                    'text' => $this->BLocale->_('Item %s has been added to a wishlist', $model->product_name),
                ]);
        }
    }
}
