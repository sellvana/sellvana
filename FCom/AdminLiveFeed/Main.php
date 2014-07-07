<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        if ($args['model']->isNewRecord()) {
            if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_catalog')) {
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                    [
                        'href' => 'catalog/products/form?id=' . $args['model']->get('id'),
                        'text' => $this->BLocale->_(
                                'New %s of products have been added to catalog',
                                '#' . $args['model']->get('id')
                            ),
                    ]
                );
            }
        }
    }

    public function onPrefAfterSave($args)
    {
        if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_newsletter')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                [
                    'text' => $args['model']->get('email') . ' ' . $this->BLocale->_('has subscribed to newsletter'),
                ]
            );
        }
    }

    public function onCustomerAfterSave($args)
    {
        if ($args['model']->isNewRecord()) {
            if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_customer')) {
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                    [
                        'href' => 'customers/form/?id=' . $args['model']->get('id'),
                        'text' => $this->BLocale->_(
                                '%s created an account.',
                                $args['model']->get('firstname') . ' ' . $args['model']->get(
                                    'lastname'
                                ) . '(' . $args['model']->get('email') . ')'
                            )
                    ]
                );
            }
        }
    }

    public function onReviewsAfterSave($args)
    {
        $pCustomerId = $args['model']->get('customer_id');
        $customer = $this->FCom_Customer_Model_Customer->load($pCustomerId);
        if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_product_reviews')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                [
                    'href' => 'prodreviews/form/?id=' . $args['model']->id(),
                    'text' => $this->BLocale->_(
                            '%s has review the product %s',
                            [$customer->firstname . ' ' . $customer->lastname, '#' . $args['model']->id()]
                        )
                ]
            );
        }
    }

    public function onOrderAfterSave($args)
    {
        if ($args['model']->isNewRecord()) {
            if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_sales')) {
                $customer = $this->FCom_Customer_Model_Customer->load($args['model']->get('customer_id'));
                $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send(
                    [
                        'href' => 'orders/form/?id=' . $args['model']->id(),
                        'text' => $this->BLocale->_(
                                'Order %s has been placed by %s',
                                ['#' . $args['model']->id(), $customer->firstname . ' ' . $customer->lastname]
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
        if ($this->BConfig->get('modules/FCom_AdminLiveFeed/enable_wishlist')) {
            $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                    'text' => $this->BLocale->_('Item %s has been added to a wishlist', $args['model']->get('product_name')),
                ]);
        }
    }
}
