<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_RecentlyViewed_Main
 *
 * @property FCom_Core_LayoutEditor $FCom_Core_LayoutEditor
 * @property Sellvana_RecentlyViewed_Model_History $Sellvana_RecentlyViewed_Model_History
 */
class Sellvana_RecentlyViewed_Main extends BClass
{
    public function onLayoutEditorFetchLibrary($args)
    {
        $this->FCom_Core_LayoutEditor->addDeclaredWidget('recently_viewed', [
            'title' => 'Recently Viewed Products',
            'view_name' => 'catalog/recently-viewed',
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

    public function onProductView($args)
    {
        $this->Sellvana_RecentlyViewed_Model_History->addItem($args['product']);
    }
}