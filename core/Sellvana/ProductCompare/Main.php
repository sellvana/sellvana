<?php

/**
 * Class Sellvana_ProductCompare_Main
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_ProductCompare_Main extends BClass
{
    public function onLayoutEditorFetchLibrary($args)
    {
        $this->FCom_Core_LayoutEditor->addDeclaredWidget('recently_compared', [
            'title' => 'Recently Compared Products',
            'view_name' => 'compare/recently-compared',
            'params' => [
                'cnt' => [
                    'type' => 'input',
                    'args' => [
                        'label' => 'Products Count',
                        'type' => 'number',
                        'value' => 6,
                    ],
                ],
            ],
        ]);
    }
}