<?php

class Sellvana_Disqus_Frontend extends BClass
{
    /**
     * @param array $d
     * @return bool
     */
    public function isLayoutEnabled($d)
    {
        $config = $this->BConfig->get('modules/Sellvana_Disqus');
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
