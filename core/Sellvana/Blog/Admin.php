<?php

/**
 * Class Sellvana_Blog_Admin
 * @property FCom_Admin_Controller_MediaLibrary $FCom_Admin_Controller_MediaLibrary
 */
class Sellvana_Blog_Admin extends BClass
{
    public function bootstrap()
    {
        $this->FCom_Admin_Controller_MediaLibrary
            ->allowFolder('media/blog/images')
        ;

    }
}