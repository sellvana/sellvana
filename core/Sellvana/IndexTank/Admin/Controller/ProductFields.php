<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_IndexTank_Admin_Controller_ProductFields
 *
 * @property Sellvana_IndexTank_Index_Product $Sellvana_IndexTank_Index_Product
 * @property Sellvana_IndexTank_Model_ProductField $Sellvana_IndexTank_Model_ProductField
 */
class Sellvana_IndexTank_Admin_Controller_ProductFields extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'indextank/product_fields';
    protected $_modelClass = 'Sellvana_IndexTank_Model_ProductField';
    protected $_mainTableAlias = 'pf';
    protected $_permission = 'index_tank/product_field';

    public function gridConfig()
    {
        try {
            $status = $this->Sellvana_IndexTank_Index_Product->status();
            $this->BLayout->view('indextank/product_fields')->set('status', $status);
        } catch (Exception $e) {
            $this->BLayout->view('indextank/product_fields')->set('status', false);
        }

        $fld = $this->Sellvana_IndexTank_Model_ProductField;
        $config = parent::gridConfig();
        $config['grid']['columns'] += [
            'field_nice_name' => ['label' => 'Name', 'editable' => true, 'formatter' => 'showlink', 'formatoptions' => [
                'baseLinkUrl' => $this->BApp->href('indextank/product_fields/form'), 'idName' => 'id',
            ]],
            'search' => ['label' => 'Search', 'options' => $fld->fieldOptions('search')],
            'facets' => ['label' => 'Facets', 'options' => $fld->fieldOptions('facets')],
            'scoring' => ['label' => 'Scoring', 'options' => $fld->fieldOptions('scoring')],
            'var_number' => ['label' => 'Scoring variable #'],
            'priority' => ['label' => 'Priority'],
            'filter' => ['label' => 'Filter type'],
            'source_type' => ['label' => 'Source type'],
            'source_value' => ['label' => 'Source'],
        ];

        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set([
            'title' => $m->id ? 'Edit Product Field: ' . $m->field_nice_name : 'Create New Product Field',
        ]);
    }

    public function formPostAfter($args)
    {
        $model = $args['model'];
        if ($model) {
            if ($model->scoring && ($model->var_number == -1 || !isset($model->var_number))) {
                /** @var Sellvana_IndexTank_Model_ProductField $maxVarField */
                $maxVarField = $this->Sellvana_IndexTank_Model_ProductField->orm()->select_expr("max(var_number) as max_var")->find_one();
                $model->var_number = $maxVarField->max_var + 1;
                $model->save();
            } elseif (0 == $model->scoring && $model->var_number >= 0) {
                $model->var_number = -1;
                $model->save();
            }
        }

        parent::formPostAfter($args);
    }

}
