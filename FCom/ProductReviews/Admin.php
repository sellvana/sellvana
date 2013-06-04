<?php

class FCom_ProductReviews_Admin extends BClass
{
    public function hookProductTab($args)
    {
        $model = $args['model'];
        BLayout::i()->view('prodreviews/products/tab')->model = $model;
    }
}
