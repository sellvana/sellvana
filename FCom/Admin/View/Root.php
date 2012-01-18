<?php

class FCom_Admin_View_Root extends BView
{
    protected $_tree = array('ul' => array('id'=>'nav'));

    public function add($path, $node)
    {
        $root =& $this->_tree;
        $pathArr = explode('/', $path);
        foreach ($pathArr as $k) {
            $root =& $root['/'][$k];
        }
        $root = $node;
        return $this;
    }

    public function tag($tag, $params=array())
    {
        $hmtl = '';
        foreach ($params as $k=>$v) {
            $hmtl .= ' '.$k.'="'.htmlspecialchars($v).'"';
        }
        return "<{$tag}{$hmtl}>";
    }

    public function renderNodes($root=null)
    {
        if (is_null($root)) {
            $root = $this->_tree;
        }
        if (empty($root['/'])) {
            return '';
        }
        $html = $this->tag('ul', !empty($root['ul']) ? $root['ul'] : array());
        foreach ($root['/'] as $k=>$node) {
            $label = !empty($node['label']) ? $node['label'] : $k;
            if (!empty($node['href'])) {
                $label = $this->tag('a', array('href'=>$node['href'])).$label.'</a>';
            }
            $children = $this->renderNodes($node);
            if (!empty($node['header'])) {
                $node['li']['class'] = 'nav-group';
                $label = '<header>'.$label.'</header>';
            }
            $html .= $this->tag('li', !empty($node['li']) ? $node['li'] : array())
                . $label . $children . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}