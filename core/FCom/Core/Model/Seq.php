<?php

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

    public function getFirstSeqId($entityType)
    {
        $seqId = str_pad('1', 8, '0');
        $this->BEvents->fire(__METHOD__, ['entity_type' => $entityType, 'seq_id' => & $seqId]);
        return $seqId;
    }

    public function getNextSeqId($entityType, $firstSeqId = null)
    {
        $alias = $this->orm()->table_alias();
        $this->BDb->run("lock tables {$this->table()} write, {$this->table()} as {$alias} write");
        $seq = $this->load($entityType, 'entity_type');
        if (!$seq) {
            $seq = $this->setNextSeqId($entityType,$firstSeqId ?: $this->getFirstSeqId($entityType));
            $nextId = $seq->get('current_seq_id');
        } else {
            $nextId = $this->BUtil->nextStringValue($seq->get('current_seq_id'), $this->getAllowedChars());
            $seq->set('current_seq_id', $nextId)->save();
        }
        $this->BDb->run('unlock tables');
        return $nextId;
    }

    public function setNextSeqId($entityType, $seqId)
    {
        $seq = $this->load($entityType, 'entity_type');
        if (!$seq) {
            $seq = $this->create(['entity_type' => $entityType]);
        }
        $seq->set('current_seq_id', $seqId)->save();
        return $seq;
    }

    public function setNextChildId(FCom_Core_Model_Abstract $child, $parentClass, $parentField, $parentPrefix, $childPrefix)
    {
        $parentId = $child->get($parentField);
        if ($parentId) {
            $lastUniqueId = $child->orm()->where($parentField, $parentId)->select('unique_id')
                ->order_by_desc('(length(unique_id))')->order_by_desc('unique_id')
                ->find_one();
            if (!$lastUniqueId) {
                /** @var FCom_Core_Model_Abstract $parent */
                $parent = $this->{$parentClass}->load($parentId);
                $nextId = str_replace($parentPrefix . '-', $childPrefix . '-', $parent->get('unique_id')) . '-01';
            } else {
                if (!preg_match("#^({$childPrefix}-\d+-)(\d+)$#", $lastUniqueId->get('unique_id'), $m)) {
                    throw new BException('Invalid last unique id: ' . $lastUniqueId->get('unique_id'));
                }
                $nextId = $m[1] . str_pad($m[2] + 1, 2, '0', STR_PAD_LEFT);
            }
        } else {
            $nextId = $this->getNextSeqId(strtolower($childPrefix), $childPrefix . '-90000000');
        }
        $child->set('unique_id', $nextId);
        return $nextId;
    }
}
