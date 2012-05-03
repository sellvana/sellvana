<?php
class FCom_IndexTank_Admin_Controller extends FCom_Admin_Controller_Abstract
{
    static public function bootstrap()
    {
        BPubSub::i()->on('FCom_IndexTank_Admin_Controller_ProductFields::gridViewBefore',
                'FCom_IndexTank_Admin_Controller_ProductFields::onGridViewBefore');
    }
}