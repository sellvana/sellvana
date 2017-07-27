<?php

/**
 * Class Sellvana_Email_Migrate
 *
 * @property Sellvana_Email_Model_Message $Sellvana_Email_Model_Message
 * @property Sellvana_Email_Model_Pref $Sellvana_Email_Model_Pref
 * @property FCom_Admin_Model_User FCom_Admin_Model_User
 *
 * @property Sellvana_Email_Model_Mailing_Subscriber Sellvana_Email_Model_Mailing_Subscriber
 * @property Sellvana_Email_Model_Mailing_List Sellvana_Email_Model_Mailing_List
 * @property Sellvana_Email_Model_Mailing_Campaign Sellvana_Email_Model_Mailing_Campaign
 * @property Sellvana_Email_Model_Mailing_ListRecipient Sellvana_Email_Model_Mailing_ListRecipient
 * @property Sellvana_Email_Model_Mailing_Link Sellvana_Email_Model_Mailing_Link
 * @property Sellvana_Email_Model_Mailing_Event Sellvana_Email_Model_Mailing_Event
 */

class Sellvana_Email_Migrate extends BClass
{
    public function install__0_1_3()
    {
        $tPref = $this->Sellvana_Email_Model_Pref->table();
        $tMessage = $this->Sellvana_Email_Model_Message->table();

        $this->BDb->ddlTableDef($tPref, [
            BDb::COLUMNS => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'email' => 'varchar(100)  NOT NULL',
                'unsub_all' => 'tinyint(4) NOT NULL',
                'sub_newsletter' => 'tinyint(4) NOT NULL',
                'create_at' => 'datetime NOT NULL',
                'update_at' => 'datetime NOT NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'email' => 'UNIQUE (email)',
            ],
        ]);
        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'view_name' => 'varchar(255) default null',
                'recipient' => 'varchar(100) NOT NULL',
                'subject' => 'varchar(255) NOT NULL',
                'body' => 'MEDIUMTEXT',
                'status' => "varchar(20) not null default 'new'",
                'error_message' => 'text',
                'num_attempts' => 'smallint not null default 0',
                'data_serialized' => 'text',
                'create_at' => 'datetime NOT NULL',
                'resent_at' => 'datetime NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'recipient' => '(recipient)',
                'IDX_view_name' => '(view_name)',
            ],
        ]);
    }

    public function upgrade__0_1_0__0_1_1()
    {
        $tMessage = $this->Sellvana_Email_Model_Message->table();

        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
                'recipient' => 'varchar(100) NOT NULL',
                'subject' => 'varchar(255) NOT NULL',
                'body' => 'MEDIUMTEXT',
                'status' => "varchar(20) not null default 'new'",
                'error_message' => 'text',
                'num_attempts' => 'smallint not null default 0',
                'data_serialized' => 'text',
                'create_dt' => 'datetime NOT NULL',
                'resent_dt' => 'datetime NULL',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'recipient' => '(recipient)',
            ],
        ]);
    }

    public function upgrade__0_1_1__0_1_2()
    {
        $tPref = $this->Sellvana_Email_Model_Pref->table();
        $tMessage = $this->Sellvana_Email_Model_Message->table();

        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                  'create_dt' => 'RENAME create_at datetime NOT NULL',
                  'resent_dt' => 'RENAME resent_at datetime NULL',
            ],
        ]);
        $this->BDb->ddlTableDef($tPref, [
            BDb::COLUMNS => [
                  'create_dt' => 'RENAME create_at datetime NOT NULL',
                  'update_dt' => 'RENAME update_at datetime NOT NULL',
            ],
        ]);
    }

    public function upgrade__0_1_2__0_1_3()
    {
        $tMessage = $this->Sellvana_Email_Model_Message->table();
        $this->BDb->ddlTableDef($tMessage, [
            BDb::COLUMNS => [
                'view_name' => 'varchar(255) default null',
            ],
            BDb::KEYS => [
                'IDX_view_name' => '(view_name)',
            ],
        ]);
    }

    public function upgrade__0_6_0_0__0_6_1_0()
    {
        $tUser = $this->FCom_Admin_Model_User->table();
        $tMailingSubscriber = $this->Sellvana_Email_Model_Mailing_Subscriber->table();
        $tMailingList = $this->Sellvana_Email_Model_Mailing_List->table();
        $tMailingListRecipient = $this->Sellvana_Email_Model_Mailing_ListRecipient->table();
        $tMailingCampaign = $this->Sellvana_Email_Model_Mailing_Campaign->table();
        $tMailingCampaignRecipient = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->table();
        $tMailingLink = $this->Sellvana_Email_Model_Mailing_Link->table();
        $tMailingEvent = $this->Sellvana_Email_Model_Mailing_Event->table();

        $this->BDb->ddlTableDef($tMailingSubscriber, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'email' => 'varchar(100) not null',
                'firstname' => 'varchar(50)',
                'lastname' => 'varchar(50)',
                'company' => 'varchar(50)',
                'unique_id' => 'binary(16)',
                'is_unsubscribed' => 'tinyint not null default 0',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_email' => 'UNIQUE (email)',
                'IDX_unsubscribed' => '(is_unsubscribed)',
                'UNQ_unique_id' => 'UNIQUE (unique_id)',
            ],
        ]);

        $this->BDb->ddlTableDef($tMailingList, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(255)',
                'unique_id' => 'binary(16)',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_unique_id' => 'UNIQUE (unique_id)',
            ],
        ]);

        $this->BDb->ddlTableDef($tMailingCampaign, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'list_id' => 'int unsigned not null',
                'title' => 'varchar(255)',
                'unique_id' => 'binary(16)',
                'status' => "char(1) not null",
                'sender_name' => 'varchar(100)',
                'sender_email' => 'varchar(100)',
                'subject' => 'varchar(255)',
                'template_html' => 'text',
                'cnt_total' => 'int unsigned not null default 0',
                'cnt_sent' => 'int unsigned not null default 0',
                'cnt_success' => 'int unsigned not null default 0',
                'cnt_error' => 'int unsigned not null default 0',
                'cnt_opened' => 'int unsigned not null default 0',
                'cnt_clicked' => 'int unsigned not null default 0',
                'cnt_unsub' => 'int unsigned not null default 0',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_unique_id' => 'UNIQUE (unique_id)',
            ],
            BDb::CONSTRAINTS => [
                'list' => ['list_id', $tMailingList],
            ],
        ]);

        $this->BDb->ddlTableDef($tMailingListRecipient, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'list_id' => 'int unsigned not null',
                'subscriber_id' => 'int unsigned not null',
                'status' => "char(1) not null default 'A'",
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_list_subscriber' => 'UNIQUE (list_id, subscriber_id)',
            ],
            BDb::CONSTRAINTS => [
                'list' => ['list_id', $tMailingList],
                'subscriber' => ['subscriber_id', $tMailingSubscriber],
            ],
        ]);

        $this->BDb->ddlTableDef($tMailingLink, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'campaign_id' => 'int unsigned not null',
                'link_href' => 'varchar(255)',
                'unique_id' => 'binary(16)',
                'cnt_clicked' => 'int unsigned not null default 0',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_unique_id' => 'UNIQUE (unique_id)',
            ],
            BDb::CONSTRAINTS => [
                'campaign' => ['campaign_id', $tMailingCampaign],
            ],
        ]);

        $this->BDb->ddlTableDef($tMailingCampaignRecipient, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'campaign_id' => 'int unsigned not null',
                'subscriber_id' => 'int unsigned not null',
                'list_recipient_id' => 'int unsigned',
                'unique_id' => 'binary(16)',
                'status' => "char(1) not null default 'P'",
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'UNQ_campaign_subscriber' => 'UNIQUE (campaign_id, subscriber_id)',
                'UNQ_unique_id' => 'UNIQUE (unique_id)',
            ],
            BDb::CONSTRAINTS => [
                'campaign' => ['campaign_id', $tMailingCampaign],
                'subscriber' => ['subscriber_id', $tMailingSubscriber],
                'list_recipient' => ['list_recipient_id', $tMailingListRecipient, 'id', 'CASCADE', 'SET NULL'],
            ],
        ]);

        $this->BDb->ddlTableDef($tMailingEvent, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'campaign_id' => 'int unsigned',
                'subscriber_id' => 'int unsigned',
                'list_id' => 'int unsigned',
                'link_id' => 'int unsigned',
                'user_id' => 'int unsigned',
                'list_recipient_id' => 'int unsigned',
                'campaign_recipient_id' => 'int unsigned',
                'event_type' => 'varchar(20)',
                'remote_ip' => 'varchar(20)',
                'data_serialized' => 'text',
                'create_at' => 'datetime',
                'update_at' => 'datetime',
            ],
            BDb::PRIMARY => '(id)',
            BDb::KEYS => [
                'IDX_event_type' => '(event_type)',
            ],
            BDb::CONSTRAINTS => [
                'campaign' => ['campaign_id', $tMailingCampaign],
                'subscriber' => ['subscriber_id', $tMailingSubscriber],
                'list' => ['list_id', $tMailingList],
                'link' => ['link_id', $tMailingLink],
                'user' => ['user_id', $tUser],
                'list_recipient' => ['list_recipient_id', $tMailingListRecipient],
                'campaign_recipient' => ['campaign_recipient_id', $tMailingCampaignRecipient],
            ],
        ]);
    }
}
