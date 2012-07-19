<?php

class FCom_Disqus_Frontend extends BClass
{
    static public function bootstrap()
    {
        BLayout::i()->addAllViews('Frontend/views');
        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_Disqus_Frontend::layout');
        setLocale(LC_ALL, 'ru_RU.UTF-8');
        BLocale::addTranslationsFile('tr.csv');
    }

    static public function layout()
    {
        if (BConfig::i()->get('modules/FCom_Disqus/show_on_all_pages')) {
             BLayout::i()->layout(array(
            'base'=>array(
                array('hook', 'footer', 'views'=>array('disqus/embed')),
                )
            ));
        } else {
            // by default only on product info page
            BLayout::i()->layout(array(
                '/catalog/product' => array(
                    array('hook', 'main', 'views'=>array('disqus/embed')),
                ),
            ));
        }
    }
}
