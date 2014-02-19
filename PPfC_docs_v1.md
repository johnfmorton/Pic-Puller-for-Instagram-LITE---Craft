# Documentation

## Overview

Pic Puller for Craft, a.k.a. PPfC, helps you set up your _own_ application with Instagram. That application will exist within your Craft CMS installation. Pic Puller walks you through the steps of creating the application, authorizing your Craft users via oAuth, and provides Craft tags for you to access the data that comes in from Instagram.

### Version History

* Version 1.0 
	* Initial release of PPfC

### How to install Pic Puller for Craft

Copy the "picpuller" directory that sits alongside this file to your Craft site in the plugins directory. The path to that direction should be "craft/plugins/".

Log into your control panel of Craft and visit the Settings page. From here, select "Plugins". You should see "Pic Puller" listed among your plugins. You will need to click the "Install" button to the right of the line. Once installed, Pic Puller's status should be "Enabled" automatically.

Once PPfC is installed, you will see it added to the top-level navigation in your control panel. Select "Pic Puller" from the top-level navigation.

### Setting up your own Instagram application

You should now see the PPfC control panel with 3 sections: Set Up, Active App Info & Authorized Users.

In the Set Up section, follow the onscreen instructions to set up your Instagram application.

The basic workflow will take you to Instagram to set up an application. PPfC provides you with an oAuth url that Instagram will ask for. Once the application is created within the Instagram developer area, you will take the Client ID and Client Secret put them into the PPfC Set Up screen and save them.

You can then authorize your new Instagram application to access your Instagram media.

### Other users and Pic Puller for Craft

You are not limited to only one user with PPfC. If you have the "Users" package activated in Craft, other users can also authorize their accounts as well.

Any "Admin" level user will be able to alter and delete the Pic Puller application.

Users that have not been granted "Admin" rights, can still access Pic Puller as long as you have granted them access under the "Users" tab. This can be done on a one-by-one basis under the "Users" tab, or on a more global scale if you set up User Groups and provide the group access to Pic Puller. These non-admin users will only have the ability to authorize and unauthorize your app. They will not be able to change or delete the app.

## Working with Pic Puller for Craft in your templates

There are 7 different template functions available with PPfC.

### Popular Photos on Instagram

#### craft.picpuller.popular

Description: Get a list of what media is most popular at the moment.

Instragram docs page for this function: [http://instagram.com/developer/endpoints/media/#get_media_popular](http://instagram.com/developer/endpoints/media/#get_media_popular "Instagram documentation for get_media_popular")

**Required parameters: none**

**Optional parameters:**

limit: an integer for how many images to request from Instagram. Instagram may return fewer under some circumstances. Maximum of 32 allowed by Instagram.

use_stale_cache: BOOLEAN, either TRUE or FALSE (defaults to TRUE if undefined), to have Pic Puller use previously cached data returned in the event of an error in retrieving new data

**Tags returned in a successful Craft loop:**

status: a BOOLEAN of TRUE (1) is returned when Instagram media data is returned, even if it is cached data

type: returns a string "image" or "video"

media_id: the Instagram unique media ID for the image or video

created_time: time stamp of image creation time, Unix timestamp formatted

link: URL of the images homepage on Instagram

caption: The caption provided by the author. Note, it may be left untitled which will return an empty string.

thumbnail: URL to image, sized 150x150

low_resolution: URL to image, sized 306x306

standard_resolution: URL to image, sized 612x612

video_low_resolution: URL to video, sized 480x480

video_standard_resolution: URL to video, sized 640x640

latitude: latitude data, if available

longitude: longitude data, if available

username: the Instagram username of the user whose account the image is from

full_name: the full name provided by the user whose account the image is from

profile_picture: URL to the profile image of the user

error_type: a string of "NoError" to indicate a successful call to the Instagram API
resulting in valid data OR a string of "NoCodeReturned" indicating there was no data returned from Instagram

error_message: a string describing the error

**Tags returned in an unsuccessful Craft loop:**

status: a BOOLEAN of FALSE (0) is returned when no data is returned from Instagram or there is no cache data to return

error_type: a single code word indicating the type of error (NoInstagramApp, MissingReqParameter, UnauthorizedUser issued by Pic Puller. Other codes are passed through from Instagram.)

error_message: a string describing the error

**Example template code:**

	{% for instagramdata in craft.picpuller.popular({'use_stale_cache' : true, 'limit': 20}) %}
		{% if loop.first %}
			<p>Status: {{ instagramdata.status }}</p>
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
			<hr>
		{% endif %}
        <p>caption: {{ instagramdata.caption }}</p>
        <p>type: {{ instagramdata.type }}</p>
        <p>cacheddata: {{ instagramdata.cacheddata }}</p>
        <p><img src="{{ instagramdata.low_resolution }}"></p><br>
	{% endfor %}


### User information

#### craft.picpuller.user

Description: Get basic information about a user.

Instragram docs page for this function:
[http://instagram.com/developer/endpoints/users/#get_users](http://instagram.com/developer/endpoints/users/#get_users "Instagram documentation for get_users")

**Required parameters:**

user_id: the Craft user id (not an Instagram user id)

**Optional parameters:**

use_stale_cache: BOOLEAN, either TRUE or FALSE (defaults to TRUE if undefined), to have Pic Puller use previously cached data returned in the event of an error in retrieving new data

**Tags returned in a successful Craft loop:**

status: a BOOLEAN of TRUE (1) is returned when Instagram media data is returned, even if it is cached data

username: the Instagram username

id: the Instagram user id

bio: biography information provided by the Instagram user

profile_picture: URL to the profile image of the user

website: the website URL provided by the user on Instagram

full_name: the full name provided by the user on Instagram

counts_media: the number of images in this user’s Instagram feed in total

counts_followed_by: the number of users who follow this user on Instagram

counts_follows: the number of users this user follows on Instagram

error_type: a string of "NoError" to indicate a successful call to the Instagram API

resulting in valid data OR a string of "NoCodeReturned" indicating there was no data returned from Instagram

error_message: a string describing the error

**Tags returned in an unsuccessful Craft loop:**

status: a BOOLEAN of FALSE (0) is returned when no data is returned from Instagram or there is no cache data to return

error_type: a single code word indicating the type of error (NoInstagramApp, MissingReqParameter, UnauthorizedUser issued by Pic Puller. Other codes are passed through from Instagram.)

error_message: a string describing the error

**Example template code:**

	{% for instagramdata in craft.picpuller.user({'use_stale_cache' : true, 'user_id' : 1 }) %}
		<p>Status: {{ instagramdata.status }}</p>
		{% if instagramdata.status == TRUE %}
			<p>username: {{ instagramdata.username }}</p>
			<p>full_name: {{ instagramdata.full_name }}</p>
			<p>profile_picture:</p>
			<p><img src="{{ instagramdata.profile_picture }}" title='{{ instagramdata.full_name }}'></p>
			<p>counts_media: {{ instagramdata.counts_media }}</p>
		{% else %}
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
		{% endif %}
	{% endfor %}


### User feed

#### craft.picpuller.user_feed

Description: See the authenticated user’s feed. Includes user photos and photos of other users the select user follows in single feed.

Instragram docs page for this function:
[http://instagram.com/developer/endpoints/users/#get_users_feed](http://instagram.com/developer/endpoints/users/#get_users_feed "Instagram documentaion for get_users_feed")

**Required parameters:**

user_id: This is the ID number of an Craft user. (It is not the Instagram user id number.)

**Optional parameters:**

limit: an integer for how many images to request from Instagram. Instagram may return fewer under some circumstances. Maximum of 32 allowed by Instagram.

use_stale_cache: BOOLEAN, either TRUE or FALSE (defaults to TRUE if undefined), to have Pic Puller use previously cached data returned in the event of an error in retrieving new data

max_id: an integer used to determine pagination of results. (See next_max_id in the ‘Tags returned’ below section for more information.)

**Tags returned in a successful Craft loop:**

status: a BOOLEAN of TRUE (1) is returned when Instagram media data is returned, even if it is cached data

type: returns a string "image" or "video"

media_id: the Instagram unique media ID for the image or video

created_time: time stamp of image creation time, Unix timestamp formatted

link: URL of the images homepage on Instagram

caption: The caption provided by the author. Note, it may be left untitled which will return an empty string.

thumbnail: URL to image, sized 150x150

low_resolution: URL to image, sized 306x306

standard_resolution: URL to image, sized 612x612

video_low_resolution: URL to video, sized 480x480

video_standard_resolution: URL to video, sized 640x640

latitude: latitude data, if available

longitude: longitude data, if available

next_max_id: an integer, provided by Instagram, used to return the next set in the same series of images.
Pass this value into the max_id parameter of the loop to get the next page of results.

user_id: the Instagram user ID of the user whose account the image is from

username: the Instagram username of the user whose account the image is from

profile_picture: URL to the profile image of the user

website: the website URL provided by the user whose account the image is from

full_name: the full name provided by the user whose account the image is from

error_type: a string of "NoError" to indicate a successful call to the Instagram API resulting in valid data OR a string of "NoCodeReturned" indicating there was no data returned from Instagram

error_message: a string describing the error

**Tags returned in an unsuccessful Craft loop:**

status: a BOOLEAN of FALSE (0) is returned when no data is returned from Instagram or there is no cache data to return

error_type: a single code word indicating the type of error (NoInstagramApp, MissingReqParameter, UnauthorizedUser issued by Pic Puller. Other codes are passed through from Instagram.)

error_message: a string describing the error

**Example template code:**

	{% for instagramdata in craft.picpuller.user_feed({'user_id' : 1,  'use_stale_cache' : true, 'limit': 20}) %}
		{% if loop.first %}
			<p>Status: {{ instagramdata.status }}</p>
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
			<hr>
		{% endif %}
		{% if instagramdata.status == 'true' %}
			<p>Loop Index: {{ loop.index }}</p>
			<p><img src="{{instagramdata.low_resolution}}"></p>
			<p>caption: {{instagramdata.caption}}</p>
			<p>created_time: {{ instagramdata.created_time }}</p>
		{% else %}
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
		{% endif %}
	{% endfor %}

### Recent media

#### craft.picpuller.media_recent

Description: Get the most recent media published by a user.

Instragram docs page for this function: [http://instagram.com/developer/endpoints/users/#get_users_media_recent](http://instagram.com/developer/endpoints/users/#get_users_media_recent "Instagram documentation for get_users_media_recent")

**Required parameters:**

user_id: This is the ID number of an Craft user. (It is not the Instagram user id number.)

**Optional parameters:**

limit: an integer for how many images to request from Instagram. Instagram may return fewer under some circumstances. Maximum of 32 allowed by Instagram.

use_stale_cache: BOOLEAN, either TRUE or FALSE (defaults to TRUE if undefined), to have Pic Puller use previously cached data returned in the event of an error in retrieving new data

max_id: an integer used to determine pagination of results. (See next_max_id in the ‘Tags returned’ below section for more information.)

**Tags returned in a successful Craft loop:**

status: a BOOLEAN of TRUE (1) is returned when Instagram media data is returned, even if it is cached data

type: returns a string "image" or "video"

media_id: the Instagram unique media ID for the image or video

created_time: time stamp of image creation time, Unix timestamp formatted

link: URL of the images homepage on Instagram

caption: The caption provided by the author. Note, it may be left untitled which will return an empty string.

thumbnail: URL to image, sized 150x150

low_resolution: URL to image, sized 306x306

standard_resolution: URL to image, sized 612x612

video_low_resolution: URL to video, sized 480x480

video_standard_resolution: URL to video, sized 640x640

latitude: latitude data, if available

longitude: longitude data, if available

next_max_id: an integer, provided by Instagram, used to return the next set in the same series of images. Pass this value into the max_id parameter of the loop to get the next page of results.

error_type: a string of "NoError" to indicate a successful call to the Instagram API resulting in valid data OR a string of "NoCodeReturned" indicating there was no data returned from Instagram

error_message: a string describing the error

**Tags returned in an unsuccessful Craft loop:**

status: a BOOLEAN of FALSE (0) is returned when no data is returned from Instagram or there is no cache data to return

error_type: a single code word indicating the type of error (NoInstagramApp, MissingReqParameter, UnauthorizedUser issued by Pic Puller. Other codes are passed through from Instagram.)

error_message: a string describing the error

**Example template code:**

	{% for instagramdata in craft.picpuller.media_recent({'user_id' : 1,  'use_stale_cache' : true, 'limit': 20}) %}
		{% if loop.first %}
			<p>Status: {{ instagramdata.status }}</p>
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
			<hr>
		{% endif %}
		{% if instagramdata.status == 'true' %}
		<p>Loop Index: {{ loop.index }}</p>
		{% if instagramdata.video_low_resolution != '' %}
			<p>THERE IS VIDEO HERE!</p>
			<p>{{ instagramdata.video_low_resolution }}</p>
		{% endif %}
		<p><img src="{{instagramdata.low_resolution}}"></p>
		<p>caption: {{instagramdata.caption}}</p>
		<p>created_time: {{ instagramdata.created_time }}</p>
		{% else %}
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
		{% endif %}
	{% endfor %}


### Liked image feed

#### craft.picpuller.user_liked

Description: See the authenticated user’s list of media they’ve liked. Note that this list is ordered by the order in which the user liked the media. Private media is returned as long as the authenticated user has permission to view that media. Liked media lists are only available for the currently authenticated user.

Instragram docs page for this function: [http://instagram.com/developer/endpoints/users/#get_users_liked_feed](http://instagram.com/developer/endpoints/users/#get_users_liked_feed "Instagram documentation for get_users_liked_photos")

**Required parameters:**

user_id: This is the ID number of an Craft user. (It is not the Instagram user id number.)

**Optional parameters:**

limit: an integer for how many images to request from Instagram. Instagram may return fewer under some circumstances. Maximum of 32 allowed by Instagram.

use_stale_cache: BOOLEAN, either TRUE or FALSE (defaults to TRUE if undefined), to have Pic Puller use previously cached data returned in the event of an error in retrieving new data

max_id: an integer used to determine pagination of results. (See next_max_id in the ‘Tags returned’ below section for more information.)

**Tags returned in a successful Craft loop:**

status: a BOOLEAN of TRUE (1) is returned when Instagram media data is returned, even if it is cached data

type: returns a string "image" or "video"

media_id: the Instagram unique media ID for the image or video

created_time: time stamp of image creation time, Unix timestamp formatted

link: URL of the images homepage on Instagram

caption: The caption provided by the author. Note, it may be left untitled which will return an empty string.

thumbnail: URL to image, sized 150x150

low_resolution: URL to image, sized 306x306

standard_resolution: URL to image, sized 612x612

video_low_resolution: URL to video, sized 480x480

video_standard_resolution: URL to video, sized 640x640

latitude: latitude data, if available

longitude: longitude data, if available

next_max_id: an integer, provided by Instagram, used to return the next set in the same series of images. Pass this value into the max_id parameter of the loop to get the next page of results.

username: the Instagram username of the user whose account the image is from

full_name: the full name provided by the user whose account the image is from

profile_picture: URL to the profile image of the user

website: the website URL provided by the user whose account the image is from

user_id: the Instagram user ID of the user whose account the image is from

error_type: a string of "NoError" to indicate a successful call to the Instagram API resulting in valid data OR a string of "NoCodeReturned" indicating there was no data returned from Instagram

error_message: a string describing the error

**Tags returned in an unsuccessful Craft loop:**

status: a BOOLEAN of FALSE (0) is returned when no data is returned from Instagram or there is no cache data to return

error_type: a single code word indicating the type of error (NoInstagramApp, MissingReqParameter, UnauthorizedUser issued by Pic Puller. Other codes are passed through from Instagram.)

error_message: a string describing the error

**Example template code:**

	{% for instagramdata in craft.picpuller.user_liked({ 'user_id' : 1,  'use_stale_cache' : true, 'limit': 20 }) %}
	    {% if loop.first %}
			<p>Status: {{ instagramdata.status }}</p>
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
			<hr>
		{% endif %}
		{% if instagramdata.status == 'true' %}
			<p>Loop Index: {{ loop.index }}</p>
			<p><img src="{{instagramdata.low_resolution}}"></p>
			<p>caption: {{instagramdata.caption}}</p>
			<p>created_time: {{ instagramdata.created_time }}</p>
		{% else %}
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
		{% endif %}
	{% endfor %}

### Media by tag

#### craft.picpuller.tagged_media

Description:
Get a list of recently tagged media. Note that this media is ordered by when the media was tagged with this tag, rather than the order it was posted.

For consistency amongst the tags used in Pic Puller, the Craft tags use ‘next_max_id’ for pagination. If you refer to the Instagram documentation, you will see references ‘max_tag_id’ is used for pagination. That does not apply to Pic Puller. Those tags are rewritten by the module to be ‘next_max_id’.

Instragram docs page for this function: [http://instagram.com/developer/endpoints/tags/#get_tags_media_recent]
(http://instagram.com/developer/endpoints/tags/#get_tags_media_recent "Instagram documentation for get_tags_media_recent")

**Required parameters:**

user_id: This is the ID number of an Craft user. (It is not the Instagram user id number.)

**Optional parameters:**

limit: an integer for how many images to request from Instagram. Instagram may return fewer under some circumstances. Maximum of 32 allowed by Instagram.

use_stale_cache: BOOLEAN, either TRUE or FALSE (defaults to TRUE if undefined), to have Pic Puller use previously cached data returned in the event of an error in retrieving new data

max_id: an integer used to determine pagination of results. (See next_max_id in the ‘Tags returned’ below section for more information.)

**Tags returned in a successful Craft loop:**

status: a BOOLEAN of TRUE (1) is returned when Instagram media data is returned, even if it is cached data

type: returns a string "image" or "video"

media_id: the Instagram unique media ID for the image or video

created_time: time stamp of image creation time, Unix timestamp formatted

link: URL of the images homepage on Instagram

caption: The caption provided by the author. Note, it may be left untitled which will return an empty string.

thumbnail: URL to image, sized 150x150

low_resolution: URL to image, sized 306x306

standard_resolution: URL to image, sized 612x612

video_low_resolution: URL to video, sized 480x480

video_standard_resolution: URL to video, sized 640x640

latitude: latitude data, if available

longitude: longitude data, if available

next_max_id: an integer, provided by Instagram, used to return the next set in the same series of images. Pass this value into the max_id parameter of the loop to get the next page of results.

user_id: the Instagram user ID of the user whose account the image is from

username: the Instagram username of the user whose account the image is from

profile_picture: URL to the profile image of the user

website: the website URL provided by the user whose account the image is from

full_name: the full name provided by the user whose account the image is from

error_type: a string of "NoError" to indicate a successful call to the Instagram API resulting in valid data OR a string of "NoCodeReturned" indicating there was no data returned from Instagram

error_message: a string describing the error

**Tags returned in an unsuccessful Craft loop:**

status: a BOOLEAN of FALSE (0) is returned when no data is returned from Instagram or there is no cache data to return

error_type: a single code word indicating the type of error (NoInstagramApp, MissingReqParameter, UnauthorizedUser issued by Pic Puller. Other codes are passed through from Instagram.)

error_message: a string describing the error

**Example template code:**

	{% for instagramdata in craft.picpuller.tagged_media({'tag': 'cats', 'user_id' : 1,  'use_stale_cache' : true, 'limit': 20}) %}
		{% if loop.first %}
			<p>Status: {{ instagramdata.status }}</p>
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
			<hr>
		{% endif %}
		{% if instagramdata.status == 'true' %}
			<p>Loop Index: {{ loop.index }}</p>
			<p><img src="{{instagramdata.low_resolution}}"></p>
			<p>caption: {{instagramdata.caption}}</p>
			<p>type: {{ instagramdata.type }}</p>
			<p>created_time: {{ instagramdata.created_time }}</p>
		{% else %}
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
		{% endif %}
	{% endfor %}

### Media by ID

#### craft.picpuller.media

Description: Get information about a single media object.

Instragram docs page for this function: [http://instagram.com/developer/endpoints/media/#get_media](http://instagram.com/developer/endpoints/media/#get_media "Instagram documenation for get_media")

**Required parameters:**

user_id: This is the ID number of an Craft user. (It is not the Instagram user id number.)

media_id: this is the ID number that Instagram has assigned to an image or video

**Optional parameters:**

use_stale_cache: BOOLEAN, either TRUE or FALSE (defaults to TRUE if undefined), to have Pic Puller use previously cached data returned in the event of an error in retrieving new data

**Tags returned in a successful Craft loop:**

status: a BOOLEAN of TRUE (1) is returned when Instagram media data is returned, even if it is cached data

created_time: time stamp of image creation time, Unix timestamp formatted

link: URL of the images homepage on Instagram

caption: The caption provided by the author. Note, it may be left untitled which will return an empty string.

thumbnail: URL to image, sized 150x150

low_resolution: URL to image, sized 306x306

standard_resolution: URL to image, sized 612x612

video_low_resolution: URL to video, sized 480x480

video_standard_resolution: URL to video, sized 640x640

latitude: latitude data, if available

longitude: longitude data, if available

username: the Instagram username of the user whose account the image is from

user_id: the Instagram user id of the user whose account the image is from

full_name: the full name provided by the user whose account the image is from

profile_picture: URL to the profile image of the user

website: the website information whose account the image is from, if available

likes: number of likes for piece of media

error_type: a string of "NoError" to indicate a successful call to the Instagram API resulting in valid data OR a string of "NoCodeReturned" indicating there was no data returned from Instagram

error_message: a string describing the error

**Tags returned in an unsuccessful Craft loop:**

status: a BOOLEAN of FALSE (0) is returned when no data is returned from Instagram or there is no cache data to return

error_type: a single code word indicating the type of error (NoInstagramApp, MissingReqParameter, UnauthorizedUser issued by Pic Puller. Other codes are passed through from Instagram.)

error_message: a string describing the error

**Example template code:**

	{% for instagramdata in craft.picpuller.media({'use_stale_cache' : TRUE, 'user_id' : 1, 'media_id': '423894109381331599_1500897'}) %}
		{% if loop.first %}
			<p>Status: {{ instagramdata.status }}</p>
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
			<hr>
		{% endif %}
		{% if instagramdata.status == 'true' %}
			<p>username: {{ instagramdata.username }}</p>
			<p>full_name: {{ instagramdata.full_name }}</p>
			<p><img src="{{instagramdata.low_resolution}}"></p>
			<p>caption: {{instagramdata.caption}}</p>
			<p>created_time: {{ instagramdata.created_time }}</p>
		{% else %}
			<p>Error Type: {{ instagramdata.error_type }}</p>
			<p>Error Message: {{ instagramdata.error_message }}</p>
		{% endif %}
	{% endfor %}
