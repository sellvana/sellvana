<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_OAuth_Controller extends FCom_Core_Controller_Abstract
{
    public function action_login()
    {
        $hlp = $this->FCom_OAuth_Main;
        $returnUrl = $this->BRequest->get('redirect_to');
        if (!$r->isUrlLocal($returnUrl)) {
            $returnUrl = '';
        }
        if (!$returnUrl) {
            $returnUrl = $this->BApp->href('login');
        }
        $providerName = $this->BRequest->param('provider', true);
        if ($returnUrl) {
            $hlp->setReturnUrl($returnUrl);
        }
        try {
            $hlp->setProvider($providerName);
            $authUrl = $hlp->loginAction();
            $this->BResponse->redirect($authUrl);
        } catch (Exception $e) {
echo "<pre>"; print_r($e); exit;
            $area = $this->BRequest->area() === 'FCom_Admin' ? 'admin' : 'frontend';
            $this->BSession->addMessage($e->getMessage(), 'error', $area);
            $this->BResponse->redirect($returnUrl);
        }
    }

    public function action_callback()
    {
        $hlp = $this->FCom_OAuth_Main;
        $returnUrl = $hlp->getReturnUrl();
        try {
            $hlp->callbackAction();
        } catch (Exception $e) {
echo "<pre>"; print_r($e); exit;
            $area = $this->BRequest->area() === 'FCom_Admin' ? 'admin' : 'frontend';
            $this->BSession->addMessage($e->getMessage(), 'error', $area);
        }
        $this->BResponse->redirect($returnUrl);
    }

    public function action_test1()
    {
        $prov = $this->FCom_OAuth_Main->setProvider('twitter')->getProviderInfo('twitter');
        $oauth = new OAuthSimple();
        $c = $this->BConfig->get('modules/FCom_OAuth/twitter');
        $signatures = [ 'consumer_key' => $c['consumer_key'], 'shared_secret' => $c['consumer_secret']];
        $oauth->setAction('POST');
        $signed = $oauth->sign(['path' => $prov['request'], 'signatures' => $signatures]);
echo "<pre>"; var_dump($signed);
        //$response = $this->BUtil->remoteHTTP('GET', $prov['request_url'], $signed['parameters'], ['Authorization: ' . $signed['header']]);
        $response = $this->BUtil->remoteHTTP('POST', $signed['signed_url'], [], ['Authorization: ' . $signed['header']]);
echo "<pre>"; var_dump($response);
exit;
    }

    public function action_test2()
    {
        require(__DIR__.'/lib/http.php');
        require(__DIR__.'/lib/oauth_client.php');

        $c = $this->BConfig->get('modules/FCom_OAuth/bitbucket');

        $client = new oauth_client_class;
        $client->debug = 1;
        $client->debug_http = 1;
        $client->server = 'BitBucket';
        #$client->redirect_uri = 'http://'.$_SERVER['HTTP_HOST'].
            #dirname(strtok($_SERVER['REQUEST_URI'],'?')).'/login_with_twitter.php';
        $client->redirect_uri = 'http://127.0.0.1/sellvana/index.php/oauth/callback';#$this->BApp->href('oauth/callback');
    //    $client->redirect_uri = 'oob';

        $client->client_id = $c['consumer_key']; $application_line = __LINE__;
        $client->client_secret = $c['consumer_secret'];

        if(strlen($client->client_id) == 0
        || strlen($client->client_secret) == 0)
            die('Please go to Twitter Apps page https://dev.twitter.com/apps/new , '.
                'create an application, and in the line '.$application_line.
                ' set the client_id to Consumer key and client_secret with Consumer secret. '.
                'The Callback URL must be '.$client->redirect_uri.' If you want to post to '.
                'the user timeline, make sure the application you create has write permissions');

        if(($success = $client->Initialize()))
        {
            if(($success = $client->Process()))
            {
                if(strlen($client->access_token))
                {
                    $success = $client->CallAPI(
                        'https://api.twitter.com/1.1/account/verify_credentials.json',
                        'GET', array(), array('FailOnAccessError'=>true), $user);

    /*
                    $values = array(
                        'status'=>str_repeat('x', 140)
                    );
                    $success = $client->CallAPI(
                        'https://api.twitter.com/1.1/statuses/update.json',
                        'POST', $values, array('FailOnAccessError'=>true), $update);
                    if(!$success)
                        error_log(print_r($update->errors[0]->code, 1));
    */

    /* Tweet with an attached image

                    $success = $client->CallAPI(
                        "https://api.twitter.com/1.1/statuses/update_with_media.json",
                        'POST', array(
                            'status'=>'This is a test tweet to evaluate the PHP OAuth API support to upload image files sent at '.strftime("%Y-%m-%d %H:%M:%S"),
                            'media[]'=>'php-oauth.png'
                        ),array(
                            'FailOnAccessError'=>true,
                            'Files'=>array(
                                'media[]'=>array(
                                )
                            )
                        ), $user);
    */
                }
            }
            $success = $client->Finalize($success);
        }
    }

}
