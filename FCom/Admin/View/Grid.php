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
                'sortname'      => 'id',
                'sortorder'     => 'asc',
                'rowNum'        => 20,
                'rowList'       => array(10, 20, 50, 100, 200),
                'pager'         => true,
                'gridview'      => true,
                'viewrecords'   => true,
                'shrinkToFit'   => false,
                'forceFit'      => false,
                'autowidth'     => true,
                //'altRows'       => true,
                'width'         => '100%',
                'height'        => '100%',
                'multiselectWidth' => 30,
           ),
           'navGrid' => array('add'=>false, 'edit'=>false, 'del'=>false, 'refresh'=>true),
        );
    }

    public function processPersonalization($cfg)
    {
        if (!empty($cfg['custom']['personalize'])) {
            $gridId = is_string($cfg['custom']['personalize'])
                ? $cfg['custom']['personalize'] : $cfg['grid']['id'];
            $pers = FCom_Admin_Model_User::i()->personalize();
            if (!empty($pers['grid'][$gridId]['columns'])) {
                $persCols = $pers['grid'][$gridId]['columns'];
                $cfg['grid']['columns'] = BUtil::arrayMerge($cfg['grid']['columns'], $persCols);
            }

            $url = BApp::url('FCom_Admin', '/my_account/personalize');
            $cfg['grid']['resizeStop'] = "function(newwidth, index) {
                var cols = \$('#{$cfg['grid']['id']}').jqGrid('getGridParam', 'colModel');
                \$.post('{$url}', {do:'grid.col.width', grid:'{$gridId}',
                    col:cols[index].name, width:newwidth
                });
            }";
            $cfg[] = array('navButtonAdd', 'caption' => 'Columns', 'title' => 'Reorder Columns',
                'onClickButton' => "function() {
                    jQuery('#{$cfg['grid']['id']}').jqGrid('columnChooser', {
                        done:function(perm) {
                            console.log(perm, this.jqGrid('getGridParam', 'colModel'));
                            if (perm) {
                                this.jqGrid('remapColumns', perm, true);
                                \$.post('{$url}', {do:'grid.col.order', grid:'{$gridId}',
                                    cols:JSON.stringify(this.jqGrid('getGridParam', 'colModel'))
                                });
                            }
                        }
                    });
                }");
        }

        if (!empty($cfg['grid']['columns'])) {
            foreach ($cfg['grid']['columns'] as $colName=>$col) {
                if (empty($col['name'])) {
                    $col['name'] = $colName;
                }
                $cfg['grid']['colModel'][] = $col;
            }
            unset($cfg['grid']['columns']);
        }

        return $cfg;
    }

    public function processConfig($cfg)
    {
        $cfg = $this->processPersonalization($cfg);

        $pos = 0;
        foreach ($cfg['grid']['colModel'] as &$col) {
            if (!empty($col['position'])) {
                $pos = $col['position'];
            } else {
                $col['position'] = ++$pos;
            }
            if (!empty($col['autocomplete'])) {
                $cfg['js'][] = "\$('#gbox_{$cfg['grid']['id']} #gs_{$col['name']}').fcom_autocomplete({
                    url:'{$col['autocomplete']}'
                });";
            }
            if (!empty($col['options'])) {
                $valArr = array();
                foreach ($col['options'] as $k=>$v) {
                    $valArr[] = $k.':'.$v;
                }
                $values = join(';', $valArr);
                if (empty($col['formatter'])) $col['formatter'] = 'select';
                if (empty($col['stype'])) $col['stype'] = 'select';
                $col['editoptions'] = array('value'=>$values);
                $col['searchoptions'] = array('value'=>':All;'.$values);
                unset($col['options']);
            }
        }
        unset($col);
        usort($cfg['grid']['colModel'], function($a, $b) {
            $i = $a['position']; $j = $b['position']; return $i<$j ? -1 : ($i>$j ? 1 : 0);
        });
        unset($cfg['custom']);

#echo "<pre>"; print_r($cfg); echo "</pre>"; exit;
        return $cfg;
    }

    public function processORM($orm, $method=null)
    {
        if (($filter = BRequest::i()->request('filters'))) {
            $where = $this->processFilters(BUtil::fromJson($filter));
#print_r($where);
            $orm->where_complex($where);
        }
        if (!is_null($method)) {
            //BPubSub::i()->fire('FCom_Admin_View_Grid::processORM', array('orm'=>$orm));
            BPubSub::i()->fire($method.'.orm', array('orm'=>$orm));
        }
        $data = $orm->jqGridData();
#print_r(BORM::get_last_query());
        if (!is_null($method)) {
            BPubSub::i()->fire($method.'.data', array('data'=>$data));
        }
        return $data;
    }

    public function processFilters($filter)
    {
        static $map = array(
            'eq'=>'=?','ne'=>'!=?','lt'=>'<?','le'=>'<=?','gt'=>'>?','ge'=>'>=?',
            'in'=>'IN (?)','ni'=>'NOT IN (?)',
        );
        $where = array();
        if (!empty($filter['rules'])) {
            foreach ($filter['rules'] as $r) {
                $data = $r['data'];
                switch ($r['op']) {
                    case 'bw': $part = array($r['field'].' LIKE ?', $data.'%'); break;
                    case 'bn': $part = array($r['field'].' NOT LIKE ?', $data.'%'); break;
                    case 'ew': $part = array($r['field'].' LIKE ?', '%'.$data); break;
                    case 'en': $part = array($r['field'].' NOT LIKE ?', '%'.$data); break;
                    case 'cn': case 'nc': //$part = array($r['field'].' LIKE ?', '%'.$data.'%'); break;
                        $terms = explode(' ', $data);
                        $part = array('AND');
                        foreach ($terms as $term) {
                            $part[] = array($r['field'].' LIKE ?', '%'.$term.'%');
                        }
                        if ($r['op']==='nc') {
                            $part = array('NOT'=>$part);
                        }
                        break;
                    default: $part = array($r['field'].' '.$map[$r['op']], $data);
                }
                $where[$filter['groupOp']][] = $part;
            }
        }
        if (!empty($filter['groups'])) {
            foreach ($filter['groups'] as $g) {
                $where[$filter['groupOp']][] = $this->processFilters($g);
            }
        }
        return $where;
    }

    /** @see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options */
    public function _render()
    {
        $cfg = BUtil::arrayMerge($this->default_config, $this->config);
//echo "<pre>"; print_r($cfg); echo "</pre>";
        $cfg = $this->processConfig($cfg);
        $id = $cfg['grid']['id'];
        $html = "<table id=\"{$id}\"></table>";
        if (!empty($cfg['grid']['pager'])) {
            $pagerId = true===$cfg['grid']['pager'] ? "pager-{$id}" : $cfg['grid']['pager'];
            $cfg['grid']['pager'] = $pagerId;
            $html .= "<div id=\"{$pagerId}\"></div>";
        }
        $extraJS = array();
        $extraHTML = array();
        $html .= "<script>head(function() { jQuery('#{$id}')";
        foreach ($cfg as $k=>$opt) {
            if ($k==='html') {
                $extraHTML[] = join('', (array)$opt);
                continue;
            } elseif ($k==='js' || is_string($opt)) {
                $extraJS[] = join('', (array)$opt);
                continue;
            }
            if (is_numeric($k)) {
                $k = array_shift($opt);
            }
            if (!empty($opt['_pager'])) {
                $localPagerId = $opt['_pager'];
                unset($opt['_pager']);
            } else {
                $localPagerId = $pagerId;
            }
            $optJS = BUtil::toJavaScript($opt);
            switch ($k) {
                case 'grid':
                    $html .= ".jqGrid({$optJS})";
                    break;
                case 'navGrid':
                case 'inlineNav':
                case 'navButtonAdd':
                    $html .= ".jqGrid('{$k}', '#{$localPagerId}', {$optJS})";
                    break;
                default:
                    $html .= ".jqGrid('{$k}', {$optJS})";
            }
        }
        $html .= '; '.join("\n", $extraJS)." });</script>".join('', $extraHTML);
        return $html;
    }
}