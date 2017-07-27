<?php

/**
 * Class Sellvana_Email_Frontend_Controller_Mailings
 *
 * @property Sellvana_Email_Model_Mailing_Campaign Sellvana_Email_Model_Mailing_Campaign
 * @property Sellvana_Email_Model_Mailing_CampaignRecipient Sellvana_Email_Model_Mailing_CampaignRecipient
 * @property Sellvana_Email_Model_Mailing_Link Sellvana_Email_Model_Mailing_Link
 * @property Sellvana_Email_Model_Mailing_Event Sellvana_Email_Model_Mailing_Event
 * @property Sellvana_Email_Model_Mailings_Subscriber Sellvana_Email_Model_Mailings_Subscriber
 */
class Sellvana_Email_Frontend_Controller_Mailings extends FCom_Frontend_Controller_Abstract
{
    public function action_link()
    {
        $campaignId = $this->BRequest->get('campaign_id');
        $recipientId = $this->BRequest->get('recipient_id');
        $linkId = $this->BRequest->get('link_id');
        if (!$campaignId || !$recipientId || !$linkId) {
            $this->BResponse->status(403);
        }

        $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId, 'unique_id');
        $recipient = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->load($recipientId, 'unique_id');
        $link = $this->Sellvana_Email_Model_Mailing_Link->load($linkId, 'unique_id');
        if (!$campaign || !$recipient || !$link
            || $recipient->get('campaign_id') !== $campaignId
            || $link->get('campaign_id') !== $campaignId
        ) {
            $this->BResponse->status(403);
        }

        $this->Sellvana_Email_Model_Mailing_Event->create([
            'campaign_id' => $campaignId,
            'campaign_recipient_id' => $recipientId,
            'link_id' => $linkId,
            'event_type' => 'link',
        ])->save();

        $this->BResponse->redirect($link->get('link_href'));
    }

    public function action_pixel()
    {
        $campaignId = $this->BRequest->get('campaign_id');
        $recipientId = $this->BRequest->get('recipient_id');
        if (!$campaignId || !$recipientId) {
            $this->BResponse->status(403);
        }

        $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId, 'unique_id');
        $recipient = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->load($recipientId, 'unique_id');
        if (!$campaign || !$recipient || $recipient->get('campaign_id') !== $campaignId) {
            $this->BResponse->status(403);
        }

        $this->Sellvana_Email_Model_Mailing_Event->create([
            'campaign_id' => $campaignId,
            'campaign_recipient_id' => $recipientId,
            'event_type' => 'pixel',
        ])->save();

        $clearPixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $this->BResponse->setContentType('image/png')->set($clearPixel);
    }

    public function action_unsubscribe()
    {
        $campaignId = $this->BRequest->get('campaign_id');
        $recipientId = $this->BRequest->get('recipient_id');
        if (!$campaignId || !$recipientId) {
            $this->BResponse->status(403);
        }

        $campaign = $this->Sellvana_Email_Model_Mailing_Campaign->load($campaignId, 'unique_id');
        $recipient = $this->Sellvana_Email_Model_Mailing_CampaignRecipient->load($recipientId, 'unique_id');
        if (!$campaign || !$recipient || $recipient->get('campaign_id') !== $campaignId) {
            $this->BResponse->status(403);
        }

        $this->Sellvana_Email_Model_Mailing_Event->create([
            'campaign_id' => $campaignId,
            'campaign_recipient_id' => $recipientId,
            'event_type' => 'unsub',
        ])->save();

        $sub = $this->Sellvana_Email_Model_Mailings_Subscriber->load($recipient->get('subscriber_id'));
        $this->view('mailings/unsubscribe')->set('email', $sub->get('email'));
        $this->layout('/mailings/unsubscribe');
    }
}