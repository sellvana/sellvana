<?php

class FCom_Catalog_Admin_Controller_Products extends BActionController
{
    public function action_index()
    {
        BLayout::i()->layout('/products');
        BResponse::i()->render();
    }

    public function action_product()
    {
        $layout = BLayout::i();
        $layout->layout('/products/product');

        BResponse::i()->render();
    }

    public function action_grid()
    {
        $orm = FCom_Catalog_Model_Product::i()->orm();
        $data = $orm->paginate(null, array('as_array'=>true));
        BResponse::i()->json($data);
    }
}