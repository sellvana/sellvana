<?php

class FCom_Core_Model_Seq extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_core_seq';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = array(
        'unique_key' => array( 'entity_type', 'current_seq_id', ), );

    static public function getSeqIdFormat( $entityType )
    {

    }

    static public function getAllowedChars()
    {
        return '0123456789';
    }

    static public function getNextSeqId( $entityType )
    {

        BDb::run( 'lock tables ' . static::table() . ' write' );
        $seq = static::orm( static::table() )->where( 'entity_type', $entityType )->find_one();
        if ( !$seq ) {
            $seq = static::create( array(
                'entity_type' => $entityType,
                'current_seq_id' => static::getFirstSeqId( $entityType )
            ) )->save();
        }
        $nextId = BUtil::nextStringValue( $seq->current_seq_id, static::getAllowedChars() );
        $seq->set( 'current_seq_id', $nextId )->save();
        BDb::run( 'unlock tables' );
        return $nextId;
    }

    static public function getFirstSeqId( $entityType )
    {
        $seqId = str_pad( '1', 8, '0' );
        BEvents::i()->fire( __METHOD__, array( 'entity_type' => $entityType, 'seq_id' => & $seqId ) );
        return $seqId;
    }
}
