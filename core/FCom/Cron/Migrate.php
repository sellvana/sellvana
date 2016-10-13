<?php

/**
 * Class FCom_Cron_Migrate
 *
 * @property FCom_Cron_Model_Task $FCom_Cron_Model_Task
 * @property FCom_Cron_Model_Log $FCom_Cron_Model_Log
 */

class FCom_Cron_Migrate extends BClass
{
    public function install__0_6_1_0()
    {
        $tTask = $this->FCom_Cron_Model_Task->table();
        $tLog = $this->FCom_Cron_Model_Log->table();

        $this->BDb->ddlTableDef($tTask, [
            BDb::COLUMNS => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'handle' => 'varchar(100)  NOT NULL',
                'cron_expr' => 'varchar(50)  NOT NULL',
                'last_start_at' => 'datetime DEFAULT NULL',
                'last_finish_a' => 'datetime DEFAULT NULL',
                'status' => 'varchar(10)  DEFAULT NULL',
                'last_error_msg' => 'text DEFAULT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_handle' => 'UNIQUE (`handle`)',
            ],
        ]);

        $this->BDb->ddlTableDef($tLog, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'task_id' => 'int unsigned not null',
                'create_at' => 'datetime',
                'start_at' => 'datetime',
                'finish_at' => 'datetime',
                'status' => 'varchar(10) default null',
                'error_msg' => 'text',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'task' => ['task_id', $tTask],
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $table = $this->FCom_Cron_Model_Task->table();
        $this->BDb->ddlTableDef($table, [
            BDb::COLUMNS => [
                'last_start_dt' => 'RENAME last_start_at datetime DEFAULT NULL',
                'last_finish_dt' => 'RENAME last_finish_at datetime DEFAULT NULL',
            ],
        ]);
    }

    public function upgrade__0_6_0_0__0_6_1_0()
    {
        $tTask = $this->FCom_Cron_Model_Task->table();
        $tLog = $this->FCom_Cron_Model_Log->table();

        $this->BDb->ddlTableDef($tTask, [
            BDb::COLUMNS => [
                'status' => 'varchar(10) default null',
            ],
        ]);

        $this->BDb->ddlTableDef($tLog, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'task_id' => 'int unsigned not null',
                'create_at' => 'datetime',
                'start_at' => 'datetime',
                'finish_at' => 'datetime',
                'status' => 'varchar(10) default null',
                'error_msg' => 'text',
                'data_serialized' => 'text',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'task' => ['task_id', $tTask],
            ],
        ]);
    }
}
