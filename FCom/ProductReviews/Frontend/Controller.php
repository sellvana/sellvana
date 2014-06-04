<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductReviews_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public $formId = 'product-review';

    public function action_add()
    {
        $r = $this->BRequest->get();

        $product = $this->FCom_Catalog_Model_Product->load($r['pid']);
        if (!$product) {
            //TODO: add notification
            $this->BResponse->redirect('');
            return;
        }

        if ($this->BModuleRegistry->isLoaded('FCom_Customer') && false == $this->FCom_Customer_Model_Customer->sessionUser()) {
            $this->forward('unauthenticated');
            return;
        }
        $pr = $this->FCom_ProductReviews_Model_Review->loadWhere([
            'product_id' => $r['pid'],
            'customer_id' => $this->FCom_Customer_Model_Customer->sessionUserId()
        ]);
        if ($pr) {
            $this->BResponse->redirect($product->url());
            return;
        }
        $this->formMessages($this->formId);
        $this->view('prodreviews/review-form')->set([
            'prod'   => $product,
            'formId' => $this->formId,
            'action' => 'add',
        ]);
        $this->layout('/prodreview/form');
    }

    public function action_add__POST()
    {
        $post = $this->BRequest->post();
        //check if customer have debug
        $pr = $this->FCom_ProductReviews_Model_Review->loadWhere([
            'product_id' => (int)$post['pid'],
            'customer_id' => $this->FCom_Customer_Model_Customer->sessionUserId()
        ]);

        $product = $this->FCom_Catalog_Model_Product->load($post['pid']);
        if (!$product || empty($post['review'])) {
            $this->BResponse->redirect('');
            return;
        }
        if (!$pr) {
            if ($this->BModuleRegistry->isLoaded('FCom_Customer')) {
                $customer = $this->FCom_Customer_Model_Customer->sessionUser();
                $customerId = $customer->id();
                $post['review']['customer_id'] = $customerId;
            }

            $post['review']['product_id'] = $product->id();
            $review = $this->FCom_ProductReviews_Model_Review->create();
            $needApprove = $this->BConfig->get('modules/FCom_ProductReviews/need_approve');
            if ($valid = $review->validate($post['review'], [], $this->formId)) {
                if (!$needApprove) {
                    $post['review']['approved'] = 1;
                }
                $review->set($post['review'])->save();
                $review->notify();
            }

            $successMessage = $this->BLocale->_('Thank you for your review!');
            if ($needApprove && $valid) {
                $successMessage = $this->BLocale->_('Thank you for your review! We will check and approve this review in 24 hours.');
            }

            if ($this->BRequest->xhr()) { //ajax request
                if ($valid) {
                    $this->BResponse->json(['status' => 'success', 'message' => $successMessage]);
                } else {
                    $this->BResponse->json(['status' => 'error', 'message' => $this->getAjaxErrorMessage()]);
                }
            } else {
                if ($valid) {
                    $this->message($successMessage);
                    $url = $product->url();
                } else {
                    $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $this->formId);
                    $url = $this->BApp->href('prodreviews/add?pid=' . $product->id());
                }
                $this->BResponse->redirect($url);
            }
        }


    }

    public function action_helpful__POST()
    {
        $post = $this->BRequest->post();

        if ($this->BModuleRegistry->isLoaded('FCom_Customer') && false == $this->FCom_Customer_Model_Customer->sessionUser()) {
            $this->BResponse->json(['redirect' => $this->BApp->href('login')]);
            return;
        }

        if (empty($post['rid'])) {
            $this->BResponse->json(['error' => 'Invalid id']);
            return;
        }

        if (!empty($post['review_helpful'])) {
            $review = $this->FCom_ProductReviews_Model_Review->load($post['rid']);
            if (!$review) {
                $this->BResponse->json(['error' => 'Invalid id']);
                return;
            }
            $mark = -1;
            if ($post['review_helpful'] == 'yes') {
                $mark = 1;
            }
            $customer = $this->FCom_Customer_Model_Customer->sessionUser();
            $record = $this->FCom_ProductReviews_Model_ReviewFlag->loadWhere([
                'customer_id' => $customer->id(),
                'review_id' => $review->id(),
            ]);

            if (!$record) {
                $review->helpful($mark);
                $data = ['customer_id' => $customer->id, 'review_id' => $review->id, 'helpful' => $mark];
                $this->FCom_ProductReviews_Model_ReviewFlag->create($data)->save();
            } elseif ($record->helpful != $mark) {
                $review->helpful($mark);
                $record->set('helpful', $mark)->save();
            } else {
                $this->BResponse->json(['error' => "You've already rated this review"]);
            }


        }
    }

    public function action_offensive()
    {
        //TODO: convert to POST
        $rid = $this->BRequest->get('rid');
        if (empty($rid)) {
            $this->forward(false);
            return;
        }
        $review = $this->FCom_ProductReviews_Model_Review->load($rid);

        $customer = $this->FCom_Customer_Model_Customer->sessionUser();
        $record = $this->FCom_ProductReviews_Model_ReviewFlag->loadWhere([
            'customer_id' => $customer->id(),
            'review_id' => $review->id(),
        ]);
        if (!$record) {
            $review->offensive++;
            $review->save();
            $data = ['customer_id' => $customer->id, 'review_id' => $review->id, 'offensive' => 1];
            $this->FCom_ProductReviews_Model_ReviewFlag->create($data)->save();
        } elseif (!$record->offensive) {
            $review->offensive++;
            $review->save();
            $record->set('offensive', 1)->save();
        }
    }

    public function getAjaxErrorMessage()
    {
        $messages = $this->BSession->messages('validator-errors:' . $this->formId);
        $errorMessages = [];
        foreach ($messages as $m) {
            if (is_array($m['msg'])) {
                $errorMessages[] = $m['msg']['error'];
            } else {
                $errorMessages[] = $m['msg'];
            }
        }

        return implode("<br />", $errorMessages);
    }

    public function action_reviews_list()
    {
        $r = $this->BRequest;
        if ($r->xhr()) {
            $pid = $r->param('pid', true);
            if (!$pid) {
                $this->BDebug->error($this->BLocale->_('Invalid ID'));
                die;
            }
            if (!($product = $this->FCom_Catalog_Model_Product->load($pid))) {
                $this->BDebug->error($this->BLocale->_('Cannot load product with this id'));
                die;
            }
            $reviews = $product->reviews();
            $this->BResponse->set($this->view('prodreviews/product-reviews-list')->set([
                'reviews' => $reviews,
                'userId'  => $this->FCom_Customer_Model_Customer->sessionUserId(),
                'prod'    => $product
            ]));
        }
    }

    public function action_edit()
    {
        $r = $this->BRequest->get();
        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();
        $pr = $this->FCom_ProductReviews_Model_Review->loadWhere([
            'id'          => $r['rid'],
            'customer_id' => $customerId
        ]);
        if (!$pr) {
            $this->message('Cannot find your review, please check again', 'error', 'validator-errors:' . $this->formId);
        } else {
            $prod = $this->FCom_Catalog_Model_Product->load($pr->product_id);

            if ($this->BModuleRegistry->isLoaded('FCom_Customer') && false == $this->FCom_Customer_Model_Customer->sessionUser()) {
                $this->forward('unauthenticated');
                return;
            }

            $this->view('prodreviews/review-form')->set([
                'prod' => $prod,
                'pr' => $pr,
            ]);
        }
        $this->view('prodreviews/review-form')->set([
            'formId' => $this->formId,
            'action' => 'edit',
        ]);
        $this->formMessages($this->formId);
        $this->layout('/prodreview/form');
    }

    public function action_edit__POST()
    {
        $post = $this->BRequest->post();
        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();
        $pr = $this->FCom_ProductReviews_Model_Review->loadWhere([
            'id'          => (int)$post['rid'],
            'customer_id' => $customerId
        ]);
        $prod = $this->FCom_Catalog_Model_Product->load($pr->product_id);
        if (!$pr) {
            $this->message('Cannot load your review, please check again', 'error', 'validator-errors:' . $this->formId);
            $this->BResponse->redirect('prodreviews/edit?pr=' . $pr->id());
            return;
        }
        //$valid = $pr->set($post['review'])->save();
        $needApprove = $this->BConfig->get('modules/FCom_ProductReviews/need_approve');
        $post['review']['product_id'] = $pr->product_id;
        $post['review']['customer_id'] = $customerId;
        if ($valid = $pr->validate($post['review'], [], $this->formId)) {
            if ($needApprove) {
                $post['review']['approved'] = 0;
            }
            $pr->set($post['review'])->save();
            //$pr->notify(); //todo: confirm about send notify
        }
        $successMessage = $this->BLocale->_('Edit review successfully!');
        if ($needApprove) {
            $successMessage = $this->BLocale->_('Edit review successfully! We will check and approve this review in 24 hours.');
        }
        if ($this->BRequest->xhr()) { //ajax request
            if ($valid) {
                $this->BResponse->json(['status' => 'success', 'message' => $successMessage]);
            } else {
                $this->BResponse->json(['status' => 'error', 'message' => $this->getAjaxErrorMessage()]);
            }
        } else {
            if ($valid) {
                $this->message($successMessage);
                $url = $prod->url();
            } else {
                $this->message('Cannot save data, please fix above errors', 'error', 'validator-errors:' . $this->formId);
                $url = $this->BApp->href('prodreviews/edit?pr=' . $pr->id());
            }
            $this->BResponse->redirect($url);
        }
    }

    public function action_ajax_review()
    {
        $post = $this->BRequest->post();
        $customerId = $this->FCom_Customer_Model_Customer->sessionUserId();
        $pr = $this->FCom_ProductReviews_Model_Review->loadWhere([
            'id'          => (int)$post['rid'],
            'customer_id' => $customerId
        ]);
        if (!$pr) {
            $this->BResponse->json(['status' => 'error', 'message' => 'Cannot load your review, please check again']);
        } else {
            $this->BResponse->json($pr->as_array() + ['status' => 'success']);
        }

    }
}
