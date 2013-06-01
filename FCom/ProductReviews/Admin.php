<?php

class FCom_ProductReviews_Admin extends BClass
{
    public static function bootstrap()
    {
        BEvents::i()
            ->on('BLayout::hook.catalog/products/tab/main', 'FCom_ProductReviews_Admin.hookProductTab')
        ;

        BRouting::i()
            ->get('/prodreviews', 'FCom_ProductReviews_Admin_Controller.index')
            ->any('/prodreviews/.action', 'FCom_ProductReviews_Admin_Controller')
        ;

        BLayout::i()->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml');
    }

    public function hookProductTab($args)
    {
        $model = $args['model'];
        BLayout::i()->view('prodreviews/products/tab')->model = $model;
    }
}
