<?php

class FCom_Disqus_Frontend extends BClass
{
    static public function layout()
    {
        $conf = BConfig::i()->get('modules/FCom_Disqus');
        if (!empty($conf['show_on_all_pages'])) {
             BLayout::i()->layout(array(
                'base'=>array(
                    array('hook' => 'footer', 'views'=>array('disqus/embed')),
                ),
            ));
        } elseif (!empty($conf['show_on_product'])) {
            // by default only on product info page
            BLayout::i()->layout(array(
                '/catalog/product' => array(
                    array('hook' => 'main', 'views'=>array('disqus/embed')),
                ),
            ));
        }
    }
}
