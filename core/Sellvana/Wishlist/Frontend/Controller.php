<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        $layout->view('breadcrumbs')->crumbs = ['home', ['label' => 'Wishlist', 'active' => true]];

        $wishlists = $this->Sellvana_Wishlist_Model_Wishlist->orm()
            ->where('customer_id', $this->Sellvana_Customer_Model_Customer->sessionUserId())
            ->order_by_desc('is_default')
            ->order_by_asc('title')
            ->find_many_assoc();

        $itemRows = $this->Sellvana_Wishlist_Model_WishlistItem->orm('wi')
            ->join('Sellvana_Catalog_Model_Product', ['p.id', '=', 'wi.product_id'], 'p')
            ->select('wi.*')
            ->select('p.id', 'product_id')
            ->where_in('wishlist_id', array_keys($wishlists))
            ->find_many();

        foreach ($itemRows as $item) {
            $wishlistItems[$item->get('wishlist_id')][] = $item;
        }
        foreach ($wishlistItems as $wId => $items) {
            $wishlists[$wId]->items = $items;
        }

        $layout->view('wishlist')->wishlists = $wishlists;
    }

    /**
     * Create wishlist
     * 
     * @return Json
     */
    public function action_create__POST()
    {
        $wishlistHref = $this->BApp->href('wishlist/create');
        $post         = $this->BRequest->post();
        $wishlist     = $this->Sellvana_Wishlist_Model_Wishlist->create();
        $customer     = $this->Sellvana_Customer_Model_Customer->sessionUser();

        if ($this->BRequest->xhr()) {
            $r = [];

            // Set model attributes
            $wishlist->title       = $post['title'];
            $wishlist->is_default  = 0;
            $wishlist->customer_id = $customer->id();

            if ($wishlist->save()) {
                $r = [
                    'success' => true,
                    'title' => 'Create wishlist successfull.'
                ];
            }

            $this->message('Create wishlist successfull.');
            $this->BResponse->json($r);
        }
    }

    public function action_settings__POST()
    {
        $post = $this->BRequest->post();
        if ($this->BRequest->xhr()) {
            $wishlists  = $post['Wishlist'];
            $deletedIds = $post['delete'];

            foreach ($wishlists as $id => $wishlist) {
                $model = $this->Sellvana_Wishlist_Model_Wishlist->load($id);
                if ($model) {
                    if (in_array($id, $deletedIds)) {
                        $model->delete();
                    } else {
                        $model->title      = $wishlist['title'];
                        $model->is_default = $wishlist['is_default'];

                        if ($wishlists['is_default'] && $wishlists['is_default'] == $id)
                            $model->is_default = 1;
                        else $model->is_default = 0;

                        if (!$model->save()) {
                            $this->message('Update wishlists fail deal to system error.');
                            $this->BResponse->json(['success' => false, 'title' => 'Update wishlists fail deal to system error.']);
                        }
                    }
                }
            }

            $this->message('Update wishlists successfull.');
            $this->BResponse->json(['success' => true, 'title' => 'Update wishlists successfull.']);
        }
    }

    public function action_index__POST()
    {
        $wishlistHref = $this->BApp->href('wishlist');
        $post = $this->BRequest->post();
        $wishlist = $this->Sellvana_Wishlist_Model_Wishlist->sessionWishlist(true);

        if ($this->BRequest->xhr()) {
            $result = [];
            $p = $this->Sellvana_Catalog_Model_Product->load($post['id']);
            if (!$p) {
                $this->BResponse->json(['title' => "Incorrect product id"]);
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
                            . '<br><br><a href="' . $wishlistHref . '" class="button">Go to wishlist</a>'
                    ];
                    break;
                case 'remove':
                    $wishlist->removeProduct($p->id());
                    $result = [
                        'success' => true,
                        'title' => 'Removed from wishlist',
                        'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> ' . htmlspecialchars($p->product_name)
                            . '<br><br><a href="' . $wishlistHref . '" class="button">Go to wishlist</a>'
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
                }
            }
            $this->BResponse->redirect($wishlistHref);
        }
    }

    /**
     * Move product to other wishlist
     * 
     * @return Json
     */
    public function action_move()
    {
        if ($this->BRequest->csrf('referrer', 'GET')) {
            $this->message('CSRF detected', 'error');
            $this->BResponse->redirect('wishlist');
            return;
        }

        $r = [];

        $id           = $this->BRequest->get('id');
        $wishlist     = $this->Sellvana_Wishlist_Model_Wishlist->load($id);
        $wishlistItem = $this->Sellvana_Wishlist_Model_WishlistItem->loadOrCreate([
                              'wishlist_id' => $this->BRequest->get('wishlist'), 
                              'product_id' => $this->BRequest->get('product')
                          ]);

        if ($this->BRequest->xhr() && $wishlistItem) {
            $wishlistItem->wishlist_id = $wishlist->id();

            if ($wishlistItem->save()) {
                $r = ['success' => true];
            }

            $this->message('Product was moved to ' . $wishlist->title);
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
        $p = $this->Sellvana_Catalog_Model_Product->load($id);
        if (!$p) {
            $this->message('Invalid product', 'error');
        } else {
            $this->Sellvana_Wishlist_Model_Wishlist->sessionWishlist(true)->addItem($id);
            $this->message('Product was added to wishlist');
        }
        $this->BResponse->redirect('wishlist');
    }

    /**
     * @param $args
     */
    public function onAddToWishlist($args)
    {
        $product = $args['product'];
        if (!$product || !$product->id()) {
            return false;
        }

        $this->Sellvana_Wishlist_Model_Wishlist->wishlist(true)->addItem($product->id());
    }
}
