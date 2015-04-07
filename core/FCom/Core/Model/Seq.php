<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Core_Model_Seq extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_core_seq';
    static protected $_origClass = __CLASS__;
    protected static $_importExportProfile = ['unique_key' => ['entity_type', 'current_seq_id',]];

    public function getSeqIdFormat($entityType)
    {

    }

    public function getAllowedChars()
    {
        return '0123456789';
    }

    public function getNextSeqId($entityType)
    {

        $this->BDb->run('lock tables ' . $this->table() . ' write');
        $seq = $this->orm($this->table())->where('entity_type', $entityType)->find_one();
        if (!$seq) {
            $seq = $this->create([
                'entity_type' => $entityType,
                'current_seq_id' => $this->getFirstSeqId($entityType)
            ])->save();
        }
        $nextId = $this->BUtil->nextStringValue($seq->current_seq_id, $this->getAllowedChars());
        $seq->set('current_seq_id', $nextId)->save();
        $this->BDb->run('unlock tables');
        return $nextId;
    }

    public function getFirstSeqId($entityType)
    {
        $seqId = str_pad('1', 8, '0');
        $this->BEvents->fire(__METHOD__, ['entity_type' => $entityType, 'seq_id' => & $seqId]);
        return $seqId;
    }
}
