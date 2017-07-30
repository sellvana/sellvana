<?php

/**
 * Class Sellvana_Seo_Model_UrlAlias
 *
 * @property int $id
 * @property string $request_url
 * @property string $target_url
 * @property int $is_active
 * @property int $is_regexp
 * @property string $redirect_type
 * @property datetime $create_at
 * @property datetime $update_at
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */
class Sellvana_Seo_Model_UrlAlias extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_seo_urlalias';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'       => ['id'],
        'unique_key' => ['request_url', 'is_regexp', 'is_active'],
    ];

    /**
     * @var array
     */
    static protected $_fieldOptions = [
        'redirect_type' => [
            'FWD' => (('Forward')),
            '301' => '301 Permanent',
            '302' => '302 Temporary',
        ],
        'is_active' => [0 => (('No')), 1 => (('Yes'))],
        'is_regexp' => [0 => (('No')), 1 => (('Yes'))],
    ];

    /**
     * @param $url
     * @return bool|Sellvana_Seo_Model_UrlAlias
     */
    public function findByUrl($url)
    {
        $alias = $this->orm()->where('is_active', 1)->where('is_regexp', 0)->where('request_url', $url)->find_one();
        if ($alias) {
            return $alias;
        }
        $alias = $this->orm()->where('is_active', 1)->where('is_regexp', 1)->where_raw('request_url regexp ?', $url)->find_one();
        if ($alias) {
            return $alias;
        }
        return false;
    }

    /**
     * @param bool $full
     * @return null|string
     */
    public function targetUrl($full = true)
    {
        $url = $this->target_url;
        if ($full && !preg_match('#^https?:#', $url)) {
            $url = $this->FCom_Frontend_Main->href($url);
        }
        return $url;
    }
}
