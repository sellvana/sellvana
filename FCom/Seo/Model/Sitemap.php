<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Seo_Model_Sitemap
 *
 * @property int $id
 * @property string $name
 * @property string $url_key
 * @property string $data_json
 * @property datetime $create_at
 * @property datetime $update_at
 */
class FCom_Seo_Model_Sitemap extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_seo_sitemap';

}