<?php

class FCom_ProductReviews_Frontend_Controller extends FCom_Frontend_Frontend_Controller_Abstract
{
    public $formId = 'product-review';

    public function action_add()
    {
        $r = BRequest::i()->get();

        $product = FCom_Catalog_Model_Product::i()->load($r['pid']);
        if (!$product) {
            //TODO: add notification
            BResponse::i()->redirect(BApp::href());
        }

        if (BModuleRegistry::i()->isLoaded('FCom_Customer') && false == FCom_Customer_Model_Customer::i()->sessionUser()) {
            $this->forward('unauthenticated');
            return;
        }

        $this->formMessages();
        $this->view('prodreviews/review-form')->set(array('prod' => $product, 'formId' => $this->formId));
        $this->layout('/prodreview/add');
    }

    public function action_add__POST()
    {
        $post = BRequest::i()->post();

        $product = FCom_Catalog_Model_Product::i()->load($post['pid']);
        if (!$product || empty($post['review'])) {
            BResponse::i()->redirect('');
        }

        if (BModuleRegistry::i()->isLoaded('FCom_Customer')) {
            $customer = FCom_Customer_Model_Customer::i()->sessionUser();
            $customerId = $customer->id();
            $post['review']['customer_id'] = $customerId;
        }

        $post['review']['product_id'] = $product->id();
        $review = FCom_ProductReviews_Model_Review::i()->create();
        $needApprove = BConfig::i()->get('modules/FCom_ProductReviews/need_approve');
        if ($valid = $review->validate($post['review'], array(), $this->formId)) {
            if (!$needApprove) {
                $post['review']['approved'] = 1;
            }
            $review->set($post['review'])->save();
            $review->notify();
        }

        $successMessage = BLocale::_('Thank you for your review!');
        if ($needApprove && $valid) {
            $successMessage = BLocale::_('Thank you for your review! We will check and approve this review in 24 hours.');
        }

        if (BRequest::i()->xhr()) { //ajax request
            if ($valid) {
                BResponse::i()->json(array('status' => 'success', 'message' => $successMessage));
            } else {
                BResponse::i()->json(array('status' => 'error', 'message' => $this->getAjaxErrorMessage()));
            }
        } else {
            if ($valid) {
                BSession::i()->addMessage($successMessage, 'success', 'frontend');
                $url = $product->url();
            } else {
                BSession::i()->addMessage(BLocale::_('Cannot save data, please fix above errors'), 'error', 'validator-errors:'.$this->formId);
                $url = BApp::href('prodreviews/add?pid='.$product->id());
            }
            BResponse::i()->redirect($url);
        }
    }

    public function action_helpful__POST()
    {
        $post = BRequest::i()->post();

        if (BModuleRegistry::i()->isLoaded('FCom_Customer') && false == FCom_Customer_Model_Customer::i()->sessionUser()) {
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
            $mark = -1;
            if ($post['review_helpful'] == 'yes') {
                $mark = 1;
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
            } else {
                BResponse::i()->json(array('error' => "You've already rated this review"));
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

    public function getAjaxErrorMessage()
    {
        $messages = BSession::i()->messages('validator-errors:'.$this->formId);
        $errorMessages = array();
        foreach($messages as $m) {
            if (is_array($m['msg']))
                $errorMessages[] = $m['msg']['error'];
            else
                $errorMessages[] = $m['msg'];
        }

        return implode("<br />", $errorMessages);
    }

    public function action_reviews_list()
    {
        $r = BRequest::i();
        if ($r->xhr()) {
            $pid = $r->param('pid', true);
            if (!$pid) {
                BDebug::error(BLocale::_('Invalid ID'));
                die;
            }
            if (!($product = FCom_Catalog_Model_Product::i()->load($pid))) {
                BDebug::error(BLocale::_('Cannot load product with this id'));
                die;
            }
            $reviews = $product->reviews();
            BResponse::i()->set($this->view('prodreviews/product-reviews-list')->set('reviews', $reviews));
        }
    }
}
