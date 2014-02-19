<?php

namespace Craft;

/**
 * PicPuller_OauthRecord
 */
class PicPuller_OauthRecord extends BaseRecord
{

		/**
     * Gets the database table name
     *
     * @return string
     */
    public function getTableName() {
		return 'picpuller_oauths';
	}

	/**
     * Define columns for our database table
     *
     * @return array
     */
    protected function defineAttributes() {
		$attributes = array(
            'app_id'    => AttributeType::Number,
			'member_id'    => array( AttributeType::String, 'required' => true ),
			'instagram_id' => array( AttributeType::String, 'required' => true ),
			'oauth' => array( AttributeType::String, 'required' => true ),
		);

		return $attributes;
	}

	/**
     * Create a new instance of the current class. This allows us to
     * properly unit test our service layer.
     *
     * @return BaseRecord
     */
    public function create()
    {
        $class = get_class($this);
        $record = new $class();

        return $record;
    }
}
