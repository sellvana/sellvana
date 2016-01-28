<?php

/**
 * Class Sellvana_ProductReviews_Frontend
 *
 * @property Sellvana_ProductReviews_Model_Review $Sellvana_ProductReviews_Model_Review
 */
class Sellvana_ProductReviews_Frontend extends BClass
{
    public function hookReviews($args)
    {
        $product = $args['product'];

        $productReviews = $this->Sellvana_ProductReviews_Model_Review->orm()
            ->where("product_id", $product->id())->find_many();

        return $this->BLayout->view('prodreviews/reviews')
            ->set(['product' => $product, 'product_reviews' => $productReviews])
            ->render();
    }
}
