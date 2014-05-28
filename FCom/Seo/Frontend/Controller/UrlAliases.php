<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Seo_Frontend_Controller_UrlAliases extends FCom_Frontend_Controller_Abstract
{
    public function action_index()
    {
        $url = BRequest::i()->param('url');
        if ($url === '' || null === $url) {
            $this->forward(false);
            return;
        }
        $alias = FCom_Seo_Model_UrlAlias::i()->findByUrl($url);
        if (!$alias) {
            $this->forward(false);
            return;
        }
        switch ($alias->redirect_type) {
        case 'FWD':
            $this->forward(true, $alias->target_url);
            break;
        case '301': case '302':
            BResponse::i()->redirect($alias->targetUrl(), $alias->redirect_type);
            break;
        }
    }
}
