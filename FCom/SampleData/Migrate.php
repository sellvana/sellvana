<?php

class FCom_SampleData_Migrate extends BClass
{
    public function install__0_1_0()
    {
        $fieldHlp = FCom_CustomField_Model_Field::i();
        $fields   = array(
            'finish'    => array(
                'field_code'       => 'finish',
                'field_name'       => 'Finish',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label'   => 'Finish',
                'frontend_show'    => 0,
                'sort_order'       => 1,
            ),
            'ship_type' => array(
                'field_code'       => 'ship_type',
                'field_name'       => 'Ship type',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'select',
                'frontend_label'   => 'Ship type',
                'frontend_show'    => 0,
                'sort_order'       => 1,
            ),
            'lead_time' => array(
                'field_code'       => 'lead_time',
                'field_name'       => 'Lead time',
                'table_field_type' => 'varchar(255)',
                'admin_input_type' => 'text',
                'frontend_label'   => 'Lead time',
                'frontend_show'    => 0,
                'sort_order'       => 1,
            )
        );
        $exist    = $fieldHlp->orm()->where_in( 'field_code', array_keys( $fields ) )->find_many_assoc( 'field_code' );

        //add custom fields
        foreach ( $fields as $f => $data ) {
            // create custom fields if they don't exist
            if ( empty( $exist[ $f ] ) ) {
                $f = $fieldHlp->create( $data )->save();
            } else {
                $f = $exist[ $f ];
            }

            $fieldName = FCom_IndexTank_Index_Product::i()->getCustomFieldKey( $f );
            $doc       = FCom_IndexTank_Model_ProductField::orm()->where( 'field_name', $fieldName )->find_one();
            if ( !$doc ) {
                /* @var FCom_IndexTank_Model_ProductField $doc */
                // create indextank custom fields.
                $doc = FCom_IndexTank_Model_ProductField::orm()->create();

                $matches = array();
                preg_match( "#(\w+)#", $data[ 'table_field_type' ], $matches );
                $type = $matches[ 1 ];

                $doc->field_name      = $fieldName;
                $doc->field_nice_name = $data[ 'frontend_label' ];
                $doc->field_type      = $type;
                $doc->facets          = 1;
                $doc->search          = 0;
                $doc->source_type     = 'custom_field';
                $doc->source_value    = $data[ 'field_code' ];

                $doc->save();
            }
            $fieldName = $f->get('field_code');
            /* @var FCom_CatalogIndex_Model_Field $catalogIndexField */
            $catalogIndexField = FCom_CatalogIndex_Model_Field::orm()->where( 'field_name', $fieldName)->find_one();

            if ( !$catalogIndexField ) {
                $data = array(
                    "field_name"    => $f->get( 'field_code' ),
                    "field_label"   => $f->get( 'field_name' ),
                    "field_type"    => 'varchar',
                    "weight"        => 0,
                    "fcom_field_id" => $f->id(),
                    "search_type"   => 'none',
                    "sort_type"     => 'none',
                );
                $catalogIndexField = FCom_CatalogIndex_Model_Field::orm()->create( $data );
                $catalogIndexField->save();
            }
        }
    }
}