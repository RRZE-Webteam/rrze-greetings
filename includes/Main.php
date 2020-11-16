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
        // Settings 
        $settings = new Settings;
        $settings->onLoaded();

        // Posttypes 
        $this->cpt = new CPT;
        $this->cpt->onLoaded();

        add_action('admin_init', [$this, 'adminInit']);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    public function adminInit()
    {
        global $post_type;

        if (in_array($post_type, $this->cpt->getAllCPT())) {
            require_once plugin()->getPath('vendor/cmb2') . 'init.php';
        }
    }

    public function adminEnqueueScripts($hook)
    {
        global $post_type;

        wp_enqueue_style(
            'rrze-greetings-admin-menu',
            plugins_url('assets/css/admin-menu.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );

        if (!in_array($post_type, $this->cpt->getAllCPT())) {
            return;
        }

        wp_enqueue_style(
            'rrze-greetings-admin',
            plugins_url('assets/css/admin.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );
    }
}
