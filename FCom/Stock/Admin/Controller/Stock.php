<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        $config['edit_url'] = $this->BApp->href($this->_gridHref . '/grid_data');
        $config['edit_url_required'] = true;
        $callback = function ($row) use (&$data) {
            $data_serialized = $this->BUtil->objectToArray(json_decode($row->get('data_serialized')));
            $qty = '';
            //$tmp_cost use when edit inline
            //@TODO: find solution other when edit inline cost only display number instead include symbol currency
            $cost = $tmp_cost = '';
            if ($row->get('cost')) {
                $tmp_cost = $row->get('cost');
                $cost = $this->BLocale->currency($tmp_cost);
            }
            $out_stock = (isset($settings['out_stock']))? 'back_order': '';
            if (isset($data_serialized['stock_policy'])) {
                $qty = (isset($data_serialized['stock_policy']['stock_qty']))? $data_serialized['stock_policy']['stock_qty'] : '';
                $out_stock = (isset($data_serialized['stock_policy']['out_stock'])) ? $data_serialized['stock_policy']['out_stock']: '';
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
        $this->FCom_Stock_Model_Sku->orm($this->_mainTableAlias)->select(array($this->_mainTableAlias.'.*', 'p.data_serialized', 'p.cost', 'p.product_name'))
            ->left_outer_join('FCom_Catalog_Model_Product', [ 'p.local_sku', '=', $this->_mainTableAlias . '.sku'], 'p')
            ->select_expr('p.product_name', 'product_name')
            ->select_expr('p.cost', 'cost')->iterate($callback);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'width' => 50, 'index' => 's.id'],
            ['type' => 'input', 'name' => 'sku', 'label' => 'SKU', 'width' => 300, 'index' => $this->_mainTableAlias.'.sku',
                    //'editable' => true, 'addable' => true, 'editor' => 'text',
                    //'validation' => ['required' => true, 'unique' => $this->BApp->href('stock/unique')]
            ],
            ['name' => 'product_name', 'label' => 'Product Name', 'width' => 300],
            ['type' => 'input', 'name' => 'status', 'label' => 'Status', 'width' => 150,
                'index' => $this->_mainTableAlias.'.status', 'editable' => true, 'edit_inline' => true,
                'mass-editable-show' => true, 'mass-editable' => true,
                'editor' => 'select', 'options' => $this->FCom_Stock_Model_Sku->statusOptions() ],
            ['type' => 'input', 'name' => 'out_stock', 'label' => 'Out of Stock Policy', 'width' => 150,
                'mass-editable-show' => true, 'mass-editable' => true,
                'editable' => true, 'edit_inline' => true, 'editor' => 'select', 'options' => $this->FCom_Stock_Model_Sku->outStockOptions()],
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
        $config['local_personalize'] = true;
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
                    return "'.$this->BLocale->_('The cost of an item cannot be less than zero').'";
                }
                if ($(element).attr("name") == "stock_qty") {
                    return "'.$this->BLocale->_('Stock Item cannot have less than 0 quantity in stock').'";
                }

            });
            $.validator.addClassRules("input-stock", {
                stockInputValidate: true
            });
        ';
        $config['filters'] = [
            ['field' => 'sku', 'type' => 'text'],
            ['field' => 'product_name', 'type' => 'text'],
            ['field' => 'status', 'type' => 'multiselect'],
            ['field' => 'out_stock', 'type' => 'multiselect'],
            ['field' => 'stock_qty', 'type' => 'number-range'],
        ];
        $config['grid_before_create'] = 'stockGridRegister';
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
            $data = $this->BUtil->objectToArray(json_decode($model->data_serialized));
            if (isset($data['stock_policy'])) {
                $stock_policy = $data['stock_policy'];
            }
        }
        return $stock_policy;
    }

    public function action_restore_stock_policy()
    {
        $post = $this->BRequest->post();
        $config = $this->BConfig->get('modules/FCom_Catalog');
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
        $this->BResponse->json(['result' => $result]);
    }
    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => [
            'new' => '<button type="button" id="add_new_sku" class="btn grid-new btn-primary _modal">'
                . $this->BLocale->_('New Sku') . '</button>']]);
    }

    public function action_unique__POST()
    {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->FCom_Stock_Model_Sku->orm()->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    public function action_grid_data__POST()
    {
        $r = $this->BRequest;
        $p = $r->post();
        switch ($p['oper']) {
            case 'edit':
                // avoid error when edit
                $p['tmp_cost'] = $p['cost'];
                unset($p['oper']);
                if (isset($p['sku'])) {
                    $prod = $this->FCom_Catalog_Model_Product->load($p['sku'], 'local_sku');
                    $this->FCom_Stock_Model_Sku->load($p['id'])->set('status', $p['status'])->save();
                    if ($prod) {
                        $data_serialized = $this->BUtil->objectToArray(json_decode($prod->get('data_serialized')));
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
                if ($p['cost'] != '') {
                    $p['cost'] = $this->BLocale->currency($p['cost']);
                }
                $this->BResponse->json($p);
                break;
            case 'mass-edit':
                $id = $p['id'];
                $args['ids'] = explode(',', $id);
                $data = $p;
                $hlp = $this->FCom_Stock_Model_Sku;
                foreach ($args['ids'] as $id) {
                        $stock = $hlp->load($id);
                        $stock->set('status', $data['status'])->save();
                        $prod = $this->FCom_Catalog_Model_Product->load($stock->get('sku'), 'local_sku');
                        if ($prod) {
                            $data_serialized = $this->BUtil->objectToArray(json_decode($prod->get('data_serialized')));
                            if (!isset($data_serialized['stock_policy']))  {
                                $data_serialized['stock_policy'] = ['out_stock' => $p['out_stock'], 'manage_stock' => $p['status']];
                            } else {
                                $data_serialized['stock_policy']['out_stock'] = $p['out_stock'];
                                $data_serialized['stock_policy']['manage_stock'] = $p['status'];
                            }
                            $prod->setData('stock_policy', $data_serialized['stock_policy']);
                            $prod->save();
                        }
                }
                $this->BResponse->json(['success' => true]);
                break;
            default:
                $this->_processGridDataPost($this->_modelClass);
                break;
        }
    }
}
