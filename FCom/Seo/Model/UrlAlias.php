<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Seo_Model_UrlAlias extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_seo_urlalias';

    static protected $_fieldOptions = [
        'redirect_type' => [
            'FWD' => 'Forward',
            '301' => '301 Permanent',
            '302' => '302 Temporary',
        ],
    ];

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

    public function targetUrl($full = true)
    {
        $url = $this->target_url;
        if ($full && !preg_match('#^https?:#', $url)) {
            $url = $this->FCom_Frontend_Main->href($url);
        }
        return $url;
    }
}
