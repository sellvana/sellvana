<?php

/**
 * Class Sellvana_Catalog_Model_ProductLink
 * @property int $id
 * @property string $link_type
 * @property int $product_id
 * @property int $linked_product_id
 * @property int $position
 *
 * DI
 * @property Sellvana_Catalog_Model_Product $Sellvana_Catalog_Model_Product
 */
class Sellvana_Catalog_Model_ProductLink extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_link';
    protected static $_origClass = __CLASS__;

    protected static $_importExportProfile = [
        'skip'    => ['id'],
        'related' => [
            'product_id'        => 'Sellvana_Catalog_Model_Product.id',
            'linked_product_id' => 'Sellvana_Catalog_Model_Product.id',
        ],
        'unique_key' => [
            'product_id',
            'linked_product_id',
            'link_type'
        ]
    ];

    /**
     * @param int $id
     * @param string $type
     * @return $this[]
     */
    public function productsByType($id, $type)
    {
        $orm = $this->Sellvana_Catalog_Model_Product->orm('p')
            ->select('*');
        $orm->join('Sellvana_Catalog_Model_ProductLink', ['pl.linked_product_id', '=', 'p.id'], 'pl')
                ->where('link_type', $type)
                ->where('pl.product_id', $id);
        return $orm->find_many();
    }
}
