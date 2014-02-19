<?php

namespace Craft;

/**
 * PicPuller_CredentialsRecord
 */
class PicPuller_CredentialsRecord extends BaseRecord
{

	/**
     * Gets the database table name
     *
     * @return string
     */
	public function getTableName() {
		return 'picpuller_credentials';
	}

	/**
     * Define columns for our database table
     *
     * @return array
     */
	protected function defineAttributes() {
		$attributes = array(
			'clientId' => array( AttributeType::String, 'required' => true ),
			'clientSecret' => array( AttributeType::String, 'required' => true )
		);

		return $attributes;
	}

    /**
     * Define columns for our database table
     *
     * @return array
     */
    public function defineRelations()
    {
        return array(
            'id' => array(static::HAS_MANY, 'PicPuller_OauthRecord', 'app_id'),
        );
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
