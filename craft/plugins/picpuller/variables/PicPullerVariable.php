<?php

namespace Craft;

/**
 * Cocktail Recipes Variable provides access to database objects from templates
 */
class PicPullerVariable
{
    public function __constructor(Array $tags = null)
    {
        return "constructor";
    }

    /**
     * Get popular photos from Instagram
     * @param  Array  $tags [description]
     * @return Array  An array of media and user data
     */
    public function popular($tags = null) {
        return craft()->picPuller_feedReader->popular($tags);
    }

    /**
     * Get user info from Instagram
     * @param  Array  $tags [description]
     * @return Array  An array of media and user data
     */
    public function user($tags = null) {
        return craft()->picPuller_feedReader->user($tags);
    }

    /**
     * Get a single piece of media from Instagram
     * @param  Array  $tags [description]
     * @return Array  An array of media and user data
     */
    public function media($tags = null) {
        return craft()->picPuller_feedReader->media($tags);
    }

    /**
     * Get recent media from a single user from Instagram
     * @param  Array  $tags [description]
     * @return Array  An array of media and user data
     */
    public function media_recent($tags = null) {
        return craft()->picPuller_feedReader->media_recent($tags);
    }

    /**
     * Get the feed (those people a user follows) of the authorized user from Instagram
     * @param  Array  $tags [description]
     * @return Array  An array of media and user data
     */
    public function user_feed($tags = null) {
        return craft()->picPuller_feedReader->user_feed($tags);
    }

    /**
     * Get the liked media of the authorized user from Instagram
     * @param  Array  $tags [description]
     * @return Array  An array of media and user data
     */
    public function user_liked($tags = null) {
        return craft()->picPuller_feedReader->user_liked($tags);
    }

    /**
     * Get media based on a single tag from Instagram
     * @param  Array  $tags [description]
     * @return Array  An array of media and user data
     */
    public function tagged_media($tags = null) {
        return craft()->picPuller_feedReader->tagged_media($tags);
    }





    /**
     * REMOVE Get all available ingredients
     *
     * @return array
     */
    // public function getSome($whatever = null)
    // {
    //     return craft()->picPuller_appCreation->getSome($whatever);
    // }

    public function getCredentials() {
        return craft()->picPuller_appCreation->getCredentials();
    }

    public function getUsers() {
        return craft()->picPuller_appCreation->getUsers();
    }

    public function getUserOauthId( $id ) {
        return craft()->picPuller_appCreation->getUserOauthId($id);
    }

    /**
     * Does the application exist in the database
     * @return BOOL returns TRUE if there is an application saved in the database, or FALSE if not
     */
    // private function applicationExists() {
    //     return TRUE;
    // }

}
