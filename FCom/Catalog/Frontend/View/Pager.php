<?php

class FCom_Catalog_Frontend_View_Pager extends FCom_Core_View_Abstract
{
    public function getViewAs()
    {
        $viewAs = BRequest::i()->get('view');
        return $viewAs && in_array($viewAs, $this->view_as_options) ? $viewAs : $this->default_view_as;
    }

    public function getPageUrl($params = [])
    {
        static $curUrl, $pageUrl;
        if (!$curUrl) {
            $curUrl = BRequest::i()->currentUrl();
        }
        // optimize page number urls by making simple str_replace
        if (!empty($params['p']) && sizeof($params) === 1) {
            if (!$pageUrl) {
                $pageUrl = BUtil::setUrlQuery($curUrl, ['page' => '-PAGE-']);
            }
            $url = str_replace('-PAGE-', $params['p'], $pageUrl);
            return $url;
        }
        return BUtil::setUrlQuery($curUrl, $params);
    }

    public function setCanonicalPrevNext()
    {
        $head = BLayout::i()->view('head');
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
