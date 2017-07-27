<?php

/**
 * Class Sellvana_Email_Model_Mailing_ListRecipient
 *
 * @property Sellvana_Email_Model_Mailing_Subscriber Sellvana_Email_Model_Mailing_Subscriber
 * @property Sellvana_Email_Model_Mailing_ListRecipient Sellvana_Email_Model_Mailing_ListRecipient
 * @property Sellvana_Email_Model_Mailing_Event Sellvana_Email_Model_Mailing_Event
 */
class Sellvana_Email_Model_Mailing_ListRecipient extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_mailing_list_recipient';

    static protected $_fieldOptions = [
        'status' => [
            'A' => 'Active',
            'U' => 'Unsubscribed',
        ],
    ];

    public function importAsTextCsv($listId, $textCsv)
    {
        $lines = str_getcsv($textCsv, "\n");
        unset($textCsv);
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = str_getcsv($line);
        }
        unset($lines);
        $emails = array_column($rows, 0);
        $existingSubs = $this->Sellvana_Email_Model_Mailing_Subscriber->orm()
            ->where_in('email', $emails)
            ->find_many_assoc('email');
        if ($existingSubs) {
            $subIds = $this->BUtil->arrayToOptions($existingSubs, 'id', 'id');
            $existingRcpt = $this->Sellvana_Email_Model_Mailing_ListRecipient->orm()
                ->where('list_id', $listId)
                ->where_in('subscriber_id', $subIds)
                ->find_many_assoc('subscriber_id');
        }
        foreach ($rows as $row) {
            $email = $row[0];
            $data = ['email' => $email];
            if (!empty($row[1])) {
                $data['firstname'] = $row[1];
            }
            if (!empty($row[2])) {
                $data['lastname'] = $row[2];
            }
            if (!empty($row[3])) {
                $data['company'] = $row[3];
            }
            if (empty($existingSubs[$email])) {
                $sub = $this->Sellvana_Email_Model_Mailing_Subscriber->create();
            } else {
                $sub = $existingSubs[$email];
            }
            $sub->set($data)->save();
            if (empty($existingRcpt[$sub->id()])) {
                $this->Sellvana_Email_Model_Mailing_ListRecipient->create([
                    'list_id' => $listId,
                    'subscriber_id' => $sub->id(),
                ])->save();
                $this->Sellvana_Email_Model_Mailing_Event->create([
                    'list_id' => $listId,
                    'subscriber_id' => $sub->id(),
                    'user_id' => $this->FCom_Admin_Model_User->sessionUserId(),
                    'event_type' => 'S',
                ])->save();
            }
        }
    }
}