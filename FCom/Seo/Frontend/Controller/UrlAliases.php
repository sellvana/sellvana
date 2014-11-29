<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Seo_Frontend_Controller_UrlAliases
 *
 * @property FCom_Seo_Model_UrlAlias $FCom_Seo_Model_UrlAlias
 */
class FCom_Seo_Frontend_Controller_UrlAliases extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $url = $this->BRequest->param('url');
        if ($url === '' || null === $url) {
            $this->forward(false);
            return;
        }
        $alias = $this->FCom_Seo_Model_UrlAlias->findByUrl($url);
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
