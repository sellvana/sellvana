<?php

class FCom_Admin_View_Grid extends BView
{
    public function __construct()
    {
        $this->default_config = array(
            'grid' => array(
                'prmNames' => array(
                    'page' => 'p',
                    'rows' => 'ps',
                    'sort' => 's',
                    'order' => 'sd',
                ),
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
           'navGrid' => array('add'=>false, 'edit'=>false, 'del'=>false, 'refresh'=>true, 'prm'=>array(
                'search'=>array('multipleSearch'=>true, 'multipleGroup'=>true),
           )),
        );
    }

    protected function _processPersonalization($cfg)
    {
        if (!empty($cfg['custom']['columnChooser'])) {
            $cfg[] = array('navButtonAdd',
                'caption' => '',
                'title' => 'Customize Columns',
                'buttonicon' => 'ui-icon-calculator',
                'onClickButton' => "function() { \$('#{$cfg['grid']['id']}').jqGrid('columnChooser') }",
            );
        }
        if (!empty($cfg['custom']['personalize'])) {
            $gridId = is_string($cfg['custom']['personalize'])
                ? $cfg['custom']['personalize'] : $cfg['grid']['id'];
            $pers = FCom_Admin_Model_User::i()->personalize();
            if (!empty($pers['grid'][$gridId]['columns'])) {
                $persCols = $pers['grid'][$gridId]['columns'];
                foreach ($persCols as $k=>$c) {
                    if (empty($cfg['grid']['columns'][$k])) {
                        unset($persCols[$k]);
                    }
                }
                $cfg['grid']['columns'] = BUtil::arrayMerge($cfg['grid']['columns'], $persCols);
            }

            $url = BApp::url('FCom_Admin', '/my_account/personalize');
            $cfg['grid']['resizeStop'] = "function(newwidth, index) {
                var cols = \$('#{$cfg['grid']['id']}').jqGrid('getGridParam', 'colModel');
                \$.post('{$url}', {do:'grid.col.width', grid:'{$gridId}',
                    col:cols[index].name, width:newwidth
                });
            }";
            $cfg[] = array('navButtonAdd',
                'caption' => '',
                'title' => 'Customize Columns',
                'buttonicon' => 'ui-icon-calculator',
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

    protected function _processSubGridConfig($cfg)
    {
        if (!empty($cfg['subGrid']) && is_array($cfg['subGrid'])) {
            $cfg['grid']['subGrid'] = true;
            $cfg['grid']['subGridOptions'] = array(
                'plusicon' => 'ui-icon-triangle-1-e',
                'minusicon' => 'ui-icon-triangle-1-s',
                'openicon' => 'ui-icon-arrowreturn-1-e',
                'reloadOnExpand' => false,
                'selectOnExpand' => false,
            );
            if (!empty($cfg['subGrid']['grid']['url'])) {
                $cfg['subGrid']['grid']['url'] = new BType('"'.$cfg['subGrid']['grid']['url'].'"+row_id');
            }
            $cfg['subGrid']['grid']['pager'] = new BType('pager_id');
            $cfg['subGrid']['isSubGrid'] = true;
            $jsBefore = !empty($cfg['subGrid']['custom']['jsBefore']) ? (string)$cfg['subGrid']['custom']['jsBefore'] : '';
            $jsAfter = '';
            if (!empty($cfg['subGrid']['grid']['editurl'])) {
                $jsAfter .= "subgrid.jqGrid('setGridParam', {editurl:subgrid.jqGrid('getGridParam', 'editurl')+row_id});";
            }
            $subGridView = static::i()->factory($cfg['grid']['id'].'_subgrid', array())->set('config', $cfg['subGrid']);
            $cfg['grid']['subGridRowExpanded'] = "function(subgrid_id, row_id) {
var subgrid_table_id = subgrid_id+'_t', pager_id = 'p_'+subgrid_table_id;
\$('#'+subgrid_id).html('<table id=\"'+subgrid_table_id+'\" class=\"scroll\"></table><div id=\"'+pager_id+'\" class=\"scroll\"></div>');
var subgrid = \$('#'+subgrid_table_id);
{$jsBefore}
{$subGridView->render()}
{$jsAfter}
            }";
            unset($cfg['subGrid']);
        }
        return $cfg;
    }

    protected function _processConfig($cfg)
    {
        $cfg = $this->_processPersonalization($cfg);
        $cfg = $this->_processSubGridConfig($cfg);

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
                if (empty($col['edittype'])) $col['edittype'] = 'select';
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
        if (!empty($cfg['navGrid']['edit'])) {
            $cfg['grid']['ondblClickRow'] = "function(rowid, iRow, iCol, e) { \$(this).jqGrid('editGridRow', rowid); }";
        }
/*
        foreach (array('add','edit','del') as $k) {
            if (!empty($cfg['navGrid'][$k])) {
                $cfg['navGrid']['prm'][$k] = array(
                    'afterSubmit'=>"function(response, postdata) {
console.log(response, postdata);
return [true, 'Testing error'];
                }");
            }
        }
*/
#echo "<pre>"; print_r($cfg); echo "</pre>"; exit;
        return $cfg;
    }

    /**
    * @see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:options
    */
    protected function _render()
    {
        $cfg = BUtil::arrayMerge($this->default_config, $this->config);
//echo "<pre>"; print_r($cfg); echo "</pre>";
        $cfg = $this->_processConfig($cfg);

        $isSubGrid = !empty($cfg['isSubGrid']);
        if (!$isSubGrid) {
            $id = $cfg['grid']['id'];
            $html = "<table id=\"{$id}\"></table>";
            if (!empty($cfg['grid']['pager'])) {
                $pagerId = true===$cfg['grid']['pager'] ? "pager-{$id}" : $cfg['grid']['pager'];
                $cfg['grid']['pager'] = $pagerId;
                $html .= "<div id=\"{$pagerId}\"></div>";
            }
            $html .= "<script>head(function() { jQuery('#{$id}')";
        } else {
            $quotedPagerId = "'#'+pager_id";
            $html = "jQuery('#'+subgrid_table_id)";
            unset($cfg['isSubGrid']);
        }

        $extraJS = array();
        $extraHTML = array();
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
            if (empty($quotedPagerId)) {
                if (!empty($opt['_pager'])) {
                    $localPagerId = $opt['_pager'];
                    unset($opt['_pager']);
                } else {
                    $localPagerId = $pagerId;
                }
                $quotedPagerId = "'#{$localPagerId}'";
            }
            if (is_array($opt) && !empty($opt['prm'])) {
                $prm = $opt['prm'];
                unset($opt['prm']);
            } else {
                $prm = array();
            }
            $optJS = BUtil::toJavaScript($opt);
            switch ($k) {
            case 'grid':
                $html .= ".jqGrid({$optJS})\n";
                break;

            case 'inlineNav':
            case 'navButtonAdd':
                $html .= ".jqGrid('{$k}', {$quotedPagerId}, {$optJS})\n";
                break;

            case 'navGrid':
                foreach (array('edit', 'add', 'del', 'search', 'view') as $t) {
                    if (!empty($prm[$t])) {
                        $prmJS[$t] = BUtil::toJavaScript($prm[$t]);
                    } else {
                        $prmJS[$t] = '{}';
                    }
                }
                $html .= ".jqGrid('navGrid', {$quotedPagerId}, {$optJS},"
                    ." {$prmJS['edit']}, {$prmJS['add']}, {$prmJS['del']},"
                    ." {$prmJS['search']}, {$prmJS['view']})\n";
                break;

            default:
                $html .= ".jqGrid('{$k}', {$optJS})\n";
            }
        }

        if (!$isSubGrid) {
            $html .= "; ".join("\n", $extraJS)." });</script>".join('', $extraHTML);
        }

        return $html;
    }

    protected function _processFilters($filter)
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

    public function processORM($orm, $method=null)
    {
        if (($filter = BRequest::i()->request('filters'))) {
            $where = $this->_processFilters(BUtil::fromJson($filter));
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
}