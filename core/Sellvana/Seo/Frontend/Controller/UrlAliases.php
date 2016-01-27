<?php

/**
 * Class Sellvana_Seo_Frontend_Controller_UrlAliases
 *
 * @property Sellvana_Seo_Model_UrlAlias $Sellvana_Seo_Model_UrlAlias
 */
class Sellvana_Seo_Frontend_Controller_UrlAliases extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $url = $this->BRequest->param('url');
        if ($url === '' || null === $url) {
            $this->forward(false);
            return;
        }
        $alias = $this->Sellvana_Seo_Model_UrlAlias->findByUrl($url);
        if (!$alias) {
            $this->forward(false);
            return;
        }
        switch ($alias->redirect_type) {
        case 'FWD':
            $this->forward(true, $alias->target_url);
            break;
        case '301': case '302':
            $this->BResponse->redirect($alias->targetUrl(), $alias->redirect_type);
            break;
        }
    }
}
