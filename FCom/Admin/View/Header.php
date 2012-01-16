<?php

class FCom_Admin_View_Header extends BView
{
    protected $_tree = array();

    public function addNode($path, $node)
    {
        $root =& $this->_tree;
        $pathArr = explode('/', $path);
        foreach ($pathArr as $k) {
            $root =& $root['/'][$k];
        }
        $root['/'] = $node;
        return $this;
    }

    public function tag($tag, $params)
    {
        $params = '';
        foreach ($params as $k=>$v) {
            $params .= ' '.$k.'="'.htmlspecialchars($v).'"';
        }
        return "<{$tag}{$params}>";
    }

    public function renderNodes($root=null)
    {
        if (is_null($root)) {
            $root = $this->_tree;
        }
        if (empty($root['/'])) {
            return '';
        }
        $html = $this->tag('ul', $root['ul']);
        foreach ($root['/'] as $k=>$node) {
            $label = !empty($node['label']) ? $node['label'] : $k;
            if (!empty($node['href'])) {
                $label = $this->tag('a', array('href'=>$node['href'])).$label.'</a>';
            }
            $children = $this->renderNodes($node);
            $html .= $this->tag('li', $node['li']) . $label . $children . '</li>';
        }
        return join('', $html);
    }
}