<?php

class FCom_CustomField_Frontend extends BClass
{
    public static function bootstrap()
    {
        FCom_CustomField_Common::bootstrap();

        BPubSub::i()
            ->on('BLayout::hook.custom-fields-filters', 'FCom_CustomField_Common.hookCustomFieldFilters')
        ;

        BLayout::i()->addAllViews('Frontend/views');
    }
}