<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Catalog_Model_ProductMedia
 *
 * @property FCom_Core_Main $FCom_Core_Main
 */

class Sellvana_Catalog_Model_ProductMedia extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_media';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'    => ['id', 'create_at', 'update_at'],
        'related' => [
            'product_id' => 'Sellvana_Catalog_Model_Product.id',
            'file_id'    => 'FCom_Core_Model_MediaLibrary.id',
        ],
        'unique_key' => [
            'product_id', 'file_id'
        ],
    ];

    const MEDIA_TYPE_IMG = "I"
        , MEDIA_TYPE_ATTACH = "A"; // any other media types?

    public function getUrl()
    {
        $subfolder = $this->get('subfolder');
        $path = $this->get('folder') . '/' . ($subfolder ? $subfolder . '/' : '') . $this->get('file_name');
        return $this->BApp->src($path);
    }

    public function imageUrl($full = false)
    {
        static $default;

        $baseUrl = $full ? $this->BRequest->baseUrl() : $this->BRequest->webRoot();
        $subfolder = $this->get('subfolder');
        $thumbUrl = $this->get('folder') . '/' . ($subfolder ? $subfolder . '/' : '') . $this->get('file_name');

        if ($thumbUrl) {
            return $baseUrl . '/' . $thumbUrl;
        }

        if (!$default) {
            $default = $this->BConfig->get('modules/Sellvana_Catalog/default_image');
            if ($default) {
                if ($default[0] === '@') {
                    $default = $this->BApp->src($default, 'baseSrc', false);
                }
            } else {
                $mediaDir = $this->BConfig->get('web/media_dir');
                $default = $baseUrl . $mediaDir . '/image-not-found.jpg';
            }
        }
        return $default;
    }

    public function thumbUrl($w, $h = null, $full = false)
    {
        $imgUrl = $this->imageUrl(false);
        return $this->FCom_Core_Main->resizeUrl($imgUrl, ['s' => $w . 'x' . $h, 'full_url' => $full]);
    }

    /**
     * Collect and populate products with requested thumbnail types
     *
     * @param Sellvana_Catalog_Model_Product[] $products
     * @param array $imgTypes
     * @param array $params
     * @return $this
     */
    public function collectProductsImages(array $products, $imgTypes = ['thumb', 'rollover', 'default'], $params = null)
    {
        if (!count($products)) {
            return $this;
        }

        $pIds = [];
        foreach ($products as $p) {
            $pIds[] = $p->id();
        }
        $select = ['fpm.product_id', 'fml.id', 'fml.folder', 'fml.subfolder', 'fml.file_name'];
        $imgTypesSql = [];
        foreach ($imgTypes as $type) {
            $imgTypesSql[] = "fpm.is_{$type}=1";
            $select[] = "fpm.is_{$type}";
        }
        $imageRows = $this->orm("fpm")
            ->join($this->FCom_Core_Model_MediaLibrary->table(), ["fpm.file_id", "=", "fml.id"], "fml")
            ->where(["fpm.media_type" => "I", "fpm.product_id" => $pIds, 'OR' => $imgTypesSql])
            ->select($select)
            ->find_many_assoc(['product_id', 'id']);
        //$coreHlp = $this->FCom_Core_Main;
        foreach ($products as $p) {
            $pId = $p->id();
            $images = [];
            if (!empty($imageRows[$pId])) {
                /**
                 * @var int $mId
                 * @var static $r
                 */
                foreach ($imageRows[$pId] as $mId => $r) {
                    $folder    = preg_replace('#^media/#', '', $r->get('folder'));
                    $subfolder = $r->get('subfolder');
                    $filename  = $r->get('file_name');
                    foreach ($imgTypes as $type) {
                        if ($r->get('is_' . $type)) {
                            $images[$type] = $folder . '/' . ($subfolder ? $subfolder . '/' : '') . $filename;
                        }
                    }
                }
            }
            foreach ($imgTypes as $type) {
                if (empty($images[$type])) {
                    $images[$type] = false;
                }
            }
            $p->setProductImages($images);
        }
        return $this;
    }
}
