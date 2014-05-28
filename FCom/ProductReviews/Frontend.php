<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_ProductReviews_Frontend extends BClass
{
    public function hookReviews($args)
    {
        $product = $args['product'];

        $productReviews = FCom_ProductReviews_Model_Review::i()->orm()
            ->where("product_id", $product->id())->find_many();

        return BLayout::i()->view('prodreviews/reviews')
            ->set(['product' => $product, 'product_reviews' => $productReviews])
            ->render();
    }
}
