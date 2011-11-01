<?php

class FCom_Checkout extends BClass
{
    static public function bootstrap()
    {
        BFrontController::i()
            ->route( 'GET /cart', 'FCom_Checkout_Controller.cart')
            ->route('POST /cart', 'FCom_Checkout_Controller.cart_post')
        ;

        BLayout::i()->allViews('views_frontend');
    }
}

class FCom_Checkout_Controller extends BActionController
{
    public function action_cart()
    {
        BLayout::i()->view('breadcrumbs')->crumbs = array('home', array('label'=>'Cart', 'active'=>true));
        BLayout::i()->hookView('main', 'cart');
        BResponse::i()->render();
    }

    public function action_cart_post()
    {
        $post = BRequest::i()->post();
        $cart = ACart::sessionCart();
        if (BRequest::i()->xhr()) {
            $result = array();
            switch ($post['action']) {
            case 'add':
                $p = AProduct::i()->load($post['id']);
                $cart->addProduct($post['id'], $post);
                $result = array(
                    'title' => 'Added to cart',
                    'html' => '<img src="'.$p->thumbUrl(35, 35).'" width="35" height="35" style="float:left"/> '.htmlspecialchars($p->product_name)
                        .(!empty($post['qty']) && $post['qty']>1 ? ' ('.$post['qty'].')' : '')
                        .'<br><a href="'.BApp::baseUrl().'/cart" class="button">Go to cart</a>',
                    'cnt' => $cart->itemQty(),
                );
                break;
            }
            BResponse::i()->json($result);
        } else {
            $cart->items();
            if (!empty($post['remove'])) {
                foreach ($post['remove'] as $id) {
                    $cart->removeItem($id);
                }

            }
            if (!empty($post['qty'])) {
                foreach ($post['qty'] as $id=>$qty) {
                    $item = $cart->childById('items', $id);
                    if ($item) $item->set('qty', $qty)->save();
                }
            }
            $cart->calcTotals()->save();
            BResponse::i()->redirect(BApp::baseUrl().'/cart');
        }
    }
}
