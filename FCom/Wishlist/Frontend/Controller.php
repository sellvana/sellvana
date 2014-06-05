<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Wishlist_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function beforeDispatch()
    {
        if (!parent::beforeDispatch()) return false;

        $this->BResponse->nocache();

        return true;
    }

    public function authenticate($args = [])
    {
        return $this->FCom_Customer_Model_Customer->isLoggedIn() || $this->BRequest->rawPath() == '/login';
    }

    public function action_index()
    {
        $layout = $this->BLayout;
        $layout->view('breadcrumbs')->crumbs = ['home', ['label' => 'Wishlist', 'active' => true]];
        $wishlist = $this->FCom_Wishlist_Model_Wishlist->sessionWishlist();
        $layout->view('wishlist')->wishlist = $wishlist;
        $this->layout('/wishlist');
    }

    public function action_index__POST()
    {
        $wishlistHref = $this->BApp->href('wishlist');
        $post = $this->BRequest->post();
        $wishlist = $this->FCom_Wishlist_Model_Wishlist->sessionWishlist();
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
                $result = [
                    'title' => 'Added to wishlist',
                    'html' => '<img src="' . $p->thumbUrl(35, 35) . '" width="35" height="35" style="float:left"/> ' . htmlspecialchars($p->product_name)
                        . '<br><br><a href="' . $wishlistHref . '" class="button">Go to wishlist</a>'
                ];
                break;

            case 'remove':
                $wishlist->removeProduct($p->id());
                $result = [
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
            $this->message('CSRF detected');
            $this->BResponse->redirect('wishlist');
            return;
        }
        $id = $this->BRequest->get('id');
        $p = $this->FCom_Catalog_Model_Product->load($id);
        if (!$p) {
            $this->message('Invalid product', 'error');
        } else {
            $wishlist = $this->FCom_Wishlist_Model_Wishlist->sessionWishlist();
            if (!$wishlist) {
                $this->forward('unauthenticated');
                return;
            }
            $wishlist->addItem($id);
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

        $wishlist = $this->FCom_Wishlist_Model_Wishlist->wishlist();
        $wishlist->addItem($product->id());
    }
}
