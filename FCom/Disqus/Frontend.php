<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Disqus_Frontend extends BClass
{
    public function isLayoutEnabled($d)
    {
        $config = $this->BConfig->get('modules/FCom_Disqus');
        switch ($d['layout_name']) {
        case 'base':
            return !empty($config['show_on_all_pages']);

        case '/catalog/product':
            return !empty($config['show_on_product']) && empty($config['show_on_all_pages']);

        default:
            return false;
        }
    }

}
