<?php

/**
 * Class Sellvana_CustomerSegments
 *
 * @property Sellvana_CustomerSegments_Model_Segment $Sellvana_CustomerSegments_Model_Segment
 * @property Sellvana_CustomerSegments_Model_SegmentCustomer $Sellvana_CustomerSegments_Model_SegmentCustomer
 * @property Sellvana_Customer_Model_Customer $Sellvana_Customer_Model_Customer
 */
class Sellvana_CustomerSegments_Migrate extends BClass
{
    public function install__0_5_0_0()
    {
        $tSegment = $this->Sellvana_CustomerSegments_Model_Segment->table();
        $tSegmentCustomer = $this->Sellvana_CustomerSegments_Model_SegmentCustomer->table();
        $tCustomer = $this->Sellvana_Customer_Model_Customer->table();

        $this->BDb->ddlTableDef($tSegment, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'title' => 'varchar(255) not null',
                'create_at' => 'datetime default null',
                'update_at' => 'datetime default null',
                'data_serialized' => 'text default null',
            ],
            BDb::PRIMARY => '(id)',
        ]);

        $this->BDb->ddlTableDef($tSegmentCustomer, [
            BDb::COLUMNS => [
                'id' => 'int unsigned not null auto_increment',
                'segment_id' => 'int unsigned not null',
                'customer_id' => 'int unsigned not null',
                'create_at' => 'datetime default null',
                'update_at' => 'datetime default null',
                'data_serialized' => 'text default null',
            ],
            BDb::PRIMARY => '(id)',
            BDb::CONSTRAINTS => [
                'segment' => ['segment_id', $tSegment],
                'customer' => ['customer_id', $tCustomer],
            ],
        ]);
    }
}