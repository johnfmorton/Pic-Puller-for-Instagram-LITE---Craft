<?php

namespace Craft;

defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');

// require_once(CRAFT_PLUGINS_PATH.'picpuller/lib/FirePHPCore/fb.php');

/*

Digging around? Enable FirePHP debugging by changin "devMode" to true in your config file, or, FB::setEnabled(true);
You'll need to use FirePHP for Firefox or FirePHP4Chrome and look at your console in your web browser

*/

// \FB::setEnabled(craft()->config->get('devMode'));

// Examples:
// \FB::log('Log message', 'Label');
// \FB::info('Info message', 'Label');
// \FB::warn('Warn message', 'Label');
// \FB::error('Error message', 'Label');

/**
 * Cocktail Recipes Service
 *
 * Provides a consistent API for our plugin to access the database
 */
class PicPuller_AppCreationService extends BaseApplicationComponent
{
    protected $credentialsRecord;
    protected $oauthRecord;

    /**
     * Create a new instance of the Pic Puller Service.
     *
     */

    public function __construct($credentialsRecord = null, $oauthRecord=null)
    {
        $this->credentialsRecord = $credentialsRecord;
        if (is_null($this->credentialsRecord)) {
            $this->credentialsRecord = PicPuller_CredentialsRecord::model();
        }
        $this->oauthRecord = $oauthRecord;
        if (is_null($this->oauthRecord)) {
            $this->oauthRecord = PicPuller_OauthRecord::model();
        }
    }

    /**
     * Get the word "SOME" unless another $thing was defined.
     *
     * @param @thing an unneccesarry variable
     */
    // public function getSome($thing = null) {
    //     if(is_null($thing)) {
    //         $thing = "SOME";
    //     }

    //     return $thing;
    // }

    // public function testQuery() {

    //     $existingOauth = craft()->db->createCommand()
    //             ->select('id, instagram_id, oauth')
    //             ->from('picpuller_oauths')
    //             //->where(array('member_id=member_id', 'app_id=app_id'))
    //             ->query();


    //      return $existingOauth;
    // }

    public function getCredentials() {
        $record = $this->credentialsRecord->find(array('limit'=>'1'));
        return PicPuller_CredentialsModel::populateModel($record);
    }

    public function saveCredentials(PicPuller_CredentialsModel &$model) {

        if ($id = $model->getAttribute('id')) {
            if (null === ($record = $this->credentialsRecord->findByPk($id))) {
                throw new Exception(Craft::t('Can\'t find credentialsRecord with ID "{id}"', array('id' => $id)));
            }
        } else {
            $record = $this->credentialsRecord->create();
        }

        $record->setAttributes($model->getAttributes());
        if ($record->save()) {
            // update id on model (for new records)
            $model->setAttribute('id', $record->getAttribute('id'));
            return true;
        } else {
            $model->addErrors($record->getErrors());

            return false;
        }
    }

    /**
     * Save an oAuth record into the database
     * @param  PicPuller_OauthModel $model An instance of an oAuth model
     * @return BOOL                      True or false depending on success
     */
    public function saveOauth(PicPuller_OauthModel &$model) {
        $record = $this->oauthRecord->create();

        $record->setAttributes($model->getAttributes());

        if ($record->save()) {
            return true;
        } else {
            $model->addErrors($record->getErrors());
            return false;
        }
    }

    /**
     * Delete a single saved oAuth by its ID
     * @param  INT $id the ID of an oAuth record in the Craft database
     * @return BOOL     True or false as to whether the record was deleted
     */
    public function deleteOauthById($id) {
        return $this->oauthRecord->deleteByPk($id);
    }

    /**
     * Delete an Instagram application by its ID
     * @param INT $id the ID of the app to delete
     * @return BOOL true or false
     */
    public function deleteAppById($id) {
        Craft::log('PICPULLER service: deleteAppById '.$id);

        if( $this->credentialsRecord->deleteByPk($id) )
        {
            $this->oauthRecord->deleteAll(
                'app_id=:app_id',
                array(':app_id' => $id)
            );
            return true;
        } else {
            return false;
        }

    }

    public function getUsers() {

        $users = craft()->db->createCommand()
                ->select('member_id, instagram_id, oauth, u.firstname, u.lastname, u.username')
                ->from('picpuller_oauths oauth')
                ->join('users u', 'oauth.member_id=u.id')
                ->order('member_id')
                ->query();
         return $users;
    }

    /**
     * Return the ID of the oAuth for a user based on the user's ID
     * @param  INT $id The ID of the Craft user
     * @return INT     The ID of the oAuth for the user
     */
    public function getUserOauthId( $id ) {
        $user = craft()->db->createCommand()
                ->select('id')
                ->from('picpuller_oauths')
                ->where('member_id=' . $id )
                ->queryRow();
        return $user['id'];
    }

    /**
     * Return the oAuth value of for a user based on the user's Craft ID
     * @param  INT $id The ID of the Craft user
     * @return STR     The oAuth for the user
     */
    public function getUserOauthValue( $id ) {
        $oauth = craft()->db->createCommand()
                ->select('oauth')
                ->from('picpuller_oauths')
                ->where('member_id=' . $id )
                ->queryRow();
        return $oauth['oauth'];
    }

    /**
     * Return the Instagram ID of for a user based on the user's Craft ID
     * @param  INT $id The ID of the Craft user
     * @return STR     The Instagram ID for the user
     */
    public function getInstagramId($userId) {
        $instagram_id = craft()->db->createCommand()
            ->select('instagram_id')
            ->from('picpuller_oauths')
            ->where('member_id=' . $userId )
            ->queryRow();
        return $instagram_id['instagram_id'];
    }

}
