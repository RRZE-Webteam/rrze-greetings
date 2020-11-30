<?php

namespace RRZE\Greetings\CPT;

defined('ABSPATH') || exit;

class CPT
{
    public function __construct()
    {
        //
    }

    public function onLoaded()
    {
        $greeting = new Greeting;
        $greeting->onLoaded();

        $greetingQueue = new GreetingQueue;
        $greetingQueue->onLoaded();     

        // CMB2 Metaboxes
        $metaboxes = new Metaboxes;
        $metaboxes->onLoaded(); 
                
        // Add Menu
        add_action('admin_menu', [$this, 'addMenu']);
        add_filter('parent_file', [$this, 'filterParentMenu']);
    }

    public function activation()
    {
        $greeting = new Greeting;
        $greeting->registerPostType();
        $greeting->registerTaxonomies();

        $greetingQueue = new GreetingQueue;
        $greetingQueue->registerPostType();
    }

    public function addMenu()
    {
        remove_submenu_page('edit.php?post_type=greeting', 'edit.php?post_type=greeting');
        remove_submenu_page('edit.php?post_type=greeting', 'post-new.php?post_type=greeting');
        remove_submenu_page('edit.php?post_type=greeting', 'edit-tags.php?taxonomy=greetings_category&amp;post_type=greeting');
        remove_submenu_page('edit.php?post_type=greeting', 'edit-tags.php?taxonomy=greetings_mailing_list&amp;post_type=greeting');

        $cpts = self::getAllCPT();
        $hiddenMenu = 'rrze-greetings-submenu-hidden';

        foreach ($cpts as $cpt) {
            $cpt_obj = get_post_type_object($cpt);
            add_submenu_page(
                'edit.php?post_type=greeting',     // parent slug
                $cpt_obj->labels->name,            // page title
                $cpt_obj->labels->menu_name,       // menu title
                $cpt_obj->cap->edit_posts,         // capability
                'edit.php?post_type=' . $cpt       // menu slug
            );

            add_submenu_page(
                'edit.php?post_type=greeting',
                $cpt_obj->labels->name,
                $hiddenMenu,
                $cpt_obj->cap->edit_posts,
                'post-new.php?post_type=' . $cpt
            );
        }

        add_submenu_page(
            'edit.php?post_type=greeting',
            __('Categories', 'rrze-greetings'),
            __('Categories', 'rrze-greetings'),
            'edit_greetings',
            'edit-tags.php?taxonomy=greetings_category&amp;post_type=greeting'
        );

        add_submenu_page(
            'edit.php?post_type=greeting',
            __('Mailing Lists', 'rrze-greetings'),
            __('Mailing Lists', 'rrze-greetings'),
            'edit_greetings',
            'edit-tags.php?taxonomy=greetings_mailing_list&amp;post_type=greeting'
        );

        global $submenu;
        $hiddenClass = $hiddenMenu;
        if (isset($submenu['edit.php?post_type=greeting'])) {
            foreach ($submenu['edit.php?post_type=greeting'] as $key => $menu) {
                if ($menu[0] == $hiddenMenu) {
                    $submenu['edit.php?post_type=greeting'][$key][4] = $hiddenClass;
                }
            }
        }
    }

    public function filterParentMenu($parent_file)
    {
        global $submenu_file, $current_screen, $pagenow;

        $cpts = self::getAllCPT();

        foreach ($cpts as $cpt) {
            if ($current_screen->post_type == $cpt) {
                if ($pagenow == 'post.php') {
                    $submenu_file = 'edit.php?post_type=' . $current_screen->post_type;
                }

                if ($pagenow == 'post-new.php') {
                    $submenu_file = 'edit.php?post_type=' . $current_screen->post_type;
                }

                $parent_file = 'edit.php?post_type=greeting';
            }
        }

        return $parent_file;
    }

    public static function getAllCPT()
    {
        return array_keys(Capabilities::getCurrentCptArgs());
    }
}
