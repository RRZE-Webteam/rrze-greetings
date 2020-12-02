<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

class Update
{
    /**
     * The option name.
     * @var string
     */
    protected $versionOptionName = 'rrze_greetings_update_version';

    /**
     * The option value.
     * @var string
     */
    protected $version;

    /**
     * Constructor method.
     */
    public function __construct()
    {
        $this->version = get_option($this->versionOptionName, plugin()->getVersion());
    }

    /**
     * Compare & update the update version.
     * @return void
     */
    public function updateVersion()
    {
        // Compares two "PHP-standardized" version number strings
        if (version_compare($this->version, '1.0.0', '<')) {
            $this->updateTo100();
            update_option($this->versionOptionName, plugin()->getVersion());
        }
    }

    /**
     * Update if version is lower than 1.0.0.
     * @return void
     */
    protected function updateTo100()
    {
        // @todo Something
    }
}
