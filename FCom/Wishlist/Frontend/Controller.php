<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Wishlist_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        if (!$this->FCom_Customer_Model_Customer->isLoggedIn()) {
            $this->forward('unauthenticated');
            return;
        }
        $this->BResponse->nocache();
        $layout = $this->BLayout;
        $this->layout('/wishlist');
        $layout->view('breadcrumbs')->crumbs = ['home', ['label' => 'Wishlist', 'active' => true]];
        $wishlist = $this->FCom_Wishlist_Model_Wishlist->sessionWishlist();
        $layout->view('wishlist')->wishlist = $wishlist;
    }

    public function action_index__POST()
    {
        $wishlistHref = $this->BApp->href('wishlist');
        $post = $this->BRequest->post();
        $wishlist = $this->FCom_Wishlist_Model_Wishlist->sessionWishlist(true);
        if ($this->BRequest->xhr()) {
            $result = [];
            $p = $this->FCom_Catalog_Model_Product->load($post['id']);
            if (!$p) {
                $this->BResponse->json(['title' => "Incorrect product id"]);
                return;
            }
            switch ($post['action']) {
            case 'add':
                $wishlist->addItem($p->id());

                if ($this->BApp->m('FCom_PushServer')->run_status === BModule::LOADED
                    && $this->BConfig->get('modules/FCom_AdminLiveFeed/wishlist_recent_activity')
                ) {
                    $this->FCom_PushServer_Model_Channel->getChannel('activities_feed', true)->send([
                            'text' => $this->_('Item %s has been added to a wishlist', $p->get('product_name')),
                        ]);
                }

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
            if (!empty($post['remove'])) {
                foreach ($post['remove'] as $id) {
                    $wishlist->removeItem($id);
                }
            }
            $this->BResponse->redirect($wishlistHref);
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
        $p = $this->FCom_Catalog_Model_Product->load($id);
        if (!$p) {
            $this->message('Invalid product', 'error');
        } else {
            $this->FCom_Wishlist_Model_Wishlist->sessionWishlist(true)->addItem($id);
            $this->message('Product was added to wishlist');
        }
        $this->BResponse->redirect('wishlist');
    }

    public function onAddToWishlist($args)
    {
        $product = $args['product'];
        if (!$product || !$product->id()) {
            return false;
        }

        $this->FCom_Wishlist_Model_Wishlist->wishlist(true)->addItem($product->id());
    }
}
