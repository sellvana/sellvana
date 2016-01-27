<?php

/**
 * Class FCom_Frontend_View_Breadcrumbs
 *
 * @property $crumbs_formatted
 * @property $crumbs
 * @property FCom_Core_Model_TreeAbstract $navNode
 */
class FCom_Frontend_View_Breadcrumbs extends BView
{
    public function getCrumbs()
    {
        if (!$this->crumbs_formatted) {
            if ($this->crumbs) {
                $crumbs = $this->crumbs;
            } elseif ($this->navNode) {
                $crumbs = ['home'];
                if (($asc = $this->navNode->ascendants())) {
                    foreach ($asc as $a) {
                        if (!$a->node_name) continue;
                        $crumbs[] = [
                            'href' => $a->url_href ? $this->BApp->baseUrl() . trim('/' . $a->url_href, '/') : null,
                            'title' => $a->node_name,
                            'label' => $a->node_name,
                        ];
                    }
                }
                $crumbs[] = ['label' => $this->navNode->node_name, 'active' => true];
            }
            if (!empty($crumbs)) {
                foreach ($crumbs as $i => &$c) {
                    if ($c == 'home') {
                        $url = $this->get('home_url');
                        if (!$this->BUtil->isUrlFull($url)) {
                            $url = $this->BApp->href($url);
                        }
                        $c = ['href' => $url, 'label' => $this->BLocale->_('Home'), 'li_class' => 'home'];
                    }
                    if (!isset($c['title'])) {
                        $c['title'] = $c['label'];
                    }
                }
                unset($c);
                $this->crumbs_formatted = $crumbs;
            } else {
                $this->crumbs_formatted = [];
            }
        }
        return $this->crumbs_formatted;
    }
}
