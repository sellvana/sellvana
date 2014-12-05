<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Wishlist_Frontend_Controller
 *
 * @property FCom_Wishlist_Model_Wishlist $FCom_Wishlist_Model_Wishlist
 * @property FCom_Catalog_Model_Product $FCom_Catalog_Model_Product
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 */
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
                $this->BEvents->fire('FCom_Wishlist_Frontend_Controller::action_index:after_add', ['model'=>$p]);
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
                switch ($post['do']) {
                    case 'add_to_cart':
                        foreach ($post['selected'] as $id) {
                            $wishlist->moveItemToCart($id);
                            $wishlist->removeItem($id);
                        }
                        $this->FCom_Sales_Model_Cart->sessionCart()->calculateTotals()->saveAllDetails();
                        break;

                    case 'remove':
                        foreach ($post['selected'] as $id) {
                            $wishlist->moveItemToCart($id);
                            $wishlist->removeItem($id);
                        }
                        break;
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

    /**
     * @param $args
     */
    public function onAddToWishlist($args)
    {
        $product = $args['product'];
        if (!$product || !$product->id()) {
            return false;
        }

        $this->FCom_Wishlist_Model_Wishlist->wishlist(true)->addItem($product->id());
    }
}
