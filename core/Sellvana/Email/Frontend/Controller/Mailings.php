<?php

/**
 * Class Sellvana_Email_Frontend_Controller_Mailings
 *
 * @property Sellvana_Email_Model_Mailing_Campaign Sellvana_Email_Model_Mailing_Campaign
 * @property Sellvana_Email_Model_Mailing_CampaignRecipient Sellvana_Email_Model_Mailing_CampaignRecipient
 * @property Sellvana_Email_Model_Mailing_Link Sellvana_Email_Model_Mailing_Link
 * @property Sellvana_Email_Model_Mailing_Event Sellvana_Email_Model_Mailing_Event
 * @property Sellvana_Email_Model_Mailing_Subscriber Sellvana_Email_Model_Mailing_Subscriber
 */
class Sellvana_Email_Frontend_Controller_Mailings extends FCom_Frontend_Controller_Abstract
{
    public function action_link()
    {
        $campaignId = $this->BRequest->get('campaign');
        $recipientId = $this->BRequest->get('recipient');
        $linkId = $this->BRequest->get('link');
        if (!$campaignId || !$recipientId || !$linkId) {
            $this->BResponse->status(403, 'Invalid data, access denied', 'Invalid data, access denied (1)');
            exit;
        }

        $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId, 'unique_id');
        $recipient = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->load($recipientId, 'unique_id');
        $link = $this->Sellvana_Email_Model_Mailing_Link->load($linkId, 'unique_id');
        if (!$campaign || !$recipient || !$link
            || $recipient->get('campaign_id') !== $campaign->id()
            || $link->get('campaign_id') !== $campaign->id()
        ) {
            $this->BResponse->status(403, 'Invalid data, access denied', 'Invalid data, access denied (2)');
            exit;
        }

        $this->Sellvana_Email_Model_Mailing_Event->create([
            'campaign_id' => $campaign->id(),
            'campaign_recipient_id' => $recipient->id(),
            'link_id' => $link->id(),
            'event_type' => 'link',
        ])->save();

        $link->add('cnt_clicked')->save();
		$campaign->add('cnt_clicked')->save();

        $this->BResponse->redirect($link->get('link_href'));
    }

    public function action_pixel()
    {
        $campaignId = $this->BRequest->get('campaign');
        $recipientId = $this->BRequest->get('recipient');
        if (!$campaignId || !$recipientId) {
            $this->BResponse->status(403, 'Invalid data, access denied', 'Invalid data, access denied');
            exit;
        }

        $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId, 'unique_id');
        $recipient = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->load($recipientId, 'unique_id');
        if (!$campaign || !$recipient || $recipient->get('campaign_id') !== $campaign->id()) {
            $this->BResponse->status(403, 'Invalid data, access denied', 'Invalid data, access denied');
            exit;
        }

        $this->Sellvana_Email_Model_Mailing_Event->create([
            'campaign_id' => $campaign->id(),
            'campaign_recipient_id' => $recipient->id(),
            'event_type' => 'pixel',
        ])->save();
		
		$campaign->add('cnt_opened')->save();

        $clearPixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $this->BResponse->setContentType('image/png')->set($clearPixel);
    }

    public function action_unsubscribe()
    {
        $campaignId = $this->BRequest->get('campaign');
        $recipientId = $this->BRequest->get('recipient');
        if (!$campaignId || !$recipientId) {
            $this->BResponse->status(403, 'Invalid data, access denied', 'Invalid data, access denied (1)');
            exit;
        }

        $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId, 'unique_id');
        $recipient = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->load($recipientId, 'unique_id');
        if (!$campaign || !$recipient || $recipient->get('campaign_id') !== $campaign->id()) {
            $this->BResponse->status(403, 'Invalid data, access denied', 'Invalid data, access denied (2)');
            exit;
        }

        $this->Sellvana_Email_Model_Mailing_Event->create([
            'campaign_id' => $campaign->id(),
            'campaign_recipient_id' => $recipient->id(),
            'event_type' => 'unsub',
        ])->save();
		
		$campaign->add('cnt_unsub')->save();

        $sub = $this->Sellvana_Email_Model_Mailing_Subscriber->load($recipient->get('subscriber_id'));
        $this->view('mailings/unsubscribe')->set('email', $sub->get('email'));
        $this->layout('/mailings/unsubscribe');
    }
}