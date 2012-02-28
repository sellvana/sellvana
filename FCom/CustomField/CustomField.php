<?php

class FCom_CustomField extends BClass
{
    protected $_types;

    public static function bootstrap()
    {
        switch (FCom::area()) {
            case 'FCom_Admin': FCom_CustomField_Admin::bootstrap(); break;
            case 'FCom_Frontend': FCom_CustomField_Frontend::bootstrap(); break;
        }

        BPubSub::i()
            ->on('FCom_Catalog_Model_Product::find_one.orm', 'FCom_CustomField.productFindORM')
            ->on('FCom_Catalog_Model_Product::find_many.orm', 'FCom_CustomField.productFindORM')
            // is there save on frontend?
            //->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField.productAfterSave')
        ;

    }

    public function productFindORM($args)
    {
        $tP = $args['orm']->table_alias();
        $args['orm']->select($tP.'.*')
            ->left_outer_join('FCom_CustomField_Model_ProductField', array('pcf.product_id','=',$tP.'.id'), 'pcf')
        ;
        $fields = FCom_CustomField_Model_Field::i()->fieldsInfo('product', true);
        $args['orm']->select($fields);
    }
}

class FCom_CustomField_Admin extends BClass
{
    public static function bootstrap()
    {
        $ctrl = 'FCom_CustomField_Admin_Controller_FieldSets.';
        BFrontController::i()
            ->route('GET /fieldsets', $ctrl.'index')
            ->route('GET|POST /fieldsets/grid_data', $ctrl.'grid_data')
            ->route('GET|POST /fieldsets/set_field_grid_data', $ctrl.'set_field_grid_data')
            ->route('GET|POST /fieldsets/field_grid_data', $ctrl.'field_grid_data')
            ->route('GET|POST /fieldsets/field_option_grid_data', $ctrl.'field_option_grid_data')

            ->route('GET|POST /fieldsets/form/:id', $ctrl.'form')
            ->route('GET /fieldsets/form_tab/:id', $ctrl.'form_tab')

            ->route('GET /products/fields_partial/:id', 'FCom_CustomField_Admin_Controller_Products.fields_partial')
        ;

        BLayout::i()
            ->allViews('Admin/views', 'customfield')
        ;

        BPubSub::i()
            ->on('BLayout::theme.load.after', 'FCom_CustomField_Admin::layout')
            ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField_Admin.productAfterSave')
            ->on('FCom_Catalog_Admin_Controller_Products::gridColumns', 'FCom_CustomField_Admin.productGridColumns');
        ;
    }

    public static function layout()
    {
        BLayout::i()
            ->layout(array(
                'base'=>array(
                    array('view', 'root', 'do'=>array(
                        array('addNav', 'catalog/fieldsets', array('label'=>'Field Sets', 'href'=>BApp::url('FCom_CustomField', '/fieldsets'))),
                    )),
                ),
                'catalog_product_form_tabs'=>array(
                    array('view', 'catalog/products/form',
                        'do'=>array(
                            array('addTab', 'fields', array('label' => 'Custom Fields', 'pos'=>'15', 'view'=>'customfield/products/fields-tab')),
                        ),
                    ),
                ),
                '/customfield/fieldsets'=>array(
                    array('layout', 'base'),
                    array('hook', 'main', 'views'=>array('customfield/fieldsets')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),
                '/customfield/fieldsets/form'=>array(
                    array('layout', 'base'),
                    array('layout', 'form'),
                    array('hook', 'main', 'views'=>array('customfield/fieldsets/form')),
                    array('view', 'root', 'do'=>array(array('setNav', 'catalog/fieldsets'))),
                ),
            ));
    }

    public function productAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = FCom_CustomField_Model_Field::i()->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = FCom_CustomField_Model_ProductField::i()->load($p->id, 'product_id');
            if (!$custom) {
                $custom = FCom_CustomField_Model_ProductField::i()->create();
            }
            $custom->set('product_id', $p->id)->set($data)->save();
        }
        // not deleting to preserve meta info about fields
    }

    public function productGridColumns($args)
    {
        $fields = FCom_CustomField_Model_Field::i()->orm('f')->find_many();
        foreach ($fields as $f) {
            $col = array('label'=>$f->field_name, 'index'=>'pcf.'.$f->field_name, 'hidden'=>true);
            if ($f->admin_input_type=='select') {
                $col['options'] = FCom_CustomField_Model_FieldOption::i()->orm()
                    ->where('field_id', $f->id)
                    ->find_many_assoc('id', 'label');
            }
            $args['columns'][$f->field_code] = $col;
        }
    }
}

class FCom_CustomField_Frontend extends BClass
{
    public static function bootstrap()
    {

    }
}

class FCom_CustomField_Model_Set extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_fieldset';
}

class FCom_CustomField_Model_Field extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_field';

    protected static $_fieldOptions = array(
        'field_type' => array(
            'product' => 'Products',
        ),
        'table_field_type' => array(
            'date' => 'Date',
            'datetime' => 'Date/Time',
            'decimal(12,4)' => 'Decimal',
            'int(11)' => 'Integer',
            'text' => 'Long Text',
            'varchar(255)' => 'Short Text',
        ),
        'admin_input_type' => array(
            'text' => 'Text Line',
            'textarea' => 'Text Area',
            'select' => 'Drop down',
        ),
    );

    protected static $_fieldTypes = array(
        'product' => array(
            'class' => 'FCom_CustomField_Model_ProductField',
        ),
    );

    protected $_oldTableFieldCode;

    protected static $_fieldsCache = array();

    public function tableName()
    {
        if (empty(static::$_fieldTypes[$this->field_type])) {
            return null;
        }
        $class = static::$_fieldTypes[$this->field_type]['class'];
        return $class::table();
    }

    public static function fieldsInfo($type, $keysOnly=false)
    {
        if (empty(static::$_fieldsCache[$type])) {
            $class = static::$_fieldTypes[$type]['class'];
            $fields = BDb::ddlFieldInfo($class::table());
            unset($fields['id'], $fields['product_id']);
            static::$_fieldsCache[$type] = $fields;
        }
        return $keysOnly ? array_keys(static::$_fieldsCache[$type]) : static::$_fieldsCache[$type];
    }

    public function afterLoad()
    {
        $this->_oldTableFieldCode = $this->field_code;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->field_type) $this->field_type = 'product';
        return true;
    }

    public function afterSave()
    {
        $fTable = $this->tableName();
        $fCode = preg_replace('#([^0-9a-z_])#', '', $this->field_code);
        $fType = preg_replace('#([^0-9a-z\(\),])#', '', $this->table_field_type);
        $field = BDb::ddlFieldInfo($fTable, $this->field_code);
        if (!$field) {
            BDb::run("ALTER TABLE {$fTable} ADD COLUMN {$fCode} {$fType}");
        } elseif ($field->Type!=$fType || $this->_oldTableFieldCode!=$fCode) {
            BDb::run("ALTER TABLE {$fTable} CHANGE COLUMN {$this->_oldTableFieldCode} {$fCode} {$fType}");
        }
    }

    public function afterDelete()
    {
        BDb::run("ALTER TABLE {$this->tableName()} DROP COLUMN {$this->field_code}");
    }
}

class FCom_CustomField_Model_SetField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_fieldset_field';
}

class FCom_CustomField_Model_FieldOption extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_field_option';
}

class FCom_CustomField_Model_ProductField extends FCom_Core_Model_Abstract
{
    protected static $_table = 'a_product_custom';

    public function productFields($p, $r=array())
    {
        $where = array();
        if ($p->_fieldset_ids || !empty($r['add_fieldset_ids'])) {
            $addSetIds = BUtil::arrayCleanInt($p->_fieldset_ids);
            if (!empty($r['add_fieldset_ids'])) {
                $addSetIds += BUtil::arrayCleanInt($r['add_fieldset_ids']);
            }
            $where['OR'][] = "f.id IN (SELECT field_id FROM ".FCom_CustomField_Model_SetField::table()
                ." WHERE set_id IN (".join(',', $addSetIds)."))";
                $p->_fieldset_ids = join(',', array_unique($addSetIds));
        }

        if ($p->_add_field_ids || !empty($r['add_field_ids'])) {
            $addFieldIds = BUtil::arrayCleanInt($p->_add_field_ids);
            if (!empty($r['add_field_ids'])) {
                $addFieldIds += BUtil::arrayCleanInt($r['add_field_ids']);
            }
            $where['OR'][] = "f.id IN (".join(',', $addFieldIds).")";
            $p->_add_field_ids = join(',', array_unique($addFieldIds));
        }

        if ($p->_hide_field_ids || !empty($r['hide_field_ids'])) {
            $hideFieldIds = BUtil::arrayCleanInt($p->_hide_field_ids);
            if (!empty($r['hide_field_ids'])) {
                $hideFieldIds += BUtil::arrayCleanInt($r['hide_field_ids']);
            }
            $where[] = "f.id NOT IN (".join(',', $hideFieldIds).")";
            $p->_hide_field_ids = join(',', array_unique($hideFieldIds));
        }

        if (!$where) {
            $fields = array();
        } else {
            $fields = FCom_CustomField_Model_Field::i()->orm('f')->where_complex($where)->find_many_assoc();
        }
        return $fields;
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) return false;
        if (!$this->product_id) return false;
        if (!$this->id && ($exists = static::i()->load($this->product_id, 'product_id'))) {
            return false;
        }
        return true;
    }
}