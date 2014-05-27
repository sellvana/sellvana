<?php

class FCom_Stock_Admin_Controller_Stock extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'catalog/stocks';
    protected $_modelClass = 'FCom_Stock_Model_Sku';
    protected $_gridHref = 'stock';
    protected $_gridTitle = 'Stock Management';
    protected $_recordName = 'SKU';
    protected $_mainTableAlias = 's';
    protected $_navPath = 'catalog/stock';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        unset($config['form_url']);
        $data = [];
        $settings = BConfig::i()->get('modules/FCom_Catalog');
        $callback = function ($row) use (&$data) {
            $data_serialized = BUtil::objectToArray(json_decode($row->get('data_serialized')));
            $qty = '';
            //$tmp_cost use when edit inline
            //@TODO: find solution other when edit inline cost only display number instead include symbol currency
            $cost = $tmp_cost = '';
            if ($row->get('cost')) {
                $tmp_cost = $row->get('cost');
                $cost = BLocale::currency($tmp_cost);
            }
            $out_stock = (isset($settings['out_stock']))? 'back_order': '';
            if (isset($data_serialized['stock_policy'])) {
                $qty = $data_serialized['stock_policy']['stock_qty'];
                $out_stock = $data_serialized['stock_policy']['out_stock'];
            }
            $tmp = [
                'id' => $row->get('id'),
                'sku' => $row->get('sku'),
                'cost' => $cost,
                'tmp_cost' => $tmp_cost,
                'product_name' => $row->get('product_name'),
                'status' => $row->get('status'),
                'stock_qty' => $qty,
                'out_stock' => $out_stock,
            ];
            array_push($data, $tmp);
        };
        FCom_Stock_Model_Sku::i()->orm($this->_mainTableAlias)->select(array($this->_mainTableAlias.'.*', 'p.data_serialized', 'p.cost', 'p.product_name'))
            ->left_outer_join('FCom_Catalog_Model_Product', [ 'p.local_sku', '=', $this->_mainTableAlias . '.sku'], 'p')
            ->select_expr('p.product_name', 'product_name')
            ->select_expr('p.cost', 'cost')->iterate($callback);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 50, 'index' => 's.id'],
            ['type' => 'input', 'name' => 'sku', 'label' => 'SKU', 'width' => 300, 'index' => $this->_mainTableAlias.'.sku',
                    //'editable' => true, 'addable' => true, 'editor' => 'text',
                    //'validation' => ['required' => true, 'unique' => BApp::href('stock/unique')]
            ],
            ['name' => 'product_name', 'label' => 'Product Name', 'width' => 300],
            ['type' => 'input', 'name' => 'status', 'label' => 'Status', 'width' => 150,
                'index' => $this->_mainTableAlias.'.status', 'editable' => true, 'edit_inline' => true,
                'mass-editable-show' => true, 'mass-editable' => true,
                'editor' => 'select', 'options' => FCom_Stock_Model_Sku::i()->statusOptions() ],
            ['type' => 'input', 'name' => 'out_stock', 'label' => 'Out of Stock Policy', 'width' => 150,
                'mass-editable-show' => true, 'mass-editable' => true,
                'editable' => true, 'edit_inline' => true, 'editor' => 'select', 'options' => FCom_Stock_Model_Sku::i()->outStockOptions()],
            ['type' => 'input', 'name' => 'cost', 'label' => 'Cost', 'width' => 300,
                'editable' => true, 'edit_inline' => true,'editor' => 'text', 'validation' => ['number' => true]],
            ['type' => 'input', 'name' => 'stock_qty', 'label' => 'Quantity', 'width' => 150,
                'editable' => true, 'edit_inline' => true,
                'editor' => 'text', 'validation' => ['required' => true, 'number' => true]],
            ['type' => 'btn_group',
                  'buttons' => [
                                    ['name' => 'edit', 'icon' => 'icon-pencil ', 'cssClass' => 'btn-xs btn-edit-inline'],
                                    ['name' => 'save-inline', 'icon' => ' icon-ok-sign', 'cssClass' => 'btn-xs btn-save-inline hide'],
                                    ['name' => 'delete'],
                                ]
                ]
        ];
        $config['data_mode'] = 'local';
        $config['data'] = $data;
        $config['actions'] = [
            'edit' => true,
            'delete' => true
        ];
        $config['callbacks']['before_edit_inline'] = '
            this.$el.find("input").addClass("input-stock");
            this.$el.find("input[name=\'cost\']").val(this.model.get("tmp_cost"));
            function stockInputValidate(value, elem, params) {
                if (value < 0) {
                    return false;
                }
                return true;
            }
            $.validator.addMethod("stockInputValidate",stockInputValidate , function(params, element) {
                if ($(element).attr("name") == "cost") {
                    return "'.BLocale::_('The cost of an item cannot be less than zero').'";
                }
                if ($(element).attr("name") == "stock_qty") {
                    return "'.BLocale::_('Stock Item cannot have less than 0 quantity in stock').'";
                }

            });
            $.validator.addClassRules("input-stock", {
                stockInputValidate: true
            });
        ';
        $config['filters'] = [
            ['field' => 'sku', 'type' => 'text'],
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'status', 'type' => 'select'],
            ['field' => 'out_stock', 'type' => 'select'],
            ['field' => 'stock_qty', 'type' => 'number-range'],
        ];
        return $config;
    }

    public function productStockPolicy($model)
    {
        $stock_policy = [
            'manage_stock' => 0,
            'stock_qty' => '',
            'out_stock' => 'keep_selling',
            'maximum_quantity_shopping' => '',
            'quantity_items_status' => '',
            'notify_administrator_quantity' => '',
        ];
        if (isset($model->data_serialized)) {
            $data = BUtil::objectToArray(json_decode($model->data_serialized));
            if (isset($data['stock_policy'])) {
                $stock_policy = $data['stock_policy'];
            }
        }
        return $stock_policy;
    }

    public function action_restore_stock_policy()
    {
        $post = BRequest::i()->post();
        $config = BConfig::i()->get('modules/FCom_Catalog');
        $result = '';
        if (isset($post['restore'])) {
            switch($post['restore']) {
                case 'maximum_quantity_shopping':case 'quantity_items_status':case 'notify_administrator_quantity':
                    if ($config) {
                        $result = $config[$post['restore']];
                    }
                     break;
                case 'out_stock':
                    $result = 'back_order';
                    break;
                default:
                    break;
            }
        }
        BResponse::i()->json(['result' => $result]);
    }
    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_sku" class="btn grid-new btn-primary _modal">'
                . BLocale::_('New Sku') . '</button>']]);
    }

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each($post);
        $rows = BDb::many_as_array(FCom_Stock_Model_Sku::i()->orm()->where($data['key'], $data['value'])->find_many());
        BResponse::i()->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    public function action_grid_data__POST()
    {
        $r = BRequest::i();
        if ($r->post('oper') == 'edit') {
            $data = $r->post();
            // avoid error when edit
            unset($data['id'], $data['oper'], $data['bin_id']);
            $set = FCom_Stock_Model_Sku::i()->load($r->post('id'))->set($data)->save();
            $result = $set->as_array();

            BResponse::i()->json($result);
        } else {
            $this->_processGridDataPost($this->_modelClass);
        }
    }

    public function action_index__POST()
    {
        $p = BRequest::i()->post();
        $p['tmp_cost'] = $p['cost'];
        if ($p['cost'] != '') {
            $p['cost'] = BLocale::currency($p['cost']);
        }
        if (isset($p['sku'])) {
            $prod = FCom_Catalog_Model_Product::i()->loadWhere(['local_sku' => $p['sku']]);
            FCom_Stock_Model_Sku::i()->loadWhere(['id' => $p['id']])->set('status', $p['status'])->save();
            if ($prod) {
                $data_serialized = BUtil::objectToArray(json_decode($prod->get('data_serialized')));
                if (!isset($data_serialized['stock_policy']))  {
                    $data_serialized['stock_policy'] = ['stock_qty' => $p['stock_qty'], 'out_stock' => $p['out_stock'], 'manage_stock' => $p['manage_stock']];
                } else {
                    $data_serialized['stock_policy']['stock_qty'] = $p['stock_qty'];
                    $data_serialized['stock_policy']['out_stock'] = $p['out_stock'];
                    $data_serialized['stock_policy']['manage_stock'] = $p['status'];
                }
                $prod->setData('stock_policy', $data_serialized['stock_policy']);
                $prod->set('cost', $p['cost']);
                $prod->save();
            }
        }
        BResponse::i()->json($p);
    }
}
