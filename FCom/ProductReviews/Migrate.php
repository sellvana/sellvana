<?php

class FCom_ProductReviews_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.1', array($this, 'install'));

    }

    public function install()
    {
        FCom_ProductReviews_Model_Reviews::i()->install();
    }


}