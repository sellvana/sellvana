<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_MultiSite_Model_Site
 *
 * @property int $id
 * @property string $name
 * @property string $match_domains
 * @property string $default_theme
 * @property string $layout_update
 * @property int $root_category_id
 * @property string $mode_by_ip
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $create_at
 * @property string $update_at
 */
class Sellvana_MultiSite_Model_Site extends FCom_Core_Model_Abstract
{
    static protected $_origClass = __CLASS__;
    static protected $_table = 'fcom_multisite_site';
    static protected $_mapCacheKey = 'Sellvana_MultiSite.domain_map';

    protected static $_validationRules = [
        ['name', '@required'],
        ['root_category_id', '@integer'],
    ];

    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['name', 'root_category_id'],
        'related'    => ['root_category_id' => 'Sellvana_Catalog_Model_Category.id'],
    ];

    public function onAfterSave()
    {
        parent::onAfterSave();
        $this->createDomainMap();
    }

    /**
     * @return array
     */
    public function createDomainMap()
    {
        if (!$this->BDb->ddlTableExists($this->table())) {
            return [];
        }
        $map = [];
        $sites = (array)$this->orm()->find_many();
        foreach ($sites as $site) {
            if (!$site->get('match_domains')) {
                continue;
            }
            $domains = explode("\n", $site->get('match_domains'));
            foreach ($domains as $pattern) {
                if (empty($pattern)) {
                    continue;
                }
                if ($pattern[0] === '^') {
                    $regex = $pattern;
                } else {
                    $regex = str_replace('*', '.*', str_replace('.', '\\.', strtolower($pattern)));
                }
                $map[$regex] = $site->id();
            }
        }
        $this->BCache->save(static::$_mapCacheKey, $map, false);
        return $map;
    }

    /**
     * @return array
     */
    public function getDomainMap()
    {
        $map = $this->BCache->load(static::$_mapCacheKey);
        if (null === $map) {
            $map = $this->createDomainMap();
        }
        return $map;
    }

    /**
     * @param null $domain
     * @return null
     */
    public function findIdByDomain($domain = null)
    {
        if (null === $domain) {
            $domain = $this->BRequest->httpHost(false);
        }
        $domain = strtolower($domain);
        $map = (array)$this->getDomainMap();
        $siteId = null;
        foreach ($map as $pattern => $id) {
            if (preg_match('#' . $pattern . '#i', $domain)) {
                $siteId = $id;
                break;
            }
        }
        return $siteId;
    }

    /**
     * @return array
     */
    public function siteOptions()
    {
        return $this->orm()->find_many_assoc('id', 'name');
    }
}
