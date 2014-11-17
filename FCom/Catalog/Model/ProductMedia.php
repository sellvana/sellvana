<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Catalog_Model_ProductMedia
 *
 * @property FCom_Core_Main $FCom_Core_Main
 */

class FCom_Catalog_Model_ProductMedia extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_product_media';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'    => ['id', 'create_at', 'update_at'],
        'related' => [
            'product_id' => 'FCom_Catalog_Model_Product.id',
            'file_id'    => 'FCom_Core_Model_MediaLibrary.id',
        ],
        'unique_key' => [
            'product_id', 'file_id'
        ],
    ];

    public function getUrl()
    {
        $subfolder = $this->get('subfolder');
        $path = $this->get('folder') . '/' . ($subfolder ? $subfolder . '/' : '') . $this->get('file_name');
        return $this->BApp->src($path);
    }

    public function imageUrl($full = false)
    {
        static $default;

        $url = $full ? $this->BRequest->baseUrl() : $this->BRequest->webRoot();
        $subfolder = $this->get('subfolder');
        $thumbUrl = $this->get('folder') . '/' . ($subfolder ? $subfolder . '/' : '') . $this->get('file_name');

        if ($thumbUrl) {
            return $url . '/' . $thumbUrl;
        }

        if (!$default) {
            $default = $this->BConfig->get('modules/FCom_Catalog/default_image');
            if ($default) {
                if ($default[0] === '@') {
                    $default = $this->BApp->src($default, 'baseSrc', false);
                }
            } else {
                $default = $url . $media . '/image-not-found.jpg';
            }
        }
        return $default;
    }

    public function thumbUrl($w, $h = null, $full = false)
    {
        $imgUrl = $this->imageUrl(false);
        return $this->FCom_Core_Main->resizeUrl($imgUrl, ['s' => $w . 'x' . $h, 'full_url' => $full]);
    }
}
