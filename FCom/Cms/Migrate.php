<?php

class FCom_Cms_Migrate extends BClass
{
    public function run()
    {
        BMigrate::install('0.1.0', function() {
            $tNav = FCom_Cms_Model_Nav::table();
            $tPage = FCom_Cms_Model_Page::table();
            $tPageHistory = FCom_Cms_Model_PageHistory::table();
            $tBlock = FCom_Cms_Model_Block::table();
            $tBlockHistory = FCom_Cms_Model_BlockHistory::table();

        BDb::run("
CREATE TABLE IF NOT EXISTS {$tNav} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `id_path` varchar(100) NOT NULL,
  `node_name` varchar(255) NOT NULL,
  `full_name` text NOT NULL,
  `url_key` varchar(255) NOT NULL,
  `url_path` varchar(255) NOT NULL,
  `url_href` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL,
  `num_children` int(10) unsigned DEFAULT NULL,
  `num_descendants` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE {$tPage} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `layout_update` text COLLATE utf8_unicode_ci,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` datetime DEFAULT NULL,
  `version` int(11) unsigned NOT NULL,
  `meta_title` text COLLATE utf8_unicode_ci,
  `meta_description` text COLLATE utf8_unicode_ci,
  `meta_keywords` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE {$tPageHistory} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned NOT NULL,
  `version` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned null,
  `username` varchar(50) COLLATE utf8_unicode_ci NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL,
  `ts` datetime not null,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE {$tBlock} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `content` text COLLATE utf8_unicode_ci,
  `layout_update` text COLLATE utf8_unicode_ci,
  `version` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE {$tBlockHistory} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned NOT NULL,
  `version` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned null,
  `username` varchar(50) COLLATE utf8_unicode_ci NULL,
  `data` text COLLATE utf8_unicode_ci NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL,
  `ts` datetime not null,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
            ");
        });
    }
}