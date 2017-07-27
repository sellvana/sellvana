<?php

class Sellvana_Email_Model_Mailing_Campaign extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_mailing_campaign';

    static protected $_fieldDefaults = [
        'status' => 'I',
    ];

    static protected $_fieldOptions = [
        'status' => [
            'I' => 'Idle',
            'R' => 'Running',
            'P' => 'Paused',
            'S' => 'Stopped',
            'C' => 'Complete',
        ],
    ];

    public function onBeforeSave()
    {
        if (!parent::onBeforeSave()) {
            return false;
        }

        if (!$this->get('unique_id')) {
            $this->set('unique_id', $this->BUtil->randomString(16));
        }

        return true;
    }

    public function getUnsubscribeUrl(Sellvana_Email_Model_Mailing_Subscriber $subscriber)
    {
        return $this->BApp->frontendHref($this->BUtil->setUrlQuery('mailings/unsubscribe', [
            'subscriber' => $subscriber->get('unique_id'),
            'campaign' => $this->get('unique_id'),
        ]));
    }

    public function getPixelUrl(Sellvana_Email_Model_Mailing_Subscriber $subscriber)
    {
        return $this->BApp->frontendHref($this->BUtil->setUrlQuery('mailings/pixel', [
            'subscriber' => $subscriber->get('unique_id'),
            'campaign' => $this->get('unique_id'),
        ]));
    }

    public function getTrackLinkUrl(Sellvana_Email_Model_Mailing_Link $link, Sellvana_Email_Model_Mailing_Subscriber $subscriber)
    {
        return $this->BApp->frontendHref($this->BUtil->setUrlQuery('mailings/link', [
            'link' => $link->get('unique_id'),
            'subscriber' => $subscriber->get('unique_id'),
            'campaign' => $this->get('unique_id'),
        ]));
    }

    public function importRecipientsFromList($listId)
    {
        $this->Sellvana_Email_Model_Mailing_CampaignRecipient->delete_many(['campaign_id', $this->id()]);
        //TODO
        return $this;
    }

    public function getBatchOfSubscribers()
    {
        return $this->Sellvana_Email_Model_Mailing_ListRecipient->orm('lr')
            ->where('lr.list_id', $this->get('list_id'))
            ->join('Sellvana_Email_Model_Mailing_Subscriber', ['s.id', '=', 'lr.subscriber_id'], 's')
            ->left_outer_join('Sellvana_Email_Model_Mailing_CampaignRecipient', ['cr.list_recipient_id', '=', 'lr.id'], 'cr')
            ->where_raw("cr.status is null or cr.status='P'")
            ->select(['lr.*', 's.email', 's.firstname', 's.lastname', 's.company'])
            ->limit(100)
            ->find_many_assoc('subscriber_id');
    }

    public function sendToSubscribers($subs)
    {
        $rcptHlp = $this->Sellvana_Email_Model_Mailing_CampaignRecipient;
        if ($subs) {
            $rcpts = $rcptHlp->orm()
                ->where('campaign_id', $this->id())
                ->where_in('subscriber_id', array_keys($subs))
                ->find_many_assoc('subscriber_id');
        } else {
            $rcpts = [];
        }
        /**
         * @var int $subId
         * @var Sellvana_Email_Model_Mailing_CampaignRecipient $sub
         */
        foreach ($subs as $subId => $sub) {
            if (empty($rcpts[$subId])) {
                $rcpt[$subId] = $rcptHlp->create([
                    'campaign_id' => $this->id(),
                    'subscriber_id' => $subId,
                    'list_recipient_id' => $sub->id(),
                    'status' => 'P',
					'email' => $sub->get('email'),
					'firstname' => $sub->get('firstname'),
					'lastname' => $sub->get('lastname'),
					'company' => $sub->get('company'),
                ]);
            }
            $rcpt[$subId]->sendEmail($this);
        }
    }

    public function start()
    {
        $cntTotal = $this->Sellvana_Email_Model_Mailing_ListRecipient->orm()->where('list_id', $this->get('list_id'))->count();
        $this->set([
            'status' => 'R',
            'cnt_total' => $cntTotal,
            'cnt_sent' => 0,
            'cnt_success' => 0,
            'cnt_error' => 0,
        ])->save();
        $this->Sellvana_Email_Model_Mailing_CampaignRecipient->delete_many(['campaign_id' => $this->id()]);

        $campaign = $this;
        while (true) {
            $subs = $this->getBatchOfSubscribers();
            if (!$subs) {
                $campaign->set('status', 'C')->save();
            }
            $campaign->sendToSubscribers($subs);

            $campaign = $this->load($this->id());
            if ($campaign->get('status') !== 'R') {
                break;
            }
        }

        $this->set('status', 'C')->save();
        return $this;
    }

    public function pause()
    {
        $this->set('status', 'P')->save();
        return $this;
    }

    public function resume()
    {
        $this->set('status', 'R')->save();
        return $this;
    }

    public function stop()
    {
        $this->set('status', 'S')->save();
        return $this;
    }
}