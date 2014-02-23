<?php
namespace Craft;

class PicPullerPlugin extends BasePlugin
{
    function getName() {
        return Craft::t( 'Pic Puller for Instagram' );
    }

    function getVersion() {
        return '1.1';
    }

    function getDeveloper() {
        return 'John F Morton';
    }

    function getDeveloperUrl() {
        return 'http://craft.picpuller.com';
    }

    /**
     * Has CP Section
     */
    public function hasCpSection() {
        return true;
    }

    /**
     * Define default setting for Pic Puller
     * @return Array The settings.
     */
    protected function defineSettings() {
        $params = '';
        return array(
            'pp_settings' => array( AttributeType::Mixed, 'default' => array(  ) ),
        );

    }

    public function prepSettings( $settings ) {
        // Modify $settings here...

        return $settings;
    }

    public function getSettingsHtml() {
        return craft()->templates->render( 'picpuller/settings/settings', array(
                'settings' => $this->getSettings()
            ) );
    }

    /**
     * Add default ingredients after plugin is installed
     */
    public function onAfterInstall() {

    }
}
