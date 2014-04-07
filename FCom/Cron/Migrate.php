<?php

class FCom_Cron_Migrate extends BClass
{
    public function install__0_1_1()
    {
        $tCron = FCom_Cron_Model_Task::table();
        BDb::run("
            CREATE TABLE IF NOT EXISTS {$tCron} (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `handle` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
            `cron_expr` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
            `last_start_at` datetime DEFAULT NULL,
            `last_finish_at` datetime DEFAULT NULL,
            `status` enum('pending','running','success','error','timeout') COLLATE utf8_unicode_ci DEFAULT NULL,
            `last_error_msg` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `handle` (`handle`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $table = FCom_Cron_Model_Task::table();
        BDb::ddlTableDef($table, array(
            'COLUMNS' => array(
                  'last_start_dt'      => 'RENAME last_start_at datetime DEFAULT NULL',
                  'last_finish_dt'      => 'RENAME last_finish_at datetime DEFAULT NULL',
            ),
          )
        );
    }
}
