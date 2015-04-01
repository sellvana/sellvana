<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Main
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 */
class Sellvana_Catalog_Main extends BClass
{
    public function onLayoutEditorFetchLibrary($args)
    {
        $this->FCom_Core_LayoutEditor
            ->addLayoutType('product', [
                'title' => 'Product',
            ])
            ->addLayoutType('category', [
                'title' => 'Category',
            ])
            ->addDeclaredWidget('featured_products', [
                'title' => 'Featured Products',
                'view_name' => 'catalog/featured-products',
                'params' => [
                    'cnt' => [
                        'type' => 'input',
                        'args' => [
                            'label' => 'Products Count',
                            'type' => 'number',
                            'value' => 6,
                        ],
                    ],
                    'auto_scroll' => [
                        'type' => 'boolean',
                        'args' => [
                            'label' => 'Auto Scroll',
                        ]
                    ]
                ],
            ])
            ->addDeclaredWidget('popular_products', [
                'title' => 'Popular Products',
                'view_name' => 'catalog/popular-products',
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
            ])
        ;
    }
}