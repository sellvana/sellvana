<?php

/**
 * Class Sellvana_IndexTank_Admin_Controller_ProductFunctions
 *
 * @property Sellvana_IndexTank_Index_Product $Sellvana_IndexTank_Index_Product
 * @property Sellvana_IndexTank_Model_ProductFunction $Sellvana_IndexTank_Model_ProductFunction
 * @property Sellvana_IndexTank_Model_ProductField $Sellvana_IndexTank_Model_ProductField
 */
class Sellvana_IndexTank_Admin_Controller_ProductFunctions extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'indextank/product_functions';
    protected $_modelClass = 'Sellvana_IndexTank_Model_ProductFunction';
    protected $_mainTableAlias = 'pf';
    protected $_permission = 'index_tank/product_function';

    public function gridConfig()
    {
        try {
            $status = $this->Sellvana_IndexTank_Index_Product->status();
            $this->BLayout->getView('indextank/product_functions')->set('status', $status);
        } catch (Exception $e) {
            $this->BLayout->getView('indextank/product_functions')->set('status', false);
        }

        $config = parent::gridConfig();
        $config['grid']['columns'] += [
            'number' => ['label' => 'Function Number', 'size' => 5],
            'definition' => ['label' => 'Function definition'],
            'name' => ['label' => 'Function code', 'editable' => true, 'formatter' => 'showlink', 'formatoptions' => [
                'baseLinkUrl' => $this->BApp->href('indextank/product_functions/form'), 'idName' => 'id',
            ]],
            'use_custom_formula' => ['label' => 'Custom Function', 'options' => [1 => 'Yes', 0 => 'No']],
            'label' => ['label' => 'Sorting label'],
            'field_name' => ['label' => 'Sorting field'],
            'sort_order' => ['label' => 'Sorting order', 'options' => ['asc' => 'Ascending', 'desc' => 'Descending']],


        ];

        return $config;
    }

    public function formPostAfter($args)
    {
        /** @var Sellvana_IndexTank_Model_ProductFunction $model */
        $model = $args['model'];

        if ($model) {
            //setup number for new functions
            if ($model->number < 0 || !isset($model->number)) {
                $functions = $this->Sellvana_IndexTank_Model_ProductFunction->getList();
                $freeNumber = -1;
                for ($i = 0; $i < count($functions); $i++) {
                    if (!isset($functions[$i])) {
                        $freeNumber = $i;
                    }
                }
                if ($freeNumber == -1) {
                    $freeNumber = count($functions);
                }
                $model->number = $freeNumber;
            }
            $definition = '';
            $name = '';
            /** @var Sellvana_IndexTank_Model_ProductField $field */
            $field = $this->Sellvana_IndexTank_Model_ProductField->orm()
                    ->where("field_name", $model->field_name)
                    ->where("scoring", 1)
                    ->find_one();
            if ($field) {
                if ('asc' == $model->sort_order) {
                    $definition = "-d[{$field->var_number}]";
                    $name = $model->field_name . '_asc';
                } else {
                    $definition = "d[{$field->var_number}]";
                    $name = $model->field_name . '_desc';
                }
            }
            if ($model->use_custom_formula == false) {
                $model->definition = $definition;
                $model->name = $name;
            }
            $model->save();

            $this->Sellvana_IndexTank_Index_Product->updateFunction($model->number, $model->definition);
        }

        parent::formPostAfter($args);
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $fields = $maxVarField = $this->Sellvana_IndexTank_Model_ProductField->orm()->where('scoring', 1)->order_by_asc('var_number')->find_many();
        $m = $args['model'];
        $m->scoring_fields = $fields;
        $args['view']->set([
            'title' => $m->id ? 'Edit Product Function: ' . $m->name : 'Create New Product Function',
        ]);
    }
}
