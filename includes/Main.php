<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

use RRZE\Greetings\CPT\CPT;

class Main
{
    /**
     * RRZE\Greetings\CPT
     * @var object
     */
    protected $cpt;

    /**
     * __construct
     */
    public function __construct()
    {
        //
    }

    public function onLoaded()
    {
        // Update
        $update = new Update();
        $update->onLoaded();

        // Settings 
        $settings = new Settings;
        $settings->onLoaded();

        // Posttypes 
        $this->cpt = new CPT;
        $this->cpt->onLoaded();

        // Virtual Page        
        $virtualPage = new VirtualPage(__('Greeting Card', 'rrze-greetings'), 'greeting-card');
        $virtualPage->onLoaded();

        // Actions
        $actions = new Actions;
        $actions->onLoaded();

        // Cron
        $actions = new Cron;
        $actions->onLoaded();

        // Media Library
        new Media;

        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    public function adminEnqueueScripts($hook)
    {
        global $post_type;

        wp_enqueue_style(
            'rrze-greetings-admin',
            plugins_url('assets/css/rrze-greetings-admin.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );

        if (!in_array($post_type, $this->cpt->getAllCPT())) {
            return;
        }
    }
}
