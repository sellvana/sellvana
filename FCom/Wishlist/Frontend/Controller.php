<?php

class FCom_Wishlist_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args = [])
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath() == '/login';
    }


    public function action_index()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = ['home', ['label' => 'Wishlist', 'active' => true]];
        $wishlist = FCom_Wishlist_Model_Wishlist::i()->sessionWishlist();
        $layout->view('wishlist')->wishlist = $wishlist;
        $this->layout('/wishlist');
    }

    public function action_index__POST()
    {
        $wishlistHref = BApp::href('wishlist');
        $post = BRequest::i()->post();
        $wishlist = FCom_Wishlist_Model_Wishlist::i()->sessionWishlist();
        if (BRequest::i()->xhr()) {
            $result = [];
            switch ($post['action']) {
            case 'add':
                $p = FCom_Catalog_Model_Product::i()->load($post['id']);
                if (!$p) {
                    BResponse::i()->json(['title' => "Incorrect product id"]);
                    return;
                }
                $wishlist->addItem($p->id());
                $result = [
                    'title' => 'Added to wishlist',
                    'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> ' . htmlspecialchars($p->product_name)
                        . '<br><br><a href="' . $wishlistHref . '" class="button">Go to wishlist</a>'
                ];
                break;
            }
            BResponse::i()->json($result);
        } else {
            if (!empty($post['remove'])) {
                foreach ($post['remove'] as $id) {
                    $wishlist->removeItem($id);
                }
            }
            BResponse::i()->redirect($wishlistHref);
        }
    }

    public function action_add()
    {
        $id = BRequest::i()->get('id');
        $p = FCom_Catalog_Model_Product::i()->load($id);
        if (!$p) {
            $this->message('Invalid product', 'error');
        } else {
            $wishlist = FCom_Wishlist_Model_Wishlist::i()->sessionWishlist();
            if (!$wishlist) {
                $this->forward('unauthenticated');
                return;
            }
            $wishlist->addItem($id);
            $this->message('Product was added to wishlist');
        }
        BResponse::i()->redirect('wishlist');
    }

    public static function onAddToWishlist($args)
    {
        $product = $args['product'];
        if (!$product || !$product->id()) {
            return false;
        }

        $wishlist = FCom_Wishlist_Model_Wishlist::i()->wishlist();
        $wishlist->addItem($product->id());
    }
}
