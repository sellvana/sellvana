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
                'rowNum'        => 20,
                'rowList'       => array(10, 20, 50, 100, 200),
                'pager'         => true,
                'gridview'      => true,
                'viewrecords'   => true,
                'shrinkToFit'   => true,
                'autowidth'     => true,
                //'altRows'       => true,
                'width'         => '100%',
                'height'        => '100%',
            ),
        );
    }

    public function processConfig($cfg)
    {
        return $cfg;
    }

    public function processORM($orm, $method=null)
    {
        if (!is_null($method)) {
            BPubSub::i()->fire($method.'.orm', array('orm'=>$orm));
        }
        $data = $orm->jqGridData();
//print_r(BORM::get_last_query());
        if (!is_null($method)) {
            BPubSub::i()->fire($method.'.data', array('data'=>$data));
        }
        return $data;
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
            $cfg['grid']['pager'] = $pagerId;
            $html .= "<div id=\"{$pagerId}\"></div>";
        }
        $html .= "<script>jQuery('#grid-{$id}')";
        foreach ($cfg as $k=>$opt) {
            if (is_numeric($k)) {
                $k = array_shift($opt);
            }
            if (!empty($opt['_pager'])) {
                $localPagerId = $opt['_pager'];
                unset($opt['_pager']);
            } else {
                $localPagerId = $pagerId;
            }
            $opt = BUtil::toJavaScript($opt);
            switch ($k) {
                case 'grid':
                    $html .= ".jqGrid({$opt})";
                    break;
                case 'navGrid':
                case 'navButtonAdd':
                    $html .= ".jqGrid('{$k}', '#{$localPagerId}', {$opt})";
                    break;
                default:
                    $html .= ".jqGrid('{$k}', {$opt})";
            }
        }
        $html .= "</script>";
        return $html;
    }
}