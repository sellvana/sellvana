<?php

/**
 * Class Sellvana_Cms_Admin
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_Cms_Model_Block $Sellvana_Cms_Model_Block
 */
class Sellvana_Cms_Main extends BClass
{
    public function onLayoutEditorFetchLibrary($args)
    {
        $this->FCom_Core_LayoutEditor
            ->addWidgetType('cms_block', [
                'title' => (('CMS Block')),
                'pos' => 50,
                'options' => $this->Sellvana_Cms_Model_Block->getAllBlocksAsOptions(),
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

