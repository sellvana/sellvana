<?php

/**
 * Class Sellvana_Wishlist_Frontend_Controller
 *
 * @property Sellvana_Wishlist_Model_Wishlist $Sellvana_Wishlist_Model_Wishlist
 * @property Sellvana_Wishlist_Model_WishlistItem $Sellvana_Wishlist_Model_WishlistItem
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Cart $Sellvana_Sales_Model_Cart
 * @property Sellvana_Sales_Main $Sellvana_Sales_Main
 */
class Sellvana_Wishlist_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        if (!$this->Sellvana_Customer_Model_Customer->isLoggedIn()) {
            $this->forward('unauthenticated');
            return;
        }

        $this->BResponse->nocache();
        $layout = $this->BLayout;
        $this->layout('/wishlist');
        $layout->getView('breadcrumbs')->crumbs = ['home', ['label' => 'Wishlist', 'active' => true]];
        $isMultiWishlist = (bool)$this->BConfig->get('modules/Sellvana_Wishlist/multiple_wishlist');

        $wishlistsOrm = $this->Sellvana_Wishlist_Model_Wishlist->orm()
            ->where('customer_id', $this->Sellvana_Customer_Model_Customer->sessionUserId());
        if ($isMultiWishlist) {
            $wishlists = $wishlistsOrm->order_by_desc('is_default')->order_by_asc('title')->find_many_assoc();
        } else {
            $wishlists = $wishlistsOrm->where('is_default', 1)->find_many_assoc();
        }

        if (!empty($wishlists)) {
            $itemRows = $this->Sellvana_Wishlist_Model_WishlistItem->orm('wi')
                ->join('Sellvana_Catalog_Model_Product', ['p.id', '=', 'wi.product_id'], 'p')
                ->select('wi.*')
                ->select('p.id', 'product_id')
                ->where_in('wishlist_id', array_keys($wishlists))
                ->find_many();

            $wishlistItems = [];
            foreach ($itemRows as $item) {
                $wishlistItems[$item->get('wishlist_id')][] = $item;
            }

            foreach ($wishlistItems as $wId => $items) {
                $wishlists[$wId]->items = $items;
            }
        }

        $layout->getView('wishlist')->set([
                'wishlists'       => $wishlists,
                'isMultiWishlist' => $isMultiWishlist
            ]);
    }

    /**
     * Create wishlist
     */
    public function action_create__POST()
    {
        $post     = $this->BRequest->post();
        $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->create();
        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $error    = false;

        // Set model attributes
        $data = [
            'title'       => (string)$post['title'] ? : 'New Wish List',
            'is_default'  => 0,
            'customer_id' => $customer->id()
        ];

        if (!$wishlist->set($data)->save()) {
            $error = true;
        }

        if ($this->BRequest->xhr()) {
            if (!$error) {
                $r = ['success' => true, 'title' => $this->_('Create wishlist successfull.')];
            } else {
                $r = ['success' => false, 'title' => $this->_('Create wishlist failure due to system error.')];
            }

            $this->BResponse->json($r);
        } else {
            if (!$error) {
                $this->message('Create wishlist successfull.');
            } else {
                $this->message('Create wishlist failure due to system error.');
            }

            $this->BResponse->redirect('wishlist');
        }
    }

    public function action_index__POST()
    {
        $wishlistHref = $this->BApp->href('wishlist');
        $post         = $this->BRequest->post();
        $wishlist     = $this->Sellvana_Wishlist_Model_Wishlist->sessionWishlist(true);

        if ($this->BRequest->xhr()) {
            $result = [];
            $p = $this->Sellvana_Catalog_Model_Product->load($post['id']);
            if (!$p) {
                $this->BResponse->json(['title' => $this->_('Incorrect product id')]);
                return;
            }
            switch ($post['action']) {
                case 'add':
                    $wishlist->addItem($p->id());
                    $this->BEvents->fire('Sellvana_Wishlist_Frontend_Controller::action_index:after_add', ['model'=>$p]);
                    $result = [
                        'success' => true,
                        'title' => 'Added to wishlist',
                        'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> ' . htmlspecialchars($p->product_name)
                            . '<br><br><a href="' . $wishlistHref . '" class="button">'. $this->_('Go to wishlist') .'</a>'
                    ];
                    break;
                case 'remove':
                    $wishlist->removeProduct($p->id());
                    $result = [
                        'success' => true,
                        'title' => 'Removed from wishlist',
                        'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> ' . htmlspecialchars($p->product_name)
                            . '<br><br><a href="' . $wishlistHref . '" class="button">'. $this->_('Go to wishlist') .'</a>'
                    ];
                    break;
            }
            $this->BResponse->json($result);
        } else {
            if (!empty($post['selected'])) {
                list($action, $wishlistId) = explode('.', $post['do']);
                if (!empty($wishlistId) && $wishlist->id() != $wishlistId) {
                    $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->load($wishlistId);
                }

                switch ($action) {
                    case 'add_to_cart':
                        $items = $this->Sellvana_Wishlist_Model_WishlistItem->orm()->where('wishlist_id', $wishlist->id())
                            ->where_in('id', $post['selected'])->find_many();
                        $addItems = [];
                        foreach ($items as $item) {
                            $addItems[] = ['id' => $item->get('product_id')];
                        }
                        $this->Sellvana_Sales_Main->workflowAction('customerAddsItemsToCart', ['items' => $addItems]);
                        foreach ($post['selected'] as $id) {
                            $wishlist->removeItem($id);
                        }
                        $wishlistHref = $this->BApp->href('cart');
                        break;

                    case 'remove':
                        foreach ($post['selected'] as $id) {
                            $wishlist->removeItem($id);
                        }
                        break;
                    case 'move':
                        $wlId = null;
                        $wlIds = $post['wishlist_ids'];
                        foreach ($post['selected'] as $id) {
                            $wishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->load($id);

                            if (empty($wlIds[$id])) {
                                continue;
                            }
                            $wlId = $wlIds[$id];

                            $wishlistItem->set('wishlist_id', $wlId)->save();

                        }

                        if ($wlId) {
                            $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->load($wlId);
                            $this->message(sprintf('Product was moved to %s', $wishlist->get('title')));
                        }

                        $this->BResponse->redirect('wishlist');
                        break;
                }
            }
            $this->BResponse->redirect($wishlistHref);
        }
    }

    public function action_settings()
    {
        $wishlists = $this->Sellvana_Wishlist_Model_Wishlist->orm()
            ->where('customer_id', $this->Sellvana_Customer_Model_Customer->sessionUserId())
            ->order_by_desc('is_default')
            ->order_by_asc('title') 
            ->find_many_assoc();
        
        $this->layout('/wishlist/form');
        $this->view('wishlist/settings')->set([
            'wishlists' => $wishlists,
            'action' => 'settings'
        ]);
    }

    public function action_settings__POST()
    {
        $post       = $this->BRequest->post();
        $wishlists  = $post['Wishlist'];
        $deletedIds = isset($post['delete']) ? $post['delete'] : [];
        $errors     = [];
        $r          = [];

        foreach ($wishlists as $id => $wishlist) {
            $model = $this->Sellvana_Wishlist_Model_Wishlist->load($id);
            if ($model) {
                if (in_array($id, $deletedIds)) {
                    if ($model->get('is_default')) {
                        $errors[] = $this->_('Can not delete default wishlist');
                    } else {
                        $model->delete();
                    }
                } else {
                    $data = [
                        'title'      => $wishlist['title'],
                        'is_default' => intval($wishlists['is_default'] == $id)
                    ];

                    if (!$model->set($data)->save()) {
                        $errors[] = $this->_('Error while saving wishlist info');
                        return;
                    }
                }
            }
        }

        if ($this->BRequest->xhr()) {
            if ($errors) {
                $r = ['success' => false, 'title' => join("\r\n", $errors)];
            } else {
                $r = ['success' => true, 'title' => $this->_('Update wishlists successfull.')];
            }

            $this->BResponse->json($r);
        } else {
            if ($errors) {
                $this->message(join("\r\n", $errors), 'error');
            } else {
                $this->message('Update wishlists successfull.');
            }

            $this->BResponse->redirect('wishlist');
        }
    }

    /**
     * Move product to other wishlist
     */
    public function action_move()
    {
        if ($this->BRequest->csrf('referrer', 'GET')) {
            $this->message('CSRF detected', 'error');
            $this->BResponse->redirect('wishlist');
            return;
        }

        $r = [];

        $id           = (int)$this->BRequest->get('id');
        $pId          = (int)$this->BRequest->get('product');
        $wlId         = (int)$this->BRequest->get('wishlist');
        $wishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->loadOrCreate(['wishlist_id' => $wlId, 'product_id' => $pId]);

        if ($this->BRequest->xhr() && $wishlistItem) {
            $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->load($id);
            if ($wishlistItem->set('wishlist_id', $id)->save()) {
                $r = ['success' => true];
                $this->message(sprintf('Product was moved to %s', $wishlist->title));
            }

            $this->BResponse->json($r);
        }
    }

    public function action_add()
    {
        if ($this->BRequest->csrf('referrer', 'GET')) {
            $this->message('CSRF detected', 'error');
            $this->BResponse->redirect('wishlist');
            return;
        }

        $id = $this->BRequest->get('id');
        $wishlistId = $this->BRequest->get('wishlist_id');
        $p  = $this->Sellvana_Catalog_Model_Product->load($id);
        if (!$p) {
            $this->message('Invalid product', 'error');
        } else {
            $wishlist = null;
            if ($wishlistId) {
                $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->orm('w')
                    ->where('customer_id', $this->Sellvana_Customer_Model_Customer->sessionUserId())
                    ->where('id', $wishlistId)
                    ->find_one();
                if (!$wishlist) {
                    $this->message('Invalid wishlist', 'error');
                }
            } else {
                $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->sessionWishlist(true);
            }
            if ($wishlist) {
                $wishlist->addItem($id);
                $messageText = 'Product was added to wishlist.';
                if (!$this->Sellvana_Customer_Model_Customer->isLoggedIn()) {
                    $messageText .= "\nPlease note that your wishlist will be stored permanently only after you sign in.";
                }
                $this->message($messageText);
            }
        }
        $this->BResponse->redirect('wishlist');
    }

    /**
     * @param $args
     */
    public function onAddToWishlist($args)
    {
        /** @var Sellvana_Catalog_Model_Product $product */
        $product = $args['product'];
        if (!$product || !$product->id()) {
            return false;
        }

        #TODO: Method `wishlist` does not available on `Sellvana_Wishlist_Model_Wishlist`
        $this->Sellvana_Wishlist_Model_Wishlist->wishlist(true)->addItem($product->id());
    }
}
