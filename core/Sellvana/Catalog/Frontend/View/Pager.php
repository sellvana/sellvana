<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Frontend_View_Pager
 *
 * @property $default_page_size
 * @property $page_size_options
 * @property $view_as_options
 * @property $default_view_as
 * @property $sort_options
 * @property $default_sort
 */
class Sellvana_Catalog_Frontend_View_Pager extends FCom_Core_View_Abstract
{
    /**
     * @return array|null|string
     */
    public function getViewAs()
    {
        $viewAs = $this->BRequest->get('view');
        return $viewAs && in_array($viewAs, $this->view_as_options) ? $viewAs : $this->default_view_as;
    }

    /**
     * @param array $params
     * @return mixed|string
     */
    public function getPageUrl($params = [])
    {
        static $curUrl, $pageUrl;
        if (!$curUrl) {
            $curUrl = $this->BRequest->currentUrl();
        }
        // optimize page number urls by making simple str_replace
        if (!empty($params['p']) && sizeof($params) === 1) {
            if (!$pageUrl) {
                $pageUrl = $this->BUtil->setUrlQuery($curUrl, ['page' => '-PAGE-']);
            }
            $url = str_replace('-PAGE-', $params['p'], $pageUrl);
            return $url;
        }
        return $this->BUtil->setUrlQuery($curUrl, $params);
    }

    /**
     *
     */
    public function setCanonicalPrevNext()
    {
        /** @var BViewHead $head */
        $head = $this->BLayout->view('head');
        if (!$head) {
            return;
        }
        $s = $this->get('state');
        if ($s['p'] > 1) {
            $head->link('prev', $this->getPageUrl(['p' => $s['p'] - 1]));
        }
        if ($s['p'] < $s['mp']) {
            $head->link('next', $this->getPageUrl(['p' => $s['p'] + 1]));
        }
    }
}
