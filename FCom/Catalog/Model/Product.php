<?php

class FCom_Catalog_Model_Product extends FCom_Core_Model_Abstract
{
    protected static $_origClass = __CLASS__;
    protected static $_table = 'fcom_product';

    public static function stockStatusOptions($onlyAvailable=false)
    {
        $options = array(
            'in_stock' => 'In Stock',
            'backorder' => 'On Backorder',
            'special_order' => 'Special Order',
        );
        if (!$onlyAvailable) {
            $options += array(
                'do_not_carry' => 'Do Not Carry',
                'temp_unavail' => 'Temporarily Unavailable',
                'vendor_disc' => 'Supplier Discontinued',
                'manuf_disc' => 'MFR Discontinued',
            );
        }
        return $options;
    }

    public function url($category=null)
    {
        return BApp::href(($category ? $category->url_path.'/' : '').$this->url_key);
    }

    public function imageUrl($full=false)
    {
        $url = $full ? BApp::src('FCom_Catalog').'/' : '';
        return $url.'media/'.($this->image_url ? $this->image_url : 'DC642702.jpg');
    }

    public function thumbUrl($w, $h=null)
    {
        return FCom_Core::i()->resizeUrl().'?f='.urlencode($this->imageUrl()).'&s='.$w.'x'.$h;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;

        //todo: check out for unique url_key before save
        if (!$this->get('url_key')) $this->generateUrlKey();

        return true;
    }

    public function afterSave()
    {
        if (!parent::afterSave()) return false;

        //todo: setup unique uniq_id
        if (!$this->get('unique_id')) {
            $this->set('unique_id', $this->id);
            $this->save();
        }

        return true;
    }

    public function generateUrlKey()
    {
        //$key = $this->manuf()->manuf_name.'-'.$this->manuf_sku.'-'.$this->product_name;
        $key = $this->product_name;
        $this->set('url_key', BLocale::transliterate($key));
        return $this;
    }

    public function onAssociateCategory($args)
    {
        $catId = $args['id'];
        $prodIds = $args['ref'];
        //todo
    }

    /**
     * Find all categories which belong to product
     * @return type
     */
    public function categories($includeAscendants=false)
    {
        $categories = FCom_Catalog_Model_CategoryProduct::i()->orm('cp')
            ->join('FCom_Catalog_Model_Category', array('cp.category_id','=','c.id'), 'c')
            ->where('cp.product_id', $this->id())->find_many_assoc();

        if ($includeAscendants) {
            $ascIds = array();
            foreach ($categories as $cat) {
                foreach (explode('/', $cat->id_path) as $id) {
                    if ($id>1 && empty($categories[$id])) {
                        $ascIds[$id] = 1;
                    }
                }
            }
            if ($ascIds) {
                $hlp = FCom_Catalog_Model_CategoryProduct::i();
                $ascendants = FCom_Catalog_Model_Category::i()->orm()->where_in('id', array_keys($ascIds))->find_many();
                foreach ($ascendants as $cat) {
                    $categories[$cat->id] = $hlp->create($cat->as_array());
                }
            }
        }
        return $categories;
    }
/*
    public function customFields($product)
    {
        return FCom_CustomField_Model_ProductField::i()->productFields($product);
    }
*/
    public function customFieldsShowOnFrontend()
    {
        $result = array();
        $fields = FCom_CustomField_Model_ProductField::i()->productFields($this);
        if ($fields) {
            foreach ($fields as $f) {
                if ($f->frontend_show) {
                    $result[] = $f;
                }
            }
        }
        return $result;
    }


    public function mediaORM($type)
    {
        return FCom_Catalog_Model_ProductMedia::i()->orm()->table_alias('pa')
            ->where('pa.product_id', $this->id)->where('pa.media_type', $type)
            //->select(array('pa.manuf_vendor_id'))
            ->join('FCom_Core_Model_MediaLibrary', array('a.id','=','pa.file_id'), 'a')
            ->select(array('a.id', 'a.file_name', 'a.file_size'));
    }

    public function media($type)
    {
        return $this->mediaORM($type)->find_many_assoc();
    }

    static public function import($data)
    {
        if (empty($data) || !is_array($data)) {
            return;
        }

        foreach($data as $d) {
            $categoriesPath = array();
            if (!empty($d['categories'])) {
                $categoriesPath = explode(";", $d['categories']);
                unset($d['categories']);
            }

            $imagesNames = array();
            if (!empty($d['images'])) {
                $imagesNames = explode(";", $d['images']);
                unset($d['images']);
            }

            //HANDLE PRODUCT
            try {
                $p = self::orm()->create($d)->save();
            } catch (Exception $e) {
                $p = self::orm()->where("unique_id", $d['unique_id'])->find_one();
            }

            if (!$p) {
                continue;
            }

            $p->set($d);
            $p->save();

            //HANDLE CUSTOM FIELDS
            //
            //find intersection of custom fields with data fields
            $cfFields = FCom_CustomField_Model_Field::i()->getListAssoc();
            $cfKeys = array_keys($cfFields);
            $dataKeys = array_keys($d);
            $cfIntersection = array_intersect($cfKeys, $dataKeys);

            //get custom fields values from data
            //add new options if necessary
            $customFields = array();
            $fieldIds = array();
            foreach($cfIntersection as $cfk) {
                $field = $cfFields[$cfk];
                $dataValue = $d[$cfk];
                $options = FCom_CustomField_Model_FieldOption::i()->getListAssocById($field->id());
                if($options) {
                    if (!isset($options[$dataValue])) {
                        FCom_CustomField_Model_FieldOption::orm()->create(array('field_id' => $field->id(), 'label'=>$dataValue))->save();
                    }
                }
                $customFields[$cfk] = $dataValue;
                $fieldIds[] = $field->id();
            }
            //get or create custom field
            $custom = FCom_CustomField_Model_ProductField::orm()->where("product_id", $p->id)->find_one();
            if ($custom) {
                if (!empty($custom->_add_field_ids)) {
                    $custom->_add_field_ids = "," . implode(",",$fieldIds);
                } else {
                    $custom->_add_field_ids = implode(",",$fieldIds);
                }
                $custom->set($customFields);
                $custom->save();
            } else {
                $customFields['product_id'] = $p->id();
                $customFields['_add_field_ids'] = implode(",",$fieldIds);
                $custom = FCom_CustomField_Model_ProductField::i()->create($customFields)->save();
                $custom->product_id = $p->id;
            }

            //HANDLE CATEGORIES
            if (!empty($categoriesPath)) {
                //check if parent category exist
                $topParentCategory = FCom_Catalog_Model_Category::orm()->where_null("parent_id")->find_one();
                if (!$topParentCategory) {
                    $topParentCategory = FCom_Catalog_Model_Category::orm()->create(array('parent_id'=>null))->save();
                }

                //check if categories exists
                //create new categories if not
                $categories = array();
                foreach($categoriesPath as $catpath) {
                    $catNodes = explode(">", $catpath);
                    $parent = $topParentCategory;
                    foreach($catNodes as $catnode) {
                        $category = FCom_Catalog_Model_Category::orm()->where("node_name", $catnode)->find_one();
                        if (!$category) {
                            $category = $parent->createChild($catnode);
                        }
                        $parent = $category;
                        $categories[$catpath] = $category;
                    }
                }

                //assign products to categories
                foreach($categories as $category) {
                    $catProduct = FCom_Catalog_Model_CategoryProduct::i()->orm()
                        ->where('product_id', $p->id())
                        ->where('category_id', $category->id())
                        ->find_one();
                    if (!$catProduct) {
                        FCom_Catalog_Model_CategoryProduct::orm()->create(array('product_id' => $p->id(), 'category_id'=>$category->id()))->save();
                    }
                }
            }


            //HANDLE IMAGES
            if (!empty($imagesNames)) {
                $mediaLib = FCom_Core_Model_MediaLibrary::i();
                $productMedia = FCom_Catalog_Model_ProductMedia::i();
                $imageFolder = BConfig::i()->get('fs/image_folder');

                foreach($imagesNames as $imagesString) {
                    $images = explode(">", $imagesString);
                    foreach($images as $fileName) {
                        $att = $mediaLib->load(array('folder'=>$imageFolder, 'file_name'=>$fileName));
                        if (!$att) {
                            $fullPathToFile = FULLERON_ROOT_DIR.'/'.$imageFolder.'/'.$fileName;
                            $size = 0;
                            if (file_exists($fullPathToFile)) {
                                $size = filesize($fullPathToFile);
                            }
                            $pathinfo = pathinfo($fileName);
                            $att = $mediaLib->create(array(
                                'folder'    => $imageFolder,
                                'subfolder' => $pathinfo['dirname'] == '.' ? null : $pathinfo['dirname'],
                                'file_name' => $pathinfo['basename'],
                                'file_size' => $size,
                            ))->save();
                        }
                        $fileId = $productMedia->orm()->where('product_id', $p->id())
                                ->where('file_id', $att->id())->find_one();
                        if (!$fileId) {
                            $fileId = $productMedia->create(array(
                                'product_id' => $p->id(),
                                'media_type' => 'images',
                                'file_id' => $att->id(),
                            ))->save();
                        }
                    }
                }
            }
        }
    }
}

