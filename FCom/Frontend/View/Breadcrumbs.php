<?php

class FCom_Frontend_View_Breadcrumbs extends BView
{
    public function getCrumbs()
    {
        if (!$this->crumbs_formatted) {
            if ($this->crumbs) {
                $crumbs = $this->crumbs;
            } elseif ($this->navNode) {
                $crumbs = array('home');
                if (($asc = $this->navNode->ascendants())) {
                    foreach ($asc as $a) {
                        if (!$a->node_name) continue;
                        $crumbs[] = array(
                            'href'=>$a->url_href ? BApp::baseUrl().trim('/'.$a->url_href, '/') : null,
                            'title'=>$a->node_name,
                            'label'=>$a->node_name,
                        );
                    }
                }
                $crumbs[] = array('label'=>$this->navNode->node_name, 'active'=>true);
            }

            if (!empty($crumbs)) {
                foreach ($crumbs as $i=>&$c) {
                    if ($c=='home') $c = array('href'=>BApp::href(), 'label'=>'Home', 'li_class'=>'home');
                    if (!isset($c['title'])) $c['title'] = $c['label'];
                }
                unset($c);
                $this->crumbs_formatted = $crumbs;
            } else {
                $this->crumbs_formatted = array();
            }
        }
        return $this->crumbs_formatted;
    }
}
