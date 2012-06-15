<?php

class FCom_Wishlist_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function authenticate($args=array())
    {
        return FCom_Customer_Model_Customer::i()->isLoggedIn() || BRequest::i()->rawPath()=='/login';
    }


    public function action_wishlist()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Wishlist', 'active'=>true));
        $wishlist = FCom_Wishlist_Model_Wishlist::i()->wishlist();
        $layout->view('wishlist')->wishlist = $wishlist;
        $this->layout('/wishlist');
        BResponse::i()->render();
    }

    public function action_wishlist_post()
    {
        $wishlistHref = BApp::href('wishlist');
        $post = BRequest::i()->post();
        $wishlist = FCom_Wishlist_Model_Wishlist::i()->wishlist();
        if (BRequest::i()->xhr()) {
            $result = array();
            switch ($post['action']) {
            case 'add':
                $p = FCom_Catalog_Model_Product::i()->load($post['id']);
                if (!$p){
                    BResponse::i()->json(array('title'=>"Incorrect product id"));
                    return;
                }
                $wishlist->addItem($p->id());
                $result = array(
                    'title' => 'Added to wishlist',
                    'html' => '<img src="'.$p->thumbUrl(35, 35).'" width="35" height="35" style="float:left"/> '.htmlspecialchars($p->product_name)
                        .'<br><br><a href="'.$wishlistHref.'" class="button">Go to wishlist</a>'
                );
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
}
