<?php

/*
Plugin Name:      RRZE Greetings
Plugin URI:       https://github.com/RRZE-Webteam/rrze-greetings
Description:      Plugin for creating and sending HTML greeting cards.
Version:          1.0.0-alpha-8
Author:           RRZE-Webteam
Author URI:       https://blogs.fau.de/webworking/
License:          GNU General Public License v2
License URI:      http://www.gnu.org/licenses/gpl-2.0.html
Font License:     Open Font License
Font License URI: https://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=OFL
Domain Path:      /languages
Text Domain:      rrze-greetings
*/

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

use RRZE\Greetings\CPT\CPT;

const RRZE_PHP_VERSION = '7.4';
const RRZE_WP_VERSION = '5.5';

// Load the settings config file.
require_once 'config/settings.php';

// Autoloading of classes.
require 'autoload.php';
//require 'vendor/autoload.php';

register_activation_hook(__FILE__, __NAMESPACE__ . '\activation');
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivation');

add_action('plugins_loaded', __NAMESPACE__ . '\loaded');

/**
 * loadTextdomain
 */
function loadTextdomain()
{
    load_plugin_textdomain('rrze-greetings', false, sprintf('%s/languages/', dirname(plugin_basename(__FILE__))));
}

/**
 * systemRequirements
 * @return string Return an error message.
 */
function systemRequirements(): string
{
    loadTextdomain();

    $error = '';
    if (version_compare(PHP_VERSION, RRZE_PHP_VERSION, '<')) {
        $error = sprintf(__('The server is running PHP version %s. The Plugin requires at least PHP version %s.', 'rrze-greetings'), PHP_VERSION, RRZE_PHP_VERSION);
    } elseif (version_compare($GLOBALS['wp_version'], RRZE_WP_VERSION, '<')) {
        $error = sprintf(__('The server is running WordPress version %s. The Plugin requires at least WordPress version %s.', 'rrze-greetings'), $GLOBALS['wp_version'], RRZE_WP_VERSION);
    }
    return $error;
}

/**
 * activation
 */
function activation()
{
    if ($error = systemRequirements()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(sprintf(__('Plugins: %s: %s', 'rrze-log'), plugin_basename(__FILE__), $error));
    }

    Roles::addRoleCaps();
    Roles::createRoles();

    $cpt = new CPT;
    $cpt->activation();

    flush_rewrite_rules();
}

/**
 * deactivation
 */
function deactivation()
{
    Roles::removeRoleCaps();
    Roles::removeRoles();

    Cron::clearSchedule();

    flush_rewrite_rules();
}

/**
 * plugin
 * @return object
 */
function plugin(): object
{
    static $instance;
    if (null === $instance) {
        $instance = new Plugin(__FILE__);
    }
    return $instance;
}

/**
 * loaded
 * @return void
 */
function loaded()
{
    add_action('init', __NAMESPACE__ . '\loadTextdomain');
    plugin()->onLoaded();

    if ($error = systemRequirements()) {
        add_action('admin_init', function () use ($error) {
            if (current_user_can('activate_plugins')) {
                $pluginData = get_plugin_data(plugin()->getFile());
                $pluginName = $pluginData['Name'];
                $tag = is_plugin_active_for_network(plugin()->getBaseName()) ? 'network_admin_notices' : 'admin_notices';
                add_action($tag, function () use ($pluginName, $error) {
                    printf(
                        '<div class="notice notice-error"><p>' . __('Plugins: %s: %s', 'rrze-greetings') . '</p></div>',
                        esc_html($pluginName),
                        esc_html($error)
                    );
                });
            }
        });
        return;
    }

    $main = new Main;
    $main->onLoaded();
}
