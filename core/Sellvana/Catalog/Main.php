<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Main
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
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
            ->addWidgetType('product_carousel', [
                'title'       => 'Products Carousel',
                'source_view' => 'catalog/products/carousel',
                'view_name'   => 'catalog/product/carousel',
                'pos'         => 100,
                'compile'     => function ($args) {
                    $w                = $args['widget'];
                    $view_name        = $w['view_name'];
                    $args['layout'][] = ['hook' => $w['area'], 'views' => $view_name];
                    $skus = explode(',', $w['value']);
                    $products = $this->Sellvana_Catalog_Model_Product->orm()->where(['product_sku' => $skus])->find_many_assoc('product_sku');
                    $args['layout'][] = [
                        'view' => $view_name,
                        'set'  => [
                            'widget_id' => $w['id'],
                            'skus' => $skus,
                            'products' => $products,
                            'height' => !empty($w['height']) ? $w['height'] : null,
                            'interval' => !empty($w['interval']) ? $w['interval'] : null,
                            'pause' => !empty($w['pause']) ? $w['pause'] : null,
                            'keyboard' => !empty($w['keyboard']) ? $w['keyboard'] : null,
                        ]
                    ];
                }
            ])
        ;
    }
}
