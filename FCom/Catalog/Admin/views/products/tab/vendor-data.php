<?=$this->view('jqgrid')->set('config', array(
    'grid' => array(
        'id' => 'product_vendors',
        'caption' => 'Product Vendors',
        'url' => BApp::url('Denteva_Merge', '/products/subgrid_data?id='.$this->model->id),
        'columns' => array(
            'id' => array('label'=>'ID', 'index'=>'pv.id', 'width'=>30),
            'vendor_code' => array('label'=>'Vendor', 'index'=>'pv.vendor_code', 'width'=>80),
            'manuf_sku' => array('label'=>'Mfr Part#', 'index'=>'pv.manuf_sku', 'width'=>80),
            'vendor_sku' => array('label'=>'Vendor SKU', 'index'=>'pv.vendor_sku', 'width'=>80),
            'product_name' => array('label'=>'Description', 'index'=>'pv.product_name', 'width'=>300),
            'manuf_name' => array('label'=>'Mfr', 'index'=>'pv.manuf_name', 'width'=>150),
            'price' => array('label'=>'Cost', 'index'=>'pv.price', 'width'=>70, 'formatter'=>'currency'),
        ),
        'multiselect' => true,
    ),
    'custom'=>array('personalize'=>true),
    'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
    array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Vendor', 'cursor'=>'pointer'),
    array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Vendor', 'cursor'=>'pointer'),
)) ?>