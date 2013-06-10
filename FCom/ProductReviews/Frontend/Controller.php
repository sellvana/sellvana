<?php

class FCom_ProductReviews_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public function action_add()
    {
        $r = BRequest::i()->get();

        $product = FCom_Catalog_Model_Product::i()->load($r['pid']);
        if (!$product) {
            //TODO: add notification
            BResponse::i()->redirect(BApp::href());
        }

        if (BModuleRegistry::isLoaded('FCom_Customer') && false == FCom_Customer_Model_Customer::i()->sessionUser()) {
            BResponse::i()->redirect(Bapp::href("login"));
        }

        $this->view('prodreviews/add')->pid = $product->id();
        $this->layout('/prodreviews/add');
    }

    public function action_add__POST()
    {
        $post = BRequest::i()->post();

        $product = FCom_Catalog_Model_Product::i()->load($post['pid']);
        if (!$product) {
            BResponse::i()->redirect(Bapp::baseUrl());
        }

        if (!empty($post['review'])) {
            $customerId = 0;
            if (BModuleRegistry::isLoaded('FCom_Customer')) {
                $customer = FCom_Customer_Model_Customer::i()->sessionUser();
                $customerId = $customer->id();
            }
            FCom_ProductReviews_Model_Review::i()->addNew($customerId, $product->id(), $post['review']);
        }
        BResponse::i()->redirect($product->url());
    }

    public function action_helpful__POST()
    {
        $post = BRequest::i()->post();

        if (BModuleRegistry::isLoaded('FCom_Customer') && false == FCom_Customer_Model_Customer::i()->sessionUser()) {
            BResponse::i()->json(array('redirect' => BApp::href('login')));
        }

        if (empty($post['rid'])) {
            BResponse::i()->json(array('error' => 'Invalid id'));
        }

        if (!empty($post['review_helpful'])) {
            $review = FCom_ProductReviews_Model_Review::i()->load($post['rid']);
            if (!$review) {
                BResponse::i()->json(array('error' => 'Invalid id'));
            }
            if ($post['review_helpful'] == 'yes') {
                $mark = 1;
            } else {
                $mark = -1;
            }
            $customer = FCom_Customer_Model_Customer::i()->sessionUser();
            $record = FCom_ProductReviews_Model_ReviewFlag::i()->load(array(
                'customer_id' => $customer->id, 
                'review_id' => $review->id,
            ));

            if (!$record) {
                $review->helpful($mark);
                $data = array('customer_id' => $customer->id, 'review_id' => $review->id, 'helpful' => $mark);
                FCom_ProductReviews_Model_ReviewFlag::i()->create($data)->save();
            } elseif ($record->helpful != $mark) {
                $review->helpful($mark);
                $record->set('helpful', $mark)->save();
            }
        }
    }

    public function action_offensive()
    {
        //TODO: convert to POST
        $rid = BRequest::i()->get('rid');
        if (empty($rid)) {
            $this->forward(false);
            return;
        }
        $review = FCom_ProductReviews_Model_Review::i()->load($rid);

        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        $record = FCom_ProductReviews_Model_ReviewFlag::i()->load(array(
            'customer_id' => $customer->id,
            'review_id' => $review->id
        ));
        if (!$record) {
            $review->offensive++;
            $review->save();
            $data = array('customer_id' => $customer->id, 'review_id' => $review->id, 'offensive' => 1);
            FCom_ProductReviews_Model_ReviewFlag::i()->create($data)->save();
        } elseif (!$record->offensive) {
            $review->offensive++;
            $review->save();
            $record->set('offensive', 1)->save();
        }
    }
}