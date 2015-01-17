<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Cms_Admin
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property FCom_Cms_Model_Block $FCom_Cms_Model_Block
 */
class FCom_Cms_Main extends BClass
{
    public function onLayoutEditorFetchLibrary($args)
    {
        $this->FCom_Core_LayoutEditor
            ->addWidgetType('cms_block', [
                'title' => 'CMS Block',
                'pos' => 50,
                'options' => $this->FCom_Cms_Model_Block->getAllBlocksAsOptions(),
                'compile' => function ($args) {
                    $w = $args['widget'];
                    $viewName = uniqid();
                    $args['layout'][] = ['cms_block' => $w['value'], 'view_name' => $viewName];
                    $args['layout'][] = ['hook' => $w['area'], 'views' => $viewName];
                }
            ])
        ;
    }
}

