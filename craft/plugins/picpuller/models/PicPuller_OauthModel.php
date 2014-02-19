<?php
namespace Craft;

class PicPuller_OauthModel extends BaseModel
{

    /**
     * Define the attributes this model will have.
     *
     * @return array
     */
    protected function defineAttributes() {

        $attributes = array(
            'id'    => AttributeType::Number,
            'app_id'    => AttributeType::Number,
            'member_id'    => array( AttributeType::String, 'required' => true ),
            'instagram_id' => array( AttributeType::String, 'required' => true ),
            'oauth' => array( AttributeType::String, 'required' => true ),
        );

        return $attributes;
    }
}
