<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Email_Main
 *
 * @property FCom_Admin_Model_Role $FCom_Admin_Model_Role
 * @property Sellvana_Email_Model_Pref $Sellvana_Email_Model_Pref
 * @property Sellvana_Email_Model_Message $Sellvana_Email_Model_Message
 */
class Sellvana_Email_Main extends BClass
{
    public function bootstrap()
    {
        $this->BEmail->addHandler('Sellvana_Email', 'Sellvana_Email_Main::handler');

        $c = $this->BConfig->get('modules/Sellvana_Email');
        if (!empty($c['smtp_host'])) {
            ini_set('SMTP', $c['smtp_host']);
        }
        if (!empty($c['sendmail_from'])) {
            ini_set('sendmail_from', $c['sendmail_from']);
        }

        if (!empty($c['default_handler'])) {
            $this->BEmail->setDefaultHandler($c['default_handler']);
        }

        $this->FCom_Admin_Model_Role->createPermission([
            'subscriptions' => 'Email Subscriptions',
        ]);
    }

    public function onEmailSendBefore($args)
    {
        $email = $args['email_data']['to'];
        $pref = $this->Sellvana_Email_Model_Pref->load($email, 'email');
        return $pref && $pref->unsub_all ? false : true;
    }

    public function handler($data)
    {
        /** @var Sellvana_Email_Model_Message $msg */
        $msg = $this->Sellvana_Email_Model_Message->create([
            'recipient' => $data['to'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => 'sending',
            'view_name' => !empty($data['orig_data']['view_name']) ? $data['orig_data']['view_name'] : null,
        ]);
        $msg->setData($this->BUtil->arrayMask($data, 'headers,params,files,orig_data'));
        $msg->save();

        $this->BDebug->startErrorLogger();
        $result = $this->BEmail->defaultHandler($data);
        $errors = $this->BDebug->stopErrorLogger();

        if ($result) {
            $msg->set([
                'status' => 'success',
            ])->save();
            return true;
        }
        if ($errors) {
            $msg->set([
                'status' => 'error',
                'error_message' => $errors[0]['message'],
                'num_attempts' => $msg->num_attempts + 1,
            ])->save();
            //TODO: create retry logic
        }
        return false;
    }
}
