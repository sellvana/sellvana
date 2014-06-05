<?php defined('BUCKYBALL_ROOT_DIR') || die();

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

    public function getDomainMap()
    {
        $map = $this->BCache->load(static::$_mapCacheKey);
        if (!$map) {
            $map = $this->createDomainMap();
        }
        return $map;
    }

    public function findByDomain($domain = null)
    {
        if (null === $domain) {
            $domain = $this->BRequest->httpHost(false);
        }
        $domain = strtolower($domain);
        $map = (array)$this->getDomainMap();
        $site = null;
        foreach ($map as $pattern => $siteData) {
            if (preg_match('#' . $pattern . '#', $domain)) {
                $site = $siteData;
                break;
            }
        }
        return $site;
    }

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
