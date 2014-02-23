<?php

namespace Craft;

defined('CRAFT_PLUGINS_PATH')      || define('CRAFT_PLUGINS_PATH',      CRAFT_BASE_PATH.'plugins/');

require_once(CRAFT_PLUGINS_PATH.'picpuller/lib/FirePHPCore/fb.php');
/*

Digging around? Enable FirePHP debugging by changin "devMode" to true in your config file, or, FB::setEnabled(true);
You'll need to use FirePHP for Firefox or FirePHP4Chrome and look at your console in your web browser.


*/
\FB::setEnabled(craft()->config->get('devMode'));

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
class PicPuller_FeedReaderService extends BaseApplicationComponent
{

	private $cache_name = 'ig_picpuller';
	private $_ig_picpuller_prefix = '';
	private $use_stale = TRUE;
	private $refresh = 45;	// Period between cache refreshes, in minutes


	/**
	 * Get popular photos from Instagram
	 * @access public
	 * @param  Array 	limit
	 * @return Arra     An array of images and associated information
	 */
	public function popular($tags = null)
	{
		Craft::log("Pic Puller: popular");

		$variables = array();
		$clientId = $this->_getClientId();

		if ( !isset($clientId) ) {
			return $this->_clientIdNotSetErrorReturn();
		};

		$limit = isset($tags['limit']) ? $tags['limit'] : '';

		if($limit != '')
		{
			$limit = "&count=$limit";
		}

		$use_stale = isset($tags['use_stale']) ? $tags['use_stale'] : $this->use_stale;

		// set up the POPULAR url used by Instagram
		$query_string ="https://api.instagram.com/v1/media/popular?client_id=$clientId". $limit;
		$data = $this->_fetch_data($query_string, $use_stale);

		if ($data['status'] === FALSE ) {
			// No images to return, even from cache, so exit the function and return the error
			// Set up the basic error messages returned by _fetch_data function
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
			return $variables;
		}

		$cacheddata = (isset($data['cacheddata'])) ? 'yes' : 'no';

		foreach($data['data'] as $node)
		{
			$variables[] = array(
				$this->_ig_picpuller_prefix.'type' => $node['type'],
				$this->_ig_picpuller_prefix.'video_low_resolution' => isset($node['videos']['low_resolution']['url']) ? $node['videos']['low_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'video_standard_resolution' => isset($node['videos']['standard_resolution']['url']) ? $node['videos']['standard_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'username' => $node['user']['username'],
				$this->_ig_picpuller_prefix.'full_name' => $node['user']['full_name'],
				$this->_ig_picpuller_prefix.'profile_picture' => isset($node['user']['profile_picture']['url']) ? $node['user']['profile_picture']['url'] : '',
				$this->_ig_picpuller_prefix.'created_time' => $node['created_time'],
				$this->_ig_picpuller_prefix.'link' => $node['link'],
				$this->_ig_picpuller_prefix.'caption' => $node['caption']['text'],
				$this->_ig_picpuller_prefix.'low_resolution' => $node['images']['low_resolution']['url'],
				$this->_ig_picpuller_prefix.'thumbnail' => $node['images']['thumbnail']['url'],
				$this->_ig_picpuller_prefix.'standard_resolution' => $node['images']['standard_resolution']['url'],
				$this->_ig_picpuller_prefix.'latitude' => isset($node['location']['latitude']) ? $node['location']['latitude'] : '',
				$this->_ig_picpuller_prefix.'longitude' => isset($node['location']['longitude']) ? $node['location']['longitude'] : '',
				$this->_ig_picpuller_prefix.'media_id' => $node['id'],
				$this->_ig_picpuller_prefix.'comment_count' => $node['comments']['count'],
				$this->_ig_picpuller_prefix.'likes' => $node['likes']['count'],
				$this->_ig_picpuller_prefix.'status' => $data['status'],
				$this->_ig_picpuller_prefix.'cacheddata' => $cacheddata,
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message']

			);
		}

		return $variables;
	}

	/**
	 * User
	 *
	 * Get the user information from a specified Craft user that has authorized the Instagram application
	 * http://instagram.com/developer/endpoints/users/#get_users
	 * @param	tag param, 'user_id', the Craft member ID of a user that has authorized the Instagram application
	 * @param 	use_stale:
	 * @return	tag data, username, bio, profile_picture, website, full_name, counts_media, counts_followed_by, counts_follows, id, status
	 */

	public function user($tags = null)
	{
		Craft::log('Pic Puller: user');

		$variables = array();
		$clientId = $this->_getClientId();

		if ( !isset($clientId) ) {
			return $this->_clientIdNotSetErrorReturn();
		};

		$user_id = isset($tags['user_id']) ? $tags['user_id'] : '';

		if ( $user_id == '' ) {
			return $this->_missinguser_idErrorReturn();
		}

		$use_stale = isset($tags['use_stale']) ? $tags['use_stale'] : $this->use_stale;

		$oauth = $this->_getUserOauth($user_id);

		if(!$oauth)
		{
			return $this->_unauthorizedUserErrorReturn();
		}

		// set up the USERS url used by Instagram
		$query_string = "https://api.instagram.com/v1/users/self?access_token={$oauth}";

		$data = $this->_fetch_data($query_string, $use_stale);

		if ($data['status'] === FALSE ) {
			// No images to return, even from cache, so exit the function and return the error
			// Set up the basic error messages returned by _fetch_data function
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
			return $variables;
		}

		$cacheddata = (isset($data['cacheddata'])) ? 'yes' : 'no';
		$node = $data['data'];
		$variables[] = array(
			$this->_ig_picpuller_prefix.'username' => $node['username'],
			$this->_ig_picpuller_prefix.'bio' => $node['bio'],
			$this->_ig_picpuller_prefix.'profile_picture' => $node['profile_picture'],
			$this->_ig_picpuller_prefix.'website' => $node['website'],
			$this->_ig_picpuller_prefix.'full_name' => $node['full_name'],
			$this->_ig_picpuller_prefix.'counts_media' => strval($node['counts']['media']),
			$this->_ig_picpuller_prefix.'counts_followed_by' => strval($node['counts']['followed_by']),
			$this->_ig_picpuller_prefix.'counts_follows' => strval($node['counts']['follows']),
			$this->_ig_picpuller_prefix.'id' => $node['id'],
			$this->_ig_picpuller_prefix.'status' => $data['status'],
			$this->_ig_picpuller_prefix.'cacheddata' => $cacheddata,
			$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
			$this->_ig_picpuller_prefix.'error_message' => $data['error_message']
		);
		return $variables;
	}


	/**
	 * Media
	 *
	 * Get information about a single media object.
	 * http://instagram.com/developer/endpoints/media/#get_media
	 *
	 * @access	public
	 * @param	tag param: 'user_id', the Craft member ID of a user that has authorized the Instagram application
	 * @param 	tag param: 'media_id', the Instagram media ID of the image to be returned
	 * @param 	use_stale:
	 * @return	tag data: status, username, user_id, full_name, profile_picture, website, created_time, link, caption, low_resolution, thumbnail, standard_resolution, latitude, longitude, likes
	 */
	 public function media($tags = null)
	 {
		Craft::log('Pic Puller: media');

		$variables = array();
		$use_stale = isset($tags['use_stale']) ? $tags['use_stale'] : $this->use_stale;

		$clientId = $this->_getClientId();

		if ( !isset($clientId) ) {
			return $this->_clientIdNotSetErrorReturn();
		};

		$user_id = isset($tags['user_id']) ? $tags['user_id'] : '';

		if ( $user_id == '' ) {
			return $this->_missinguser_idErrorReturn();
		}


		$oauth = $this->_getUserOauth($user_id);

		if(!$oauth)
		{
			return $this->_unauthorizedUserErrorReturn();
		}

		$media_id = isset($tags['media_id']) ? $tags['media_id'] : '';

		if($media_id == '')
		{
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => 'MissingReqParameter',
				$this->_ig_picpuller_prefix.'error_message' => 'No media_id set for this function',
				$this->_ig_picpuller_prefix.'status' => 'false'
			);

			return $variables;
		}

		// set up the MEDIA url used by Instagram
		$query_string = "https://api.instagram.com/v1/media/{$media_id}?access_token={$oauth}";

		$data = $this->_fetch_data($query_string, $use_stale);

		if ($data['status'] === FALSE ) {
			// No images to return, even from cache, so exit the function and return the error
			// Set up the basic error messages returned by _fetch_data function
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
			return $variables;
		}

		$cacheddata = (isset($data['cacheddata'])) ? 'yes' : 'no';

		$node = $data['data'];

		$variables[] = array(
			$this->_ig_picpuller_prefix.'type' => $node['type'],
			$this->_ig_picpuller_prefix.'video_low_resolution' => isset($node['videos']['low_resolution']['url']) ? $node['videos']['low_resolution']['url'] : "",
			$this->_ig_picpuller_prefix.'video_standard_resolution' => isset($node['videos']['standard_resolution']['url']) ? $node['videos']['standard_resolution']['url'] : "",
			$this->_ig_picpuller_prefix.'username' => $node['user']['username'],
			$this->_ig_picpuller_prefix.'user_id' => $node['user']['id'],
			$this->_ig_picpuller_prefix.'full_name' => $node['user']['full_name'],
			$this->_ig_picpuller_prefix.'profile_picture' => $node['user']['profile_picture'],
			$this->_ig_picpuller_prefix.'website' => $node['user']['website'],
			$this->_ig_picpuller_prefix.'created_time' => $node['created_time'],
			$this->_ig_picpuller_prefix.'link' => $node['link'],
			$this->_ig_picpuller_prefix.'caption' => $node['caption']['text'],
			$this->_ig_picpuller_prefix.'low_resolution' => $node['images']['low_resolution']['url'],
			$this->_ig_picpuller_prefix.'thumbnail' => $node['images']['thumbnail']['url'],
			$this->_ig_picpuller_prefix.'standard_resolution' => $node['images']['standard_resolution']['url'],
			$this->_ig_picpuller_prefix.'latitude' => isset($node['location']['latitude']) ? $node['location']['latitude'] : '',
			$this->_ig_picpuller_prefix.'longitude' => isset($node['location']['longitude']) ? $node['location']['longitude'] : '',
			$this->_ig_picpuller_prefix.'comment_count' => $node['comments']['count'],
			$this->_ig_picpuller_prefix.'likes' => $node['likes']['count'],
			$this->_ig_picpuller_prefix.'cacheddata' => $cacheddata,
			$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
			$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
			$this->_ig_picpuller_prefix.'status' => $data['status']
		);
		return $variables;
	 }




	/**
	 * Media Recent
	 *
	 * Get the most recent media published from a specified EE user that has authorized the Instagram application
	 * http://instagram.com/developer/endpoints/users/#get_users_media_recent
	 *
	 * @access	public
	 * @param	tag param: 'user_id', the EE member ID of a user that has authorized the Instagram application
	 * @param 	tag param: 'limit', an integer that determines how many images to return
	 * @param 	use_stale:
	 * @return	tag data: caption, media_id, next_max_id, low_resolution, thumbnail, standard_resolution, latitude, longitude, link, created_time
	 */

	public function media_recent($tags = null)
	{
		Craft::log('Pic Puller: media_recent');

		$variables = array();
		$clientId = $this->_getClientId();

		if ( !isset($clientId) ) {
			return $this->_clientIdNotSetErrorReturn();
		};

		$user_id = isset($tags['user_id']) ? $tags['user_id'] : '';

		if ( $user_id == '' ) {
			return $this->_missinguser_idErrorReturn();
		}

		$use_stale = isset($tags['use_stale']) ? $tags['use_stale'] : $this->use_stale;

		$limit = isset($tags['limit']) ? $tags['limit'] : '';

		if($limit != '')
		{
			$limit = "&count=$limit";
		}

		$min_id = isset($tags['min_id']) ? $tags['min_id'] : '';

		if($min_id != '')
		{
			$min_id = "&min_id=$min_id";
		}

		$max_id = isset($tags['max_id']) ? $tags['max_id'] : '';

		if($max_id != '')
		{
			$max_id = "&max_id=$max_id";
		}

		$ig_user_id = $this->_getInstagramId($user_id);

		if(!$ig_user_id)
		{
			return $this->_noInstagramIdErrorReturn();
		}

		$oauth = $this->_getUserOauth($user_id);

		if(!$oauth)
		{
			return $this->_unauthorizedUserErrorReturn();
		}

		// set up the MEDIA/RECENT url used by Instagram
		$query_string = "https://api.instagram.com/v1/users/{$ig_user_id}/media/recent/?access_token={$oauth}". $limit.$max_id.$min_id;

		$data = $this->_fetch_data($query_string, $use_stale);

		if ($data['status'] === FALSE ) {
			// No images to return, even from cache, so exit the function and return the error
			// Set up the basic error messages returned by _fetch_data function
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
			return $variables;
		}

		$node = $data['data'];

		$next_max_id = '';
		if (isset($data['pagination']['next_max_id'])){
			$next_max_id = $data['pagination']['next_max_id'];
		}

		$cacheddata = (isset($data['cacheddata'])) ? 'yes' : 'no';

		foreach($data['data'] as $node)
		{
			$variables[] = array(
				$this->_ig_picpuller_prefix.'type' => $node['type'],
				$this->_ig_picpuller_prefix.'video_low_resolution' => isset($node['videos']['low_resolution']['url']) ? $node['videos']['low_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'video_standard_resolution' => isset($node['videos']['standard_resolution']['url']) ? $node['videos']['standard_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'created_time' => $node['created_time'],
				$this->_ig_picpuller_prefix.'link' => $node['link'],
				$this->_ig_picpuller_prefix.'caption' => $node['caption']['text'],
				$this->_ig_picpuller_prefix.'low_resolution' => $node['images']['low_resolution']['url'],
				$this->_ig_picpuller_prefix.'thumbnail' => $node['images']['thumbnail']['url'],
				$this->_ig_picpuller_prefix.'standard_resolution' => $node['images']['standard_resolution']['url'],
				$this->_ig_picpuller_prefix.'latitude' => isset($node['location']['latitude']) ? $node['location']['latitude'] : '',
				$this->_ig_picpuller_prefix.'longitude' => isset($node['location']['longitude']) ? $node['location']['longitude'] : '',
				$this->_ig_picpuller_prefix.'media_id' => $node['id'],
				$this->_ig_picpuller_prefix.'next_max_id' => $next_max_id,
				$this->_ig_picpuller_prefix.'comment_count' => $node['comments']['count'],
				$this->_ig_picpuller_prefix.'likes' => $node['likes']['count'],
				$this->_ig_picpuller_prefix.'cacheddata' => $cacheddata,
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
		}
		return $variables;
	}


/**
	 * User Feed
	 *
	 * Get the feed of a specified EE user that has authorized the Instagram application
	 * http://instagram.com/developer/endpoints/users/#get_users_feed
	 *
	 * @access	public
	 * @param	tag param: 'user_id', the EE member ID of a user that has authorized the Instagram application
	 * @param 	tag param: 'limit', an integer that determines how many images to return
	 * @param 	use_stale_cache:
	 * @return	tag data: caption, media_id, next_max_id, low_resolution, thumbnail, standard_resolution, latitude, longitude, link, created_time, profile_picture, username, website, full_name, user_id
	 */

	public function user_feed($tags = null)
	{
		Craft::log('Pic Puller: user_feed');

		$variables = array();

		// if (!$this->applicationExists() ) {
		// 	$variables[] = array(
		// 		$this->_ig_picpuller_prefix.'error_type' => 'NoInstagramApp',
		// 		$this->_ig_picpuller_prefix.'error_message' => 'There is no application stored in the Expression Engine data base. It appear set up is not complete.',
		// 		$this->_ig_picpuller_prefix.'status' => 'false'
		// 	);

		// 	return $variables;
		// };

		$use_stale = isset($tags['use_stale']) ? $tags['use_stale'] : $this->use_stale;


		$user_id = isset($tags['user_id']) ? $tags['user_id'] : '';

		if ( $user_id == '' ) {
			return $this->_missinguser_idErrorReturn();
		}

		$limit = isset($tags['limit']) ? $tags['limit'] : '';

		if($limit != '')
		{
			$limit = "&count=$limit";
		}

		$min_id = isset($tags['min_id']) ? $tags['min_id'] : '';

		if($min_id != '')
		{
			$min_id = "&min_id=$min_id";
		}

		$max_id = isset($tags['max_id']) ? $tags['max_id'] : '';

		if($max_id != '')
		{
			$max_id = "&max_id=$max_id";
		}

		$ig_user_id = $this->_getInstagramId($user_id);

		if(!$ig_user_id)
		{
			return $this->_noInstagramIdErrorReturn();
		}

		$oauth = $this->_getUserOauth($user_id);

		if(!$oauth)
		{
			return $this->_unauthorizedUserErrorReturn();
		}

		$query_string = "https://api.instagram.com/v1/users/self/feed?access_token={$oauth}". $limit.$max_id.$min_id;

		$data = $this->_fetch_data($query_string, $use_stale);

		if ($data['status'] === FALSE ) {
			// No images to return, even from cache, so exit the function and return the error
			// Set up the basic error messages returned by _fetch_data function
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
			return $variables;
		}

		$node = $data['data'];
		//$next_url = isset($data['pagination']['next_url']) ? $data['pagination']['next_url'] : 'no';

		$next_max_id = '';
		if (isset($data['pagination']['next_max_id'])){
			$next_max_id = $data['pagination']['next_max_id'];
		}

		$cacheddata = (isset($data['cacheddata'])) ? 'yes' : 'no';

		foreach($data['data'] as $node)
		{
			$variables[] = array(
				$this->_ig_picpuller_prefix.'type' => $node['type'],
				$this->_ig_picpuller_prefix.'video_low_resolution' => isset($node['videos']['low_resolution']['url']) ? $node['videos']['low_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'video_standard_resolution' => isset($node['videos']['standard_resolution']['url']) ? $node['videos']['standard_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'created_time' => $node['created_time'],
				$this->_ig_picpuller_prefix.'link' => $node['link'],
				$this->_ig_picpuller_prefix.'caption' => $node['caption']['text'],
				$this->_ig_picpuller_prefix.'low_resolution' => $node['images']['low_resolution']['url'],
				$this->_ig_picpuller_prefix.'thumbnail' => $node['images']['thumbnail']['url'],
				$this->_ig_picpuller_prefix.'standard_resolution' => $node['images']['standard_resolution']['url'],
				$this->_ig_picpuller_prefix.'latitude' => isset($node['location']['latitude']) ? $node['location']['latitude'] : '',
				$this->_ig_picpuller_prefix.'longitude' => isset($node['location']['longitude']) ? $node['location']['longitude'] : '',
				$this->_ig_picpuller_prefix.'media_id' => $node['id'],
				$this->_ig_picpuller_prefix.'next_max_id' => $next_max_id,
				$this->_ig_picpuller_prefix.'profile_picture' => $node['user']['profile_picture'],
				$this->_ig_picpuller_prefix.'username' => $node['user']['username'],
				$this->_ig_picpuller_prefix.'website' => $node['user']['website'],
				$this->_ig_picpuller_prefix.'full_name' => $node['user']['full_name'],
				$this->_ig_picpuller_prefix.'user_id' => $node['user']['id'],
				$this->_ig_picpuller_prefix.'comment_count' => $node['comments']['count'],
				$this->_ig_picpuller_prefix.'likes' => $node['likes']['count'],
				$this->_ig_picpuller_prefix.'cacheddata' => $cacheddata,
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
		}
		return $variables;
	}

	/**
	 * User Liked
	 *
	 * Get liked media of a specified EE user that has authorized the Instagram application
	 * http://instagram.com/developer/endpoints/users/#get_users_liked_feed
	 *
	 * @access	public
	 * @param	tag param: 'user_id', the EE member ID of a user that has authorized the Instagram application
	 * @param 	tag param: 'limit', an integer that determines how many images to return
	 * @param 	use_stale_cache:
	 * @return	tag data: caption, media_id, next_max_id, low_resolution, thumbnail, standard_resolution, latitude, longitude, link, created_time, profile_picture, username, website, full_name, user_id
	 */

	public function user_liked($tags = null)
	{
		Craft::log('Pic Puller: user_liked');

		$variables = array();

		// if (!$this->applicationExists() ) {
		// 	$variables[] = array(
		// 		$this->_ig_picpuller_prefix.'error_type' => 'NoInstagramApp',
		// 		$this->_ig_picpuller_prefix.'error_message' => 'There is no application stored in the Expression Engine data base. It appear set up is not complete.',
		// 		$this->_ig_picpuller_prefix.'status' => 'false'
		// 	);

		// 	return $variables;
		// };

		$use_stale = isset($tags['use_stale']) ? $tags['use_stale'] : $this->use_stale;

		$user_id = isset($tags['user_id']) ? $tags['user_id'] : '';

		if ( $user_id == '' ) {
			return $this->_missingUser_idErrorReturn();
		}

		$limit = isset($tags['limit']) ? $tags['limit'] : '';

		if($limit != '')
		{
			$limit = "&count=$limit";
		}

		$min_id = isset($tags['min_id']) ? $tags['min_id'] : '';


		if($min_id != '')
		{
			$min_id = "&min_id=$min_id";
		}

		$max_id = isset($tags['max_id']) ? $tags['max_id'] : '';

		if($max_id != '')
		{
			$max_id = "&max_id=$max_id";
		}

		$ig_user_id = $this->_getInstagramId($user_id);

		if(!$ig_user_id)
		{
			return $this->_noInstagramIdErrorReturn();
		}

		$oauth = $this->_getUserOauth($user_id);

		if(!$oauth)
		{
			return $this->_unauthorizedUserErrorReturn();
		}

		$query_string = "https://api.instagram.com/v1/users/self/media/liked?access_token={$oauth}". $limit.$max_id.$min_id;

		$data = $this->_fetch_data($query_string, $use_stale);

		if ($data['status'] === FALSE ) {
			// No images to return, even from cache, so exit the function and return the error
			// Set up the basic error messages returned by _fetch_data function
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
			return $variables;
		}

		$node = $data['data'];
		$next_max_id = '';
		if (isset($data['pagination']['next_max_like_id'])){
			$next_max_id = $data['pagination']['next_max_like_id'];
		}

		$cacheddata = (isset($data['cacheddata'])) ? 'yes' : 'no';

		foreach($data['data'] as $node)
		{
			$variables[] = array(
				$this->_ig_picpuller_prefix.'type' => $node['type'],
				$this->_ig_picpuller_prefix.'video_low_resolution' => isset($node['videos']['low_resolution']['url']) ? $node['videos']['low_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'video_standard_resolution' => isset($node['videos']['standard_resolution']['url']) ? $node['videos']['standard_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'created_time' => $node['created_time'],
				$this->_ig_picpuller_prefix.'link' => $node['link'],
				$this->_ig_picpuller_prefix.'caption' => $node['caption']['text'],
				$this->_ig_picpuller_prefix.'low_resolution' => $node['images']['low_resolution']['url'],
				$this->_ig_picpuller_prefix.'thumbnail' => $node['images']['thumbnail']['url'],
				$this->_ig_picpuller_prefix.'standard_resolution' => $node['images']['standard_resolution']['url'],
				$this->_ig_picpuller_prefix.'latitude' => isset($node['location']['latitude']) ? $node['location']['latitude'] : '',
				$this->_ig_picpuller_prefix.'longitude' => isset($node['location']['longitude']) ? $node['location']['longitude'] : '',
				$this->_ig_picpuller_prefix.'media_id' => $node['id'],
				$this->_ig_picpuller_prefix.'next_max_id' => $next_max_id,
				$this->_ig_picpuller_prefix.'profile_picture' => $node['user']['profile_picture'],
				$this->_ig_picpuller_prefix.'username' => $node['user']['username'],
				$this->_ig_picpuller_prefix.'website' => $node['user']['website'],
				$this->_ig_picpuller_prefix.'full_name' => $node['user']['full_name'],
				$this->_ig_picpuller_prefix.'user_id' => $node['user']['id'],
				$this->_ig_picpuller_prefix.'comment_count' => $node['comments']['count'],
				$this->_ig_picpuller_prefix.'likes' => $node['likes']['count'],
				$this->_ig_picpuller_prefix.'cacheddata' => $cacheddata,
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
		}
		return $variables;
	}

	/**
	 * Recent Media by Tag
	 *
	 * Get a list of recently tagged media. Note that this media is ordered by when the media was tagged with this tag, rather than the order it was posted.
	 * http://instagram.com/developer/endpoints/tags/#get_tags_media_recent
	 *
	 * @access	public
	 * @param	tag param: 'user_id', the EE member ID of a user that has authorized the Instagram application
	 * @param 	tag param: 'limit', an integer that determines how many images to return
	 * @param 	use_stale_cache:
	 * @return	tag data: caption, media_id, next_max_id, low_resolution, thumbnail, standard_resolution, latitude, longitude, link, created_time, profile_picture, username, website, full_name, user_id
	 */

	public function tagged_media($tags = null)
	{
		Craft::log('Pic Puller: tagged_media');

		$variables = array();

		// if (!$this->applicationExists() ) {
		// 	$variables[] = array(
		// 		$this->_ig_picpuller_prefix.'error_type' => 'NoInstagramApp',
		// 		$this->_ig_picpuller_prefix.'error_message' => 'There is no application stored in the Expression Engine data base. It appear set up is not complete.',
		// 		$this->_ig_picpuller_prefix.'status' => 'false'
		// 	);

		// 	return $variables;
		// };

		$use_stale = isset($tags['use_stale']) ? $tags['use_stale'] : $this->use_stale;

		$variables = array();
		$user_id = isset($tags['user_id']) ? $tags['user_id'] : '';

		if ( $user_id == '' ) {
			return $this->_missingUser_idErrorReturn();
		}

		$limit = isset($tags['limit']) ? $tags['limit'] : '';

		if($limit != '')
		{
			$limit = "&count=$limit";
		}

		$min_id = isset($tags['min_id']) ? $tags['min_id'] : '';

		if($min_id != '')
		{
			$min_id = "&min_id=$min_id";
		}

		$max_id = isset($tags['max_id']) ? $tags['max_id'] : '';

		if($max_id != '')
		{
			$max_id = "&max_id=$max_id";
		}

		$tag = isset($tags['tag']) ? $tags['tag'] : '';

		if($tag == '')
		{
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => 'MissingReqParameter',
				$this->_ig_picpuller_prefix.'error_message' => 'No tag to search for set for this function',
				$this->_ig_picpuller_prefix.'status' => 'false'
			);

			return $variables;
		}

		$ig_user_id = $this->_getInstagramId($user_id);

		if(!$ig_user_id)
		{
			return $this->_noInstagramIdErrorReturn();
		}

		$oauth = $this->_getUserOauth($user_id);

		if(!$oauth)
		{
			return $this->_unauthorizedUserErrorReturn();
		}

		$query_string = "https://api.instagram.com/v1/tags/$tag/media/recent?access_token={$oauth}". $limit.$max_id.$min_id;

		$data = $this->_fetch_data($query_string, $use_stale);

		if ($data['status'] === FALSE ) {
			// No images to return, even from cache, so exit the function and return the error
			// Set up the basic error messages returned by _fetch_data function
			$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
			return $variables;
		}

		$node = $data['data'];

		$next_max_id = '';
		if (isset($data['pagination']['next_max_id'])){
			$next_max_id = $data['pagination']['next_max_id'];
		}

		$cacheddata = (isset($data['cacheddata'])) ? 'yes' : 'no';
		foreach($data['data'] as $node)
		{
			$variables[] = array(
				$this->_ig_picpuller_prefix.'type' => $node['type'],
				$this->_ig_picpuller_prefix.'video_low_resolution' => isset($node['videos']['low_resolution']['url']) ? $node['videos']['low_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'video_standard_resolution' => isset($node['videos']['standard_resolution']['url']) ? $node['videos']['standard_resolution']['url'] : "",
				$this->_ig_picpuller_prefix.'created_time' => $node['created_time'],
				$this->_ig_picpuller_prefix.'link' => $node['link'],
				$this->_ig_picpuller_prefix.'caption' => $node['caption']['text'],
				$this->_ig_picpuller_prefix.'low_resolution' => $node['images']['low_resolution']['url'],
				$this->_ig_picpuller_prefix.'thumbnail' => $node['images']['thumbnail']['url'],
				$this->_ig_picpuller_prefix.'standard_resolution' => $node['images']['standard_resolution']['url'],
				$this->_ig_picpuller_prefix.'latitude' => isset($node['location']['latitude']) ? $node['location']['latitude'] : '',
				$this->_ig_picpuller_prefix.'longitude' => isset($node['location']['longitude']) ? $node['location']['longitude'] : '',
				$this->_ig_picpuller_prefix.'media_id' => $node['id'],
				$this->_ig_picpuller_prefix.'next_max_id' => $next_max_id,
				$this->_ig_picpuller_prefix.'profile_picture' => $node['user']['profile_picture'],
				$this->_ig_picpuller_prefix.'username' => $node['user']['username'],
				$this->_ig_picpuller_prefix.'website' => $node['user']['website'],
				$this->_ig_picpuller_prefix.'full_name' => $node['user']['full_name'],
				$this->_ig_picpuller_prefix.'user_id' => $node['user']['id'],
				$this->_ig_picpuller_prefix.'comment_count' => $node['comments']['count'],
				$this->_ig_picpuller_prefix.'likes' => $node['likes']['count'],
				$this->_ig_picpuller_prefix.'cacheddata' => $cacheddata,
				$this->_ig_picpuller_prefix.'error_type' => $data['error_type'],
				$this->_ig_picpuller_prefix.'error_message' => $data['error_message'],
				$this->_ig_picpuller_prefix.'status' => $data['status']
			);
		}
		return $variables;
	}










	 /******************************************
	  *                                        *
	  *     -------------------------------    *
	  *		PRIVATE HELPER FUNCTIONS FOLLOW    *
	  *     -------------------------------    *
	  *                  ***                   *
	  *                                        *                                        *
	  ******************************************/


	/**
	 * Get the client ID for the registered Instagram application
	 * @return STR client ID
	 */
	private function _getClientId() {
		$clientId = craft()->db->createCommand()
                ->select('clientId')
                ->from('picpuller_credentials')
                ->limit(1)
                ->queryRow();
        return $clientId['clientId'];
	}

	/**
	 * A single function to return a consistent error message when a clientID isn't set for a function
	 * @return ARR error_type, error_message, and status
	 */
	private function _noAppErrorReturn() {
		$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => 'NoInstagramApp',
				$this->_ig_picpuller_prefix.'error_message' => 'There is no application stored in the Craft data base. It appears the set up process is not complete.',
				$this->_ig_picpuller_prefix.'status' => 'false'
			);

		return $variables;
	}

	/**
	 * A single function to return a consistent error message when a clientID isn't set for a function
	 * @return ARR error_type, error_message, and status
	 */
	private function _clientIdNotSetErrorReturn() {
		$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => 'NoInstagramApp',
				$this->_ig_picpuller_prefix.'error_message' => 'There is no application stored in the Craft data base. It appear set up is not complete.',
				$this->_ig_picpuller_prefix.'status' => 'false'
			);

		return $variables;
	}


	/**
	 * A single function to return a consistent error message when a Craft user hasn't authorized Pic Puller
	 * @return ARR error_type, error_message, and status
	 */
	private function _unauthorizedUserErrorReturn() {
		$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => 'UnauthorizedUser',
				$this->_ig_picpuller_prefix.'error_message' => 'User has not authorized Pic Puller for access to Instagram.',
				$this->_ig_picpuller_prefix.'status' => 'false'
			);

		return $variables;
	}


	/**
	 * A single function to return a consistent error message when an Instagram ID couldn't be retrieved
	 * @return ARR error_type, error_message, and status
	 */
	private function _noInstagramIdErrorReturn() {
		$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => 'NoInstagramId',
				$this->_ig_picpuller_prefix.'error_message' => 'No Instagram ID was stored for the specified Craft user.',
				$this->_ig_picpuller_prefix.'status' => 'false'
			);
		return $variables;
	}

	/**
	 * A single function to return a consistent error message when a Craft user_id has not been supplied to a function
	 * @return ARR error_type, error_message, and status
	 */
	private function _missinguser_idErrorReturn() {
		$variables[] = array(
				$this->_ig_picpuller_prefix.'error_type' => 'MissingReqParameter',
				$this->_ig_picpuller_prefix.'error_message' => 'No user ID set for this function',
				$this->_ig_picpuller_prefix.'status' => 'false'
			);
		return $variables;
	}






	/**
	 * Get user oAuth by Craft user ID
	 * @param  INT $user_id Craft user ID
	 * @return STR     oAuth code for Instagram user for this app
	 */
	private function _getUserOauth( $user_id ) {
        return craft()->picPuller_appCreation->getUserOauthValue($user_id);
    }


    /**
	 * Get Instagram ID
	 *
	 * Get Instagram ID for an EE member ID
	 *
	 * @access	private
	 * @param	string - User ID number for an EE member
	 * @return	mixed - returns Instagram ID if available in DB, or FALSE if unavailable
	 */

	private function _getInstagramId($user_id)
	{
		return craft()->picPuller_appCreation->getInstagramId($user_id);
	}


	/**
	 * Fetch Data
	 *
	 * Using CURL, fetch requested Instagram URL and return with validated data
	 *
	 * @access	private
	 * @param	string - a full Instagram API call URL
	 * @return	array - the original data or cached data (if stale allowed) with the error array
	 */

	private function _fetch_data($url, $use_stale)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		// to prevent the response from being outputted
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// POST to the Instagram auth url
		//curl_setopt($ch, CURLOPT_POST, 1);
		// adding the post variables to the request
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $datatopost);
		// don't verify the SSL cert
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);

		$valid_data = $this->_validate_data($data, $url, $use_stale);

		return $valid_data;

	}

	/**
	 * Validate Data
	 *
	 * Validate that data coming in from an Instagram API call is valid data and respond with that data plus error_state details
	 *
	 * @access	private
	 * @param	string - the data to validate
	 * @param	string - the URL that generated that data
	 * @return	array - the original data or cached data (if stale allowed) with the error array
	 */

	private function _validate_data($data, $url, $use_stale){

		// to FAKE a non-responsive error from Instagram, change the initial conditional meta code statement below
		// from $data !== '' to $data === ''

		if ($data !== '' && isset($data['meta']))
		{
			$error_array;
			$meta = $data['meta'];
			// 200 means IG api did respond with good data
			if ($meta['code'] == 200)
			{
				// There is an outlying chance that IG says 200, but the data array is empty.
				// Pic Puller considers that an error so we return a custom error message
				if(count($data['data']) == 0) {
					$error_array = array(
						'status' => FALSE,
						'error_message' => "There was no media to return for that user.",
						'error_type' => 'NoData'
					);
				}
				else
				{
					$error_array = array(
						'status' => TRUE,
						'error_message' => "Nothing wrong here. Move along.",
						'error_type' => 'NoError'
					);
					// Fresher valid data was received, so update the cache to reflect that.
					$this->_write_cache($data, $url);
				}
			}
			else // this ELSE loop is only executed when the meta['code'] is not 200, typically, it's a 400
			{
				// even though there is an error, we can use old data if
				// use_stale is set to YES. Here the $data passed in is replace with
				// a cache version of itself if available
				if ($use_stale == TRUE)
				{
					$data = $this->_check_cache($url, $use_stale);

					if ($data) {
						$data['cacheddata'] = TRUE;
						$error_array = array(
							'status' => TRUE,
							'error_message' => (isset($meta['error_message']) ? $meta['error_message'] : 'No data returned from Instagram API. Check http://api-status.com/6404/174981/Instagram-API. Using cached data.' ), //. ' Using stale data as back up if available.',
							'error_type' =>  (isset($meta['error_type']) ? $meta['error_type'] : 'NoCodeReturned')
						);
					}
					else
					{

						$error_array = array(
							'status' => FALSE,
							'error_message' => (isset($meta['error_message']) ? $meta['error_message'] : 'No error message provided by Instagram. No cached data available.' ),
							'error_type' =>  (isset($meta['error_type']) ? $meta['error_type'] : 'NoCodeReturned')
						);

						return $error_array;
					}
				}
			}

		}
		else // The was no response at all from Instagram, so make a custom error message.
		{

			if ($use_stale == TRUE)
			{
				$data = $this->_check_cache($url, $use_stale);
			}
			if ($data) {
				$data['cacheddata'] = TRUE;
				$error_array = array(
					'status' => TRUE,
					'error_message' => (isset($meta['error_message']) ? $meta['error_message'] : 'No data returned from Instagram API. Check http://api-status.com/6404/174981/Instagram-API. Using cached data.' ), //. ' Using stale data as back up if available.',
					'error_type' =>  (isset($meta['error_type']) ? $meta['error_type'] : 'NoCodeReturned')
				);
			}

		}
		return array_merge($data, $error_array);
		// merge the original data or cached data (if stale allowed) with the error array
	}
	// ---------- CACHE CONTROL/ ------------- //

	/**
	 * Check Cache
	 *
	 * Check for cached data
	 *
	 * @access	private
	 * @param	string
	 * @param	bool	Allow pulling of stale cache file
	 * @return	mixed - string if pulling from cache, FALSE if not
	 */
	private function _check_cache($url, $use_stale = FALSE)
	{
		// Check for cache directory
		Craft::log('Pic Puller: Checking Cache');
		$cacheDirectory = craft()->path->getCachePath() . '/' . $this->cache_name . '/';
		$dir = $cacheDirectory;

		if ( ! @is_dir($dir))
		{
			Craft::log('CHECK CASHE: directory wasn\'t accessible');
			return FALSE;
		}

		// Check for cache file

		$file = $dir.md5($url);

		if ( ! file_exists($file) OR ! ($fp = @fopen($file, 'rb')))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);

		$cache = @fread($fp, filesize($file));

		flock($fp, LOCK_UN);

		fclose($fp);

		// Grab the timestamp from the first line

		$eol = strpos($cache, "\n");

		$timestamp = substr($cache, 0, $eol);
		$cache = trim((substr($cache, $eol)));

		if ($use_stale == FALSE && time() > ($timestamp + ($this->refresh * 60)))
		{
			return FALSE;
		}

		Craft::log("Instagram data retrieved from cache");

		$cache = json_decode($cache, true);

		return $cache;
	}


	/**
	 * Write Cache
	 *
	 * Write the cached data
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	private function _write_cache($data, $url)
	{

		// Check for cache directory
		Craft::log('Pic Puller: _write_cache $data '. gettype($data));

		$cacheDirectory = craft()->path->getCachePath() . '/' . $this->cache_name . '/';

		$data = json_encode($data);

		$dir = $cacheDirectory;

		if ( ! @is_dir($dir))
		{
			if ( ! @mkdir($dir, 0777))
			{
				return FALSE;
			}

			@chmod($dir, 0777);
		}

		// add a timestamp to the top of the file
		$data = time()."\n".$data;

		// Write the cached data
		$file = $dir.md5($url);

		if ( ! $fp = @fopen($file, 'wb'))
		{
			return FALSE;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($file, 0777);

		// now clean up the cache
		$this->_clear_cache();
	}

	/**
	 * Clear out the cache directory and keep only the 25 most recent files
	 * @return NULL
	 */
	private function _clear_cache()
	{
		$cacheDirectory = craft()->path->getCachePath() . '/' . $this->cache_name . '/';
		$file = '*';
		$dir = $cacheDirectory;

		$sorted_array = $this->listdir_by_date($dir.$file);

		$count = count($sorted_array);
		foreach ($sorted_array as $value) {
			if($count > 25 ){
			// unlinking, as in deleting, cache files that are oldest, but keeping 25 most recent
			unlink($dir.$value);
			}
			$count--;
		}
	}

	/**
	 * List files in a directory by the date created
	 * @param  STR $pathtosearch The server path to the directory in question
	 * @return ARR The files in order
	 */
	private function listdir_by_date($pathtosearch)
	{
		foreach (glob($pathtosearch) as $filename)
		{
			$file_array[filectime($filename)]=basename($filename); // or just $filename
		}
		ksort($file_array);

		return $file_array;
	}
}