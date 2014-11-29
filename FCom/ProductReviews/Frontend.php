<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_ProductReviews_Frontend
 *
 * @property FCom_ProductReviews_Model_Review $FCom_ProductReviews_Model_Review
 */
class FCom_ProductReviews_Frontend extends BClass
{
    public function hookReviews($args)
    {
        $product = $args['product'];

        $productReviews = $this->FCom_ProductReviews_Model_Review->orm()
            ->where("product_id", $product->id())->find_many();

        return $this->BLayout->view('prodreviews/reviews')
            ->set(['product' => $product, 'product_reviews' => $productReviews])
            ->render();
    }
}
