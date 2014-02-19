<?php
namespace Craft;

class PicPuller_OauthController extends BaseController
{
    protected $allowAnonymous = array('actionAuthenticate');
    //protected $allowAnonymous = true;

    /**
     *
     * Save Credentials
     *
     * Save credentials entered in via the control panel set up form
     */

    public function actionSaveCredentials()
    {
    	$this->requirePostRequest();

        $model = new PicPuller_CredentialsModel();

        $attributes = craft()->request->getPost();
        $model->setAttributes($attributes);

    	if (craft()->picPuller_appCreation->saveCredentials($model))
        {
            craft()->userSession->setNotice(Craft::t('Credentials saved.'));
            $this->redirectToPostedUrl();
        }
        else
        {
            $attributes = craft()->request->getPost('clientId');
            $model->setAttributes(craft()->request->getPost());


            // Prepare a flash error message for the user.
            craft()->userSession->setError(Craft::t('Couldn’t save credentials.'));

            // Make the ingredient model available to the template as an 'ingredient' variable,
            // since it contains the user's dumb input as well as the validation errors.
            craft()->urlManager->setRouteVariables(array(
                'ig_credentials' => $model
            ));
        }


    }

    /**
     * Authenticate
     *
     * respond to Instagram authenication request
     */

    public function actionAuthenticate()
    {
        $this->requirePostRequest();

        $attributes = craft()->request->getPost();
        // $this->returnJson($attributes);
        // prepare the data to post back to Instagram
        // in order to get the authorization codes
        $urltopost = "https://api.instagram.com/oauth/access_token";
        $datatopost = array(
            'client_id'=>$attributes['clientId'],
            'client_secret'=>$attributes['clientSecret'],
            'grant_type'=>'authorization_code',
            'redirect_uri'=> $attributes['authUrl'],
            'code'=>$attributes['code']
            );

        if ( !$this->_iscurlinstalled() ){
            craft()->userSession->setError(Craft::t('Required cURL function not available'));
            $json->{'error_type'} = 'ServerError';
            $json->{'error_message'} = 'Pic Puller requires cURL to complete authorization with Instagram and it is not available on the server.';

            $json->{'success'} = false;
            $this->returnJson($json);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $urltopost);
        // to prevent the response from being outputted
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // POST to the Instagram auth url
        curl_setopt($ch, CURLOPT_POST, 1);
        // adding the post variables to the request
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datatopost);
        // false = don't verify the SSL cert - many servers
        // don't have certificates installed
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $returndata = curl_exec($ch);

        $json = json_decode($returndata);

        if (isset($json->{'access_token'}) ){
            $model = new PicPuller_OauthModel();

            $formattedReturnedData = [
                'app_id'=>$attributes['appId'],
                'oauth'=>$json->{'access_token'},
                'instagram_id'=>$json->{'user'}->id,
                'member_id'=>$attributes['userId']
            ];

            $model->setAttributes($formattedReturnedData);

            // Does an oAuth already exist for this user and this app?
            // If so, we'll drop it from the database before adding a
            // new one.
            $existingOauthId = craft()->db->createCommand()
                ->select('id')
                ->from('picpuller_oauths')
                ->where(array('and','member_id='.$attributes['userId'], 'app_id='.$attributes['appId']))
                ->queryRow();
            if ($existingOauthId) {
                craft()->picPuller_appCreation->deleteOauthById($existingOauthId['id']);
            }
            // Now that we know we won't be creating a duplicate oAuth for this user with this
            // app we will have the newly created model to the database
            if (craft()->picPuller_appCreation->saveOauth($model))
            {
               $this->returnJson(['success' => true]);
            }
            else
            {
                craft()->userSession->setError(Craft::t('Damn it!'));
            }

        }  else {
            craft()->userSession->setError(Craft::t('Couldn’t save oAuth to database.'));
            // $json->{'error_type'} and $json->{'error_message'} should be returned from Instagram.
            // Usually, this is an OAuthException resulting from an incorrect password.
            $json->{'success'} = false;
            $this->returnJson($json);
        }

    }

    /**
     * Is cURL available?
     * @return BOOL true is returned if cURL is available and false if not
     */
    private function _iscurlinstalled() {
        if  (in_array  ('curl', get_loaded_extensions())) {
            return true;
        }
        else{
            return false;
        }
    }


    /**
     * Remove an oAuth
     * @return [type] [description]
     */
    public function actionRemoveOauth() {

        $this->requirePostRequest();

        $attributes = craft()->request->getPost();

        return craft()->picPuller_appCreation->deleteOauthById($attributes['oauth_id']);
    }

    /**
     * Remove Instagram application and all user oAuth
     * @return BOOL Returns true or false
     */

    public function actionDeleteApplication() {
        $this->requireAdmin();
        $this->requirePostRequest();
        $attributes = craft()->request->getPost();
        Craft::log('PICPULLER: actionDeleteApplication '. $attributes['id']);
        if (craft()->picPuller_appCreation->deleteAppById($attributes['id']))
            {
                craft()->userSession->setNotice(Craft::t('Application deleted successfully.'));
                $this->redirectToPostedUrl();
            } else {
                craft()->userSession->setError(Craft::t('Application could not be deleted.'));
            }

    }


    // TESTING function only --- remove it
    public function actionSaveOauth()
    {
        $model = new PicPuller_OauthModel();
        //$attributes = craft()->request->getPost();

        $fakeattributes = [
            'app_id'=> rand(10,100),
            'oauth' => '757575.2342.23423',
            'instagram_id' => '23423',
            'member_id' => rand(10,100)
        ];

        //$this->returnJson($fakeattributes);
        $model->setAttributes($fakeattributes);
        // $this->returnJson($model);

        if (craft()->picPuller_appCreation->saveOauth($model))
        {
           craft()->userSession->setNotice(Craft::t('oAuth saved.'));
            //$this->redirectToPostedUrl();
           $this->redirect('picpuller', $terminate = true, $statusCode = 200);

        }
        else
        {
            craft()->userSession->setError(Craft::t('Damn it!'));

        }
    }



}