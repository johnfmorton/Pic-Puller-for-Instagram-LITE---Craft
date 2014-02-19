<?php
namespace Craft;

class PicPuller_CredentialsModel extends BaseModel
{

    /**
     * Define the attributes this model will have.
     *
     * @return array
     */
    public function defineAttributes() {
        $attributes = array(
            'id'    => AttributeType::Number,
            'clientId' => array( AttributeType::String, 'required' => true ),
            'clientSecret' => array( AttributeType::String, 'required' => true )
        );

        return $attributes;
    }
}
