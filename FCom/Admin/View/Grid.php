<?php

class FCom_Admin_View_Grid extends BView
{
    public function __construct()
    {
        $this->default_config = array(
            'grid' => array(
                'datatype'  => 'json',
                'jsonReader' =>  array(
                    'root' => 'rows',
                    'page'=>'p',
                    'total'=>'mp',
                    'records'=>'c',
                    'repeatitems'=>false,
                    'id'=>'id',
                ),
            ),
        );
    }

    public function processConfig($cfg)
    {

    }


    /** @see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options */
    public function _render()
    {
        $cfg = array_merge_recursive($this->default_config, $this->config);
        $cfg = $this->processConfig($cfg);
        $id = $cfg['grid']['id'];
        $html = "<table id=\"grid-{$id}\"></table>";
        if (!empty($cfg['grid']['pager'])) {
            $pagerId = true===$cfg['grid']['pager'] ? "grid-pager-{$id}" : $cfg['grid']['pager'];
            $html .= "<div id=\"{$pagerId}\"></div>";
        }
        $html .= "<script>jQuery('#grid-{$id}')";
        foreach ($cfg as $k=>$opt) {
            $opt = BUtil::toJavaScript($opt);
            switch ($k) {
                case 'grid': $html .= ".jqGrid({$opt})"; break;
                case 'navGrid': $html .= ".jqGrid('navGrid', '#{$pagerId}', {$opt})"; break;
                default: $hmtl .= ".jqGrid('{$k}', {$opt})";
            }
        }
        $html .= "</script>";
        return $html;
    }
}