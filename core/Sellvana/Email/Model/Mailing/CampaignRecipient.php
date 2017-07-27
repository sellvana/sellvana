<?php

/**
 * Class Sellvana_Email_Model_Mailing_ListRecipient
 *
 * @property Sellvana_Email_Model_Mailing_Subscriber Sellvana_Email_Model_Mailing_Subscriber
 * @property Sellvana_Email_Model_Mailing_ListRecipient Sellvana_Email_Model_Mailing_ListRecipient
 * @property Sellvana_Email_Model_Mailing_Event Sellvana_Email_Model_Mailing_Event
 * @property Sellvana_Email_Model_Mailing_Link Sellvana_Email_Model_Mailing_Link
 */
class Sellvana_Email_Model_Mailing_CampaignRecipient extends FCom_Core_Model_Abstract
{
    static protected $_table = 'fcom_mailing_campaign_recipient';

    static protected $_fieldOptions = [
        'status' => [
            'P' => 'Pending',
            'E' => 'Error',
            'S' => 'Sent',
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

    public function sendEmail(Sellvana_Email_Model_Mailing_Campaign $campaign)
    {
        $body = str_replace(
            ['{{email}}', '{{firstname}}', '{{lastname}}', '{{company}}', '{{unsub_url}}', '{{pixel_url}}'],
            [$this->get('email'), $this->get('firstname'), $this->get('lastname'), $this->get('company'),
                $this->getUnsubscribeUrl($campaign), $this->getPixelUrl($campaign)],
            $campaign->get('template_html')
        );
        $body = preg_replace_callback('#\{\{[\'"](.*?)[\'"]\s*\|\s*track_link\s*\}\}#', function ($m) use ($campaign) {
            $links = $campaign->get('links');
            $url = $m[1];
            if (empty($links[$url])) {
                $links[$url] = $this->Sellvana_Email_Model_Mailing_Link->create([
                    'campaign_id' => $campaign->id(),
                    'link_href' => $url,
                ])->save();
                $campaign->set('links', $links);
            }
            return $this->getTrackLinkUrl($links[$url], $campaign);
        }, $body);

        try {
            $result = $this->BEmail->send([
                'content-type' => 'text/html; charset=UTF-8',
                'from' => '"' . $campaign->get('sender_name') . '" <' . $campaign->get('sender_email') . '>',
                'to' => '"' . $this->get('firstname') . ' ' . $this->get('lastname') . '" <' . $this->get('email') . '>',
                'subject' => $campaign->get('subject'),
                'body' => $body,
            ]);
            if (!$result) {
                $message = error_get_last()['message'];
            }
        } catch (Exception $e) {
            $result = false;
            $message = $e->getMessage();
        }

        if (!$this->id()) {
            $this->set([
                'campaign_id' => $campaign->id(),
                'status' => $result ? 'S' : 'E',
            ])->save();
        }

        $event = $this->Sellvana_Email_Model_Mailing_Event->create([
            'campaign_id' => $campaign->id(),
            'subscriber_id' => $this->get('subscriber_id'),
            'list_recipient_id' => $this->get('list_recipient_id'),
            'campaign_recipient_id' => $this->id(),
            'event_type' => $result ? 'sent' : 'error',
        ]);
        if (!empty($message)) {
            $event->setData('message', $message);
        }
        $event->save();

        $campaign->add('cnt_sent');
        if ($result) {
            $campaign->add('cnt_success');
        } else {
            $campaign->add('cnt_error');
        }
        $campaign->save();

        return $this;
    }

    public function getUnsubscribeUrl(Sellvana_Email_Model_Mailing_Campaign $campaign)
    {
        return $this->BApp->frontendHref($this->BUtil->setUrlQuery('mailings/unsubscribe', [
            'campaign' => $campaign->get('unique_id'),
            'recipient' => $this->get('unique_id'),
        ]));
    }

    public function getPixelUrl(Sellvana_Email_Model_Mailing_Campaign $campaign)
    {
        return $this->BApp->frontendHref($this->BUtil->setUrlQuery('mailings/pixel', [
            'campaign' => $campaign->get('unique_id'),
            'recipient' => $this->get('unique_id'),
        ]));
    }

    public function getTrackLinkUrl(Sellvana_Email_Model_Mailing_Link $link, Sellvana_Email_Model_Mailing_Campaign $campaign)
    {
        return $this->BApp->frontendHref($this->BUtil->setUrlQuery('mailings/link', [
            'link' => $link->get('unique_id'),
            'campaign' => $campaign->get('unique_id'),
            'recipient' => $this->get('unique_id'),
        ]));
    }
}