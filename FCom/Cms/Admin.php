<?php

class FCom_Cms_Admin extends BClass
{
    public static function bootstrap()
    {
        BRouting::i()
            ->get('/cms/nav', 'FCom_Cms_Admin_Controller_Nav.index')
            ->any('/cms/nav/.action', 'FCom_Cms_Admin_Controller_Nav')

            ->get('/cms/pages', 'FCom_Cms_Admin_Controller_Pages.index')
            ->any('/cms/pages/.action', 'FCom_Cms_Admin_Controller_Pages')
            ->any('/cms/pages/history/:id/grid_data', 'FCom_Cms_Admin_Controller_Pages.history_grid_data')

            ->get('/cms/blocks', 'FCom_Cms_Admin_Controller_Blocks.index')
            ->any('/cms/blocks/.action', 'FCom_Cms_Admin_Controller_Blocks')
            ->any('/cms/blocks/history/:id/grid_data', 'FCom_Cms_Admin_Controller_Blocks.history_grid_data')

            ->any('/cms/forms', 'FCom_Cms_Admin_Controller_Forms.index')
            ->any('/cms/forms/.action', 'FCom_Cms_Admin_Controller_Forms')
        ;

        BLayout::i()->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml')
        ;

        FCom_Admin_Model_Role::i()->createPermission(array(
            'cms' => 'CMS',
            'cms/pages' => 'Manage Pages',
            'cms/blocks' => 'Manage Blocks',
            'cms/nav' => 'Manage Navigation',
        ));
    }
}

