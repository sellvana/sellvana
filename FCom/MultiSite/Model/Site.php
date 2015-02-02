<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_MultiSite_Model_Site
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
class FCom_MultiSite_Model_Site extends FCom_Core_Model_Abstract
{
    static protected $_origClass = __CLASS__;
    static protected $_table = 'fcom_multisite_site';
    static protected $_mapCacheKey = 'FCom_MultiSite.domain_map';

    protected static $_validationRules = [
        ['name', '@required'],
        ['root_category_id', '@integer'],
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
            $domains = explode("\n", $site->match_domains);
            foreach ($domains as $pattern) {
                if (empty($pattern)) {
                    continue;
                }
                if ($pattern[0] === '^') {
                    $regex = $pattern;
                } else {
                    $regex = str_replace('*', '.*', str_replace('.', '\\.', strtolower($pattern)));
                }
                $map[$regex] = $site->as_array();
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
        if (!$map) {
            $map = $this->createDomainMap();
        }
        return $map;
    }

    /**
     * @param null $domain
     * @return null
     */
    public function findByDomain($domain = null)
    {
        if (null === $domain) {
            $domain = $this->BRequest->httpHost(false);
        }
        $domain = strtolower($domain);
        $map = (array)$this->getDomainMap();
        $site = null;
        foreach ($map as $pattern => $siteData) {
            if (preg_match('#' . $pattern . '#i', $domain)) {
                $site = $siteData;
                break;
            }
        }
        return $site;
    }

    /**
     * @return array
     */
    public function siteOptions()
    {
        $sites = (array)$this->orm()->find_many();
        $groups = [];
        foreach ($sites as $model) {
            $key            = $model->id;
            $groups[$key] = $model->name;
        }

        return $groups;
    }
}
