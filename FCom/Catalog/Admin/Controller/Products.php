<?php

class FCom_Catalog_Admin_Controller_Products extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_modelClass = 'FCom_Catalog_Model_Product';
    protected $_gridHref = 'catalog/products';
    protected $_gridTitle = 'Products';
    protected $_recordName = 'Product';
    protected $_mainTableAlias = 'p';

    public function gridColumns()
    {
        $columns = array(
            'id'=>array('label'=>'ID', 'index'=>'p.id', 'width'=>55, 'hidden'=>true, 'frozen'=>true),
            'product_name'=>array('label'=>'Name', 'index'=>'p.product_name', 'width'=>250, 'frozen'=>true),
            'manuf_sku'=>array('label'=>'Mfr Part #', 'index'=>'p.manuf_sku', 'width'=>100),
            'create_dt'=>array('label'=>'Created', 'index'=>'p.create_dt', 'formatter'=>'date', 'width'=>100),
            'uom'=>array('label'=>'UOM', 'index'=>'p.uom', 'width'=>60),
        );
        BPubSub::i()->fire(__METHOD__, array('columns'=>&$columns));
        return $columns;
    }

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['id'] = 'products';
        $config['grid']['columns'] = $this->gridColumns();
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        $orm->use_index('primary');
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'sidebar_img' => $m->thumbUrl(98),
            'title' => $m->id ? 'Edit Product: '.$m->product_name : 'Create New Product',
        ));
    }

    public function linked_categories_data()
    {
        $result = array('check_3', 'check_2','check_5','check_7');
        return BUtil::toJson($result);
    }

    public function productLibraryGridConfig($gridId='products')
    {
        $columns = $this->gridColumns();
        unset($columns['product_name']['formatter'], $columns['product_name']['formatoptions']);
        $columns['create_dt']['hidden'] = true;
        $config = $this->gridConfig();
        $config['grid']['autowidth'] = false;
        $config['grid']['caption'] = 'All products';
        $config['grid']['multiselect'] = true;
        $config['grid']['height'] = '100%';
        $config['grid']['columns'] = $columns;
        $config['navGrid'] = array('add'=>false, 'edit'=>false, 'del'=>false);
        $config['custom']['personalize'] = 'products';
        //$config['custom']['autoresize'] = '#linked-products-layout';
        return $config;
    }

    public function linkedProductGridConfig($model, $type)
    {
        $orm = FCom_Catalog_Model_Product::i()->orm()->table_alias('p')
            ->select(array('p.id', 'p.product_name', 'p.manuf_sku'));

        switch ($type) {
        case 'related': case 'similar':
            $orm->join('FCom_Catalog_Model_ProductLink', array('pl.linked_product_id','=','p.id'), 'pl')
                ->where('link_type', $type)
                ->where('pl.product_id', $model ? $model->id : 0);

            //TODO: flexibility for more types
            $caption = $type=='related' ? 'Related Products' : 'Similar Products';
            break;

        case 'family':
            $family = FCom_Catalog_Model_ProductFamily::i()->orm()->table_alias('pf')
                ->where('pf.product_id', $model ? $model->id : 0)
                ->join('FCom_Catalog_Model_Family', array('f.id','=','pf.family_id'), 'f')
                ->select('f.id')->select('f.family_name')
                ->find_one();

            $orm->join('FCom_Catalog_Model_ProductFamily', array('pf.product_id','=','p.id'), 'pf')
                ->where('pf.family_id', $family ? $family->id : 0);

            $vendorName = $model ? htmlspecialchars($model->manuf_vendor_name) : '';
            $vendorId = $model ? $model->manuf_vendor_id : '';
            $caption = 'Family Products '
.'<input type="text" id="family-autocomplete" name="family_name" style="width:100px" value="'
    .($family ? htmlspecialchars($family->family_name) : '').'"/>'
.'<input type="hidden" id="family-id" name="family_id" value="'.($family ? $family->id : '').'"/>'
.'<button type="button" id="family-new" title="New Family"><span class="ui-icon ui-icon-plus"></span></button>'
.'<button type="button" id="family-rename" title="Rename Family"><span class="ui-icon ui-icon-pencil"></span></button>'
.' Mfr: <input type="text" id="family-manuf-autocomplete" style="width:100px" value="'.$vendorName.'">'
.'<input type="hidden" id="family-manuf-id" name="manuf_id" value="'.$vendorId.'"/>'
;
            break;
        }

        BPubSub::i()->fire(__METHOD__.'.orm', array('type'=>$type, 'orm'=>$orm));
        $data = BDb::many_as_array($orm->find_many());

        $gridId = 'linked_products_'.$type;
        $config = array(
            'grid' => array(
                'id'            => $gridId,
                'data'          => $data,
                'datatype'      => 'local',
                'caption'       => $caption,
                'colModel'      => array(
                    array('name'=>'id', 'label'=>'ID', 'index'=>'p.id', 'width'=>40, 'hidden'=>true),
                    array('name'=>'product_name', 'label'=>'Name', 'index'=>'product_name', 'width'=>250),
                    array('name'=>'manuf_sku', 'label'=>'Mfr Part #', 'index'=>'manuf_sku', 'width'=>70),
                ),
                'rowNum'        => 10,
                'sortname'      => 'p.product_name',
                'sortorder'     => 'asc',
                'autowidth'     => false,
                'multiselect'   => true,
                'shrinkToFit' => true,
                'forceFit' => true,
            ),
            'navGrid' => array('add'=>false, 'edit'=>false, 'search'=>false, 'del'=>false, 'refresh'=>false),
            array('navButtonAdd', 'caption' => 'Add', 'buttonicon'=>'ui-icon-plus', 'title' => 'Add Products'),
            array('navButtonAdd', 'caption' => 'Remove', 'buttonicon'=>'ui-icon-trash', 'title' => 'Remove Products'),
        );

        BPubSub::i()->fire(__METHOD__.'.config', array('type'=>$type, 'config'=>&$config));

        return $config;
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        extract($args);
        $this->processCategoriesPost($model);
        $this->processLinkedProductsPost($model, $data);
        $this->processMediaPost($model, $data);
        $this->processFamilyProductsPost($model, $data);
    }

    public function processCategoriesPost($model)
    {
        $post = BRequest::i()->post();
        $categoreis = array();
        foreach($post as $key => $value){
            $matches = array();
            if(preg_match("#check_(\d+)#", $key, $matches)){
                $categoreis[] = intval($matches[1]);
            }
        }
        if (!empty($categoreis)){
            $cat_product = FCom_Catalog_Model_CategoryProduct::i();
            $category_model = FCom_Catalog_Model_Category::i();
            foreach($categoreis as $cat_id){
                $product = $cat_product->orm()->where('product_id', $model->id())->where('category_id', $cat_id)->find_one();
                if(0 == $value && $product){
                    $product->delete();
                }elseif(false == $product){
                    $category = $category_model->load($cat_id);
                    if(!$category){
                        continue;
                    }
                    $category_ids = explode("/",$category->id_path);
                    foreach($category_ids as $c_id) {
                        $product = $cat_product->orm()->where('product_id', $model->id())->where('category_id', $c_id)->find_one();
                        if(false == $product){
                            $data=array('product_id' => $model->id(), 'category_id'=>$c_id);
                            FCom_Catalog_Model_CategoryProduct::create($data)->save();
                        }
                    }
                }
            }
        }
    }
    public function processLinkedProductsPost($model, $data)
    {
        $hlp = FCom_Catalog_Model_ProductLink::i();
        foreach (array('related', 'similar') as $type) {
            $typeName = 'linked_products_'.$type;
            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many(array(
                    'product_id' => $model->id,
                    'link_type' => $type,
                    'linked_product_id' => explode(',', $data['grid'][$typeName]['del']),
                ));
            }
            if (!empty($data['grid'][$typeName]['add'])) {
                $oldLinks = $hlp->orm()->where('link_type', $type)->where('product_id', $model->id)
                    ->find_many_assoc('linked_product_id');
                foreach (explode(',', $data['grid'][$typeName]['add']) as $linkedId) {
                    if ($linkedId && empty($oldLinks[$linkedId])) {
                        $m = $hlp->create(array(
                            'product_id' => $model->id,
                            'link_type' => $type,
                            'linked_product_id' => $linkedId,
                        ))->save();
#echo "<pre>"; print_r($m->as_array()); echo "</pre>";
                    }
                }
            }
        }
#exit;
        return $this;
    }

    public function processFamilyProductsPost($model, $data)
    {
        if (empty($data['family_id'])) {
            return;
        }
        $hlp = FCom_Catalog_Model_ProductFamily::i();
        $pf = $hlp->load($model->id, 'product_id');
        $fId = $pf ? $pf->family_id : null;
        if ($pf && !empty($data['family_id']) && $data['family_id']!=$pf->family_id) {
            $pf->delete();
        }
        if ($data['family_id']) {
            if ($fId!=$data['family_id']) {
                $hlp->create(array('family_id'=>$data['family_id'], 'product_id'=>$model->id))->save();
            }
            if (!empty($data['grid']['linked_products_family']['add'])) {
                foreach (explode(',', $data['grid']['linked_products_family']['add']) as $id) {
                    if (!$id) continue;
                    $hlp->delete_many(array('product_id'=>$id));
                    $hlp->create(array('family_id'=>$data['family_id'], 'product_id'=>$id))->save();
                }
            }
            if (!empty($data['grid']['linked_products_family']['del'])) {
                $pIds = explode(',', $data['grid']['linked_products_family']['del']);
                $hlp->delete_many(array('family_id'=>$data['family_id'], 'product_id'=>$pIds));
            }
        }
    }

    public function processMediaPost($model, $data)
    {
        $hlp = FCom_Catalog_Model_ProductMedia::i();
        foreach (array('A'=>'attachments', 'I'=>'images') as $type=>$typeName) {
            $typeName = 'product_'.$typeName;
            if (!empty($data['grid'][$typeName]['del'])) {
                $hlp->delete_many(array(
                    'product_id' => $model->id,
                    'media_type' => $type,
                    'file_id'    => explode(',', $data['grid'][$typeName]['del']),
                ));
            }
            if (!empty($data['grid'][$typeName]['add'])) {
//echo "<pre>"; print_r($data['grid'][$typeName]['add']);
                $oldAtt = $hlp->orm()->where('product_id', $model->id)->where('media_type', $type)
                    ->find_many_assoc('file_id');
//print_r(BDb::many_as_array($oldAtt));
                foreach (explode(',', $data['grid'][$typeName]['add']) as $attId) {
                    if ($attId && empty($oldAtt[$attId])) {
//try {
//    echo 1;
                        $m = $hlp->create(array(
                            'product_id' => $model->id,
                            'media_type' => $type,
                            'file_id' => $attId,
                        ))->save();
//    print_r($m->as_array());
//} catch (Exception $e) {
//    echo 2;
//    Debug::exceptionHandler($e);
//}
                    }
                }
//echo "</pre>";
//exit;
            }
        }
        return $this;
    }

    public function onMediaGridConfig($args)
    {
        array_splice($args['config']['grid']['colModel'], -1, 0, array(
            array('name'=>'manuf_vendor_name', 'label'=>'Manufacturer', 'width'=>150, 'index'=>'v.vendor_name', 'editable'=>true),
        ));
    }

    public function onMediaGridGetORM($args)
    {
        $args['orm']->join('FCom_Catalog_Model_ProductMedia', array('pa.file_id','=','a.id',), 'pa')
            ->where_null('pa.product_id')->where('media_type', $args['type'])
            ->select(array('pa.manuf_vendor_id'));
    }

    public function onMediaGridUpload($args)
    {
        $hlp = FCom_Catalog_Model_ProductMedia::i();
        $id = $args['model']->id;
        if (!$hlp->load(array('product_id'=>null, 'file_id'=>$id))) {
            $hlp->create(array('file_id' => $id, 'media_type'=>$args['type']))->save();
        }
    }

    public function onMediaGridEdit($args)
    {
        $r = BRequest::i();
        $m = Denteva_Model_Vendor::i()->load(array(
            'is_manuf' => 1,
            'vendor_name' => $r->post('manuf_vendor_name')
        ));
        FCom_Catalog_Model_ProductMedia::i()
            ->load(array('product_id'=>null, 'file_id'=>$args['model']->id))
            ->set(array(
                'manuf_vendor_id' => $m ? $m->id : null,
            ))
            ->save();
    }
}