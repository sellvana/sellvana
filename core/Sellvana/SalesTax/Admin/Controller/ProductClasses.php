<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_SalesTax_Admin_Controller_ProductClasses
 *
 * @property Sellvana_SalesTax_Model_ProductClass $Sellvana_SalesTax_Model_ProductClass
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 * @property Sellvana_SalesTax_Model_ProductTax $Sellvana_SalesTax_Model_ProductTax
 */
class Sellvana_SalesTax_Admin_Controller_ProductClasses extends FCom_Admin_Controller_Abstract_GridForm {
    protected static $_origClass = __CLASS__;

    protected $_gridHref = 'salestax/product-classes';
    protected $_modelClass = 'Sellvana_SalesTax_Model_ProductClass';
    protected $_gridTitle = 'Product Tax Classes';
    protected $_recordName = 'Product Tax Class';
    protected $_formTitleField = 'title';
    protected $_mainTableAlias = 'tp';
    protected $_navPath = 'sales/tax/product-classes';
    protected $_permission = 'sales/tax/product_classes';

    public function gridConfig() {
        $config = parent::gridConfig();
        //unset($config['form_url']);
        $config['id'] = 'product-class';
        $config['caption'] = 'Product Class';
        $config['columns'] = [
            ['type' => 'row_select'],
            ['type' => 'btn_group', 'buttons' => [['name' => 'edit'], ['name' => 'delete']]],
            ['name' => 'id', 'label' => 'ID', 'width' => 50],
            ['type' => 'input', 'name' => 'title', 'label' => 'Title', 'width' => 300,
                'editable' => true, 'addable' => true,
                'validation' => ['required' => true, 'unique' => $this->BApp->href('salestax/product-classes/unique')]],
        ];
        $config['actions'] = [
            #'new' => array('caption' => 'Add New Product Tax Class', 'modal' => true),
            'edit' => true,
            'delete' => true,
        ];
        $config['filters'] = [
            ['field' => 'title', 'type' => 'text'],
        ];
        $config['new_button'] = '#add_new_product_class';
        return $config;
    }

    public function action_unique__POST() {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->Sellvana_SalesTax_Model_ProductClass->orm()
                                                   ->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        /** @var Sellvana_SalesTax_Model_ProductClass $model */
        $model = $args['model'];
        $data  = $this->BRequest->post();
        if(!empty($data['grid']['products'])){
            if(!empty($data['grid']['products']['add'])){
                // add ProductTax models
                $addIds = explode(',', $data['grid']['products']['add']);
                foreach ($addIds as $id) {
                    $this->Sellvana_SalesTax_Model_ProductTax
                        ->create(['product_id'=>(int)trim($id), 'product_class_id' => $model->id()])
                        ->save();
                }

            }

            if(!empty($data['grid']['products']['del'])){
                // del ProductTax models
                $rmIds = explode(',', $data['grid']['products']['del']);
                $toDel = $this->Sellvana_SalesTax_Model_ProductTax
                    ->orm()->where('product_class_id', $model->id())
                    ->where(['product_id' => $rmIds])->find_many();
                if($toDel){
                    foreach ($toDel as $d) {
                        $d->delete();
                    }

                }
            }
        }
    }

    /**
     * @param $model Sellvana_SalesTax_Model_ProductClass
     * @return mixed
     */
    public function productTaxGridConfig($model)
    {
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')
                                                    ->select([
                                                        'p.id',
                                                        'p.product_name',
                                                        'p.product_sku'
                                                    ])->join($this->Sellvana_SalesTax_Model_ProductTax->table(), 'pt.product_id=p.id', 'pt')
                                                    ->where('pt.product_class_id', $model->id());

        $gridId = 'product_tax_grid';

        $config['config'] = [
            'id'                 => $gridId,
            'data'               => null,
            'data_mode'          => 'local',
            'columns'            => [
                ['type' => 'row_select'],
                ['name' => 'id', 'label' => 'ID', 'index' => 'p.id', 'width' => 80, 'hidden' => true],
                ['name' => 'product_name', 'label' => 'Name', 'index' => 'p.product_name', 'width' => 400],
                ['name' => 'product_sku', 'label' => 'SKU', 'index' => 'p.product_sku', 'width' => 200],
            ],
            'actions'            => [
                #'add' => ['caption' => 'Add products'],
                'delete'              => ['caption' => 'Remove'],
                'add-tax-product' => [
                    'caption'  => 'Add Tax Products',
                    'type'     => 'button',
                    'id'       => 'add-tax-product-from-grid',
                    'class'    => 'btn-primary',
                    'callback' => 'showModalToAddTaxProduct'
                ]
            ],
            'filters'            => [
                ['field' => 'product_name', 'type' => 'text'],
                ['field' => 'product_sku', 'type' => 'text']
            ],
            'events'             => ['init', 'add', 'mass-delete'],
            'grid_before_create' => $gridId . '_register'
        ];

        $data = $this->BDb->many_as_array($orm->find_many());

        $config['config']['data'] = $data;


        $config['config']['callbacks'] = [
            'componentDidMount' => 'setTaxProdMainGrid'
        ];

        return $config;
    }

}
