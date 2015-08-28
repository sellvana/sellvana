<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_ProductReviews_Frontend_Controller
 *
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_ProductReviews_Model_Review $Sellvana_ProductReviews_Model_Review
 * @property Sellvana_ProductReviews_Model_ReviewFlag $Sellvana_ProductReviews_Model_ReviewFlag
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 * @property Sellvana_Sales_Model_Order $Sellvana_Sales_Model_Order
 */
class Sellvana_ProductReviews_Frontend_Controller extends FCom_Frontend_Controller_Abstract
{
    public $formId = 'product-review';

    public function action_index()
    {
        $p = $this->BRequest->param('product');
        if ($p === '' || is_null($p)) {
            $this->forward(false);
            return $this;
        }
        $product = $this->Sellvana_Catalog_Model_Product->load($p, 'url_key');
        if (!$product) {
            $this->forward(false);
            return $this;
        }
        if ($product->isDisabled()) {
            $this->forward(false);
            return $this;
        }

        $this->layout('/prodreview/index');
        $this->BApp->set('current_product', $product);
        $this->view('prodreviews/product-details')->set([
            'product' => $product,
            'type' => 'full'
        ]);
    }

    public function action_add()
    {
        $r = $this->BRequest->get();

        $product = $this->Sellvana_Catalog_Model_Product->load($r['pid']);
        if (!$product) {
            //TODO: add notification
            $this->BResponse->redirect('');
            return;
        }

        if ($this->BModuleRegistry->isLoaded('Sellvana_Customer') && false == $this->Sellvana_Customer_Model_Customer->sessionUser()) {
            $this->forward('unauthenticated');
            return;
        }
        $pr = $this->Sellvana_ProductReviews_Model_Review->loadWhere([
            'product_id' => $r['pid'],
            'customer_id' => $this->Sellvana_Customer_Model_Customer->sessionUserId()
        ]);
        if ($pr) {
            $this->BResponse->redirect($product->url());
            return;
        }
        $this->layout('/prodreview/form');
        $this->formMessages($this->formId);
        $this->view('prodreviews/review-form')->set([
            'prod'   => $product,
            'formId' => $this->formId,
            'action' => 'add',
        ]);
    }

    public function action_add__POST()
    {
        $post = $this->BRequest->post();
        //check if customer have debug
        $pr = $this->Sellvana_ProductReviews_Model_Review->loadWhere([
            'product_id' => (int)$post['pid'],
            'customer_id' => $this->Sellvana_Customer_Model_Customer->sessionUserId()
        ]);

        $product = $this->Sellvana_Catalog_Model_Product->load($post['pid']);
        if (!$product || empty($post['review'])) {
            $this->BResponse->redirect('');
            return;
        }
        if (!$pr) {
            if ($this->BModuleRegistry->isLoaded('Sellvana_Customer')) {
                $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
                $customerId = $customer->id();
                $post['review']['customer_id'] = $customerId;
            }

            $post['review']['product_id'] = $product->id();
            $review = $this->Sellvana_ProductReviews_Model_Review->create();
            $needApprove = $this->BConfig->get('modules/Sellvana_ProductReviews/need_approve');
            if ($valid = $review->validate($post['review'], [], $this->formId)) {
                if (!$needApprove) {
                    $post['review']['approved'] = 1;
                }
                $review->set($post['review']);

                if ($this->BModuleRegistry->isLoaded('Sellvana_Sales')) {
                    $orders = $this->Sellvana_Sales_Model_Order->orm('o')
                        ->join('Sellvana_Sales_Model_Order_Item', ['o.id', '=', 'oi.order_id'], 'oi')
                        ->where('oi.product_id', $product->get('id'))
                        ->where('o.state_overall', Sellvana_Sales_Model_Order_State_Overall::COMPLETE)->find_many();

                    if(count($orders)) {
                        $review->set('verified_purchase', 1);
                    }
                }

                $review->save();
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

        if ($this->BModuleRegistry->isLoaded('Sellvana_Customer') && false == $this->Sellvana_Customer_Model_Customer->sessionUser()) {
            $this->BResponse->json(['redirect' => $this->BApp->href('login')]);
            return;
        }

        if (empty($post['rid'])) {
            $this->BResponse->json(['error' => 'Invalid id']);
            return;
        }

        if (!empty($post['review_helpful'])) {
            $review = $this->Sellvana_ProductReviews_Model_Review->load($post['rid']);
            if (!$review) {
                $this->BResponse->json(['error' => 'Invalid id']);
                return;
            }
            $mark = 0;
            if ($post['review_helpful'] == 'yes') {
                $mark = 1;
            }
            $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
            $record = $this->Sellvana_ProductReviews_Model_ReviewFlag->loadWhere([
                'customer_id' => $customer->id(),
                'review_id' => $review->id(),
            ]);
            /** @var Sellvana_ProductReviews_Model_ReviewFlag $record */

            if (!$record) {
                $review->helpful($mark, true);
                $data = ['customer_id' => $customer->id, 'review_id' => $review->id, 'helpful' => $mark];
                $this->Sellvana_ProductReviews_Model_ReviewFlag->create($data)->save();
            } elseif ($record->helpful != $mark) {
                $review->helpful($mark, false);
                $record->set('helpful', $mark)->save();
            } else {
                $this->BResponse->json(['error' => "You've already rated this review"]);
            }


        }
    }

    public function action_offensive__POST()
    {
        $rid = $this->BRequest->post('rid');
        if (empty($rid)) {
            $this->forward(false);
            return;
        }
        $review = $this->Sellvana_ProductReviews_Model_Review->load($rid);

        $customer = $this->Sellvana_Customer_Model_Customer->sessionUser();
        $record = $this->Sellvana_ProductReviews_Model_ReviewFlag->loadWhere([
            'customer_id' => $customer->id(),
            'review_id' => $review->id(),
        ]);
        /** @var Sellvana_ProductReviews_Model_ReviewFlag $record */
        if (!$record) {
            $review->offensive++;
            $review->save();
            $data = ['customer_id' => $customer->id, 'review_id' => $review->id, 'offensive' => 1];
            $this->Sellvana_ProductReviews_Model_ReviewFlag->create($data)->save();
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
            if (!($product = $this->Sellvana_Catalog_Model_Product->load($pid))) {
                $this->BDebug->error($this->BLocale->_('Cannot load product with this id'));
                die;
            }
            $reviews = $product->reviews();
            $this->BResponse->set($this->view('prodreviews/product-reviews-list')->set([
                'reviews' => $reviews,
                'userId'  => $this->Sellvana_Customer_Model_Customer->sessionUserId(),
                'prod'    => $product
            ]));
        }
    }

    public function action_edit()
    {
        $r = $this->BRequest->get();
        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        /** @var Sellvana_ProductReviews_Model_Review $pr */
        $pr = $this->Sellvana_ProductReviews_Model_Review->loadWhere([
            'id'          => $r['rid'],
            'customer_id' => $customerId
        ]);
        if (!$pr) {
            $this->layout('/prodreview/form');
            $this->message('Cannot find your review, please check again', 'error', 'validator-errors:' . $this->formId);
        } else {
            $prod = $this->Sellvana_Catalog_Model_Product->load($pr->product_id);

            if ($this->BModuleRegistry->isLoaded('Sellvana_Customer') && false == $this->Sellvana_Customer_Model_Customer->sessionUser()) {
                $this->forward('unauthenticated');
                return;
            }

            $this->layout('/prodreview/form');
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
    }

    public function action_edit__POST()
    {
        $post = $this->BRequest->post();
        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        /** @var Sellvana_ProductReviews_Model_Review $pr */
        $pr = $this->Sellvana_ProductReviews_Model_Review->loadWhere([
            'id'          => (int)$post['rid'],
            'customer_id' => $customerId
        ]);
        $prod = $this->Sellvana_Catalog_Model_Product->load($pr->product_id);
        if (!$pr) {
            $this->message('Cannot load your review, please check again', 'error', 'validator-errors:' . $this->formId);
            $this->BResponse->redirect('prodreviews/edit?pr=' . $pr->id());
            return;
        }
        //$valid = $pr->set($post['review'])->save();
        $needApprove = $this->BConfig->get('modules/Sellvana_ProductReviews/need_approve');
        $post['review']['product_id'] = $pr->product_id;
        $post['review']['customer_id'] = $customerId;
        if ($valid = $pr->validate($post['review'], [], $this->formId)) {
            if ($needApprove) {
                $post['review']['approved'] = 0;
            }
            $pr->set($post['review'])->save();
            //$pr->notify(); //todo: confirm about send notify
        }
        $successMessage = $this->BLocale->_('Your review updates have been submitted.');
        if ($needApprove) {
            $successMessage = $this->BLocale->_('Your review updates have been submitted. The changes will be visible as soon as they are approved.');
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
                $this->message('Cannot save data, please fix errors above', 'error', 'validator-errors:' . $this->formId);
                $url = $this->BApp->href('prodreviews/edit?pr=' . $pr->id());
            }
            $this->BResponse->redirect($url);
        }
    }

    public function action_ajax_review()
    {
        $req = $this->BRequest->request();
        $customerId = $this->Sellvana_Customer_Model_Customer->sessionUserId();
        $pr = $this->Sellvana_ProductReviews_Model_Review->loadWhere([
            'id'          => (int)$req['rid'],
            'customer_id' => $customerId
        ]);
        if (!$pr) {
            $this->BResponse->json(['status' => 'error', 'message' => 'Cannot load your review, please check again']);
        } else {
            $this->BResponse->json($pr->as_array() + ['status' => 'success']);
        }

    }
}
