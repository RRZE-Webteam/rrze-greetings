<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'greeting_template'
 * ------------------------------------------------------------------------- */

namespace RRZE\Greetings\CPT;

defined('ABSPATH') || exit;

class GreetingTemplate
{
    /**
     * Custom post type
     * @var string
     */
    protected static $postType = 'greeting_template';

    protected $filterDate;

    public function __construct()
    {
        //
    }

    public function onLoaded()
    {
        // Register CPT.
        add_action('init', [$this, 'registerPostType']);
        // CPT Custom Columns.
        add_filter('manage_greeting_template_posts_columns', [$this, 'columns']);
        add_action('manage_greeting_template_posts_custom_column', [$this, 'customColumn'], 10, 2);
        add_filter('manage_edit-greeting_template_sortable_columns', [$this, 'sortableColumns']);
        // CPT List Filters.
        add_filter('months_dropdown_results', [$this, 'removeMonthsDropdown'], 10, 2);
        //add_action('restrict_manage_posts', [$this, 'applyFilters'], 10, 1);
        //add_filter('parse_query', [$this, 'filterQuery'], 10);
        // List Actions
        //add_filter('post_row_actions', [$this, 'rowActions'], 10, 2);
        //add_filter('handle_bulk_actions-edit-greeting_template', [$this, 'bulkActionsHandler'], 10, 3);
        // Custom Post Links
        add_filter('preview_post_link', [$this, 'previewLink'], 10, 2);
        add_filter('post_type_link', [$this, 'postLink'], 10, 2);
        // Save Template
        add_action('save_post', [$this, 'saveTemplate'], 99, 2);
    }

    public function registerPostType()
    {
        $labels = [
            'name'                      => _x('Templates', 'Post type general name', 'rrze-greetings'),
            'singular_name'             => _x('Template', 'Post type singular name', 'rrze-greetings'),
            'menu_name'                 => _x('Templates', 'Admin Menu text', 'rrze-greetings'),
            'name_admin_bar'            => _x('Template', 'Add New on Toolbar', 'rrze-greetings'),
            'add_new'                   => __('Add New', 'rrze-greetings'),
            'add_new_item'              => __('Add New Template', 'rrze-greetings'),
            'new_item'                  => __('New Template', 'rrze-greetings'),
            'edit_item'                 => __('Edit Template', 'rrze-greetings'),
            'view_item'                 => __('View Template', 'rrze-greetings'),
            'all_items'                 => __('All Templates', 'rrze-greetings'),
            'search_items'              => __('Search Templates', 'rrze-greetings'),
            'featured_image'            => _x('Card Image Source', 'Overrides the “Featured Image” phrase for this post type.', 'rrze-greetings'),
            'set_featured_image'        => _x('Set the card image source', 'Overrides the “Set featured image” phrase for this post type.', 'rrze-greetings'),
            'remove_featured_image'     => _x('Remove the card image source', 'Overrides the “Remove featured image” phrase for this post type.', 'rrze-greetings'),
            'use_featured_image'        => _x('Use as source image', 'Overrides the “Use as featured image” phrase for this post type.', 'rrze-greetings'),
            'not_found'                 => __('No Templates found.', 'rrze-greetings'),
            'not_found_in_trash'        => __('No Templates found in Trash.', 'rrze-greetings'),
            'archives'                  => _x('Template archives', 'The post type archive label used in nav menus.', 'rrze-greetings'),
            'filter_items_list'         => _x('Filter Templates list', 'Screen reader text for the filter links heading on the post type listing screen.', 'rrze-greetings'),
            'items_list_navigation'     => _x('Templates list navigation', 'Screen reader text for the pagination heading on the post type listing screen.', 'rrze-greetings'),
            'items_list'                => _x('Templates list', 'Screen reader text for the items list heading on the post type listing screen.', 'rrze-greetings'),
        ];

        $args = [
            'label'                     => __('Template', 'rrze-greetings'),
            'description'               => __('Add and edit Templates', 'rrze-greetings'),
            'labels'                    => $labels,
            'supports'                  => ['title'],
            'hierarchical'              => false,
            'public'                    => false,
            'show_ui'                   => true,
            'show_in_menu'              => false,
            'show_in_nav_menus'         => false,
            'show_in_admin_bar'         => false,
            'can_export'                => true,
            'has_archive'               => false,
            'exclude_from_search'       => true,
            'publicly_queryable'        => true,
            'delete_with_user'          => false,
            'show_in_rest'              => false,
            'capability_type'           => Capabilities::getCptCapabilityType(self::$postType),
            'capabilities'              => (array) Capabilities::getCptCaps(self::$postType),
            'map_meta_cap'              => Capabilities::getCptMapMetaCap(self::$postType)
        ];

        register_post_type(self::$postType, $args);
    }

    public static function getPostType()
    {
        return self::$postType;
    }

    public function columns($columns)
    {
        // @todo Something
        return $columns;
    }

    public function sortableColumns($columns)
    {
        // @todo Something
        return $columns;
    }

    public function customColumn($column, $postId)
    {
        // @todo Something
    }

    /**
     * Filters the array of row action links on the Greetings list table.
     * The filter is evaluated only for non-hierarchical post types.
     * @param array $actions An array of row action links.
     * @param object $post \WP_Post The post object.
     * @return array $actions
     */
    public function rowActions(array $actions, \WP_Post $post): array
    {
        if ($post->post_type != self::$postType || $post->post_status != 'publish') {
            return $actions;
        }

        // @todo Something

        return $actions;
    }

    public function bulkActionsHandler($redirectTo, $doaction, $postIds)
    {
        switch ($doaction) {
            case 'edit':
                foreach ((array) $postIds as $key => $postId) {
                    $post = get_post($postId);
                    $status = get_post_meta($postId, 'rrze_greetings_status', true);
                    $isQueued = in_array($status, ['send', 'queued']);
                    if ($post->post_status == 'publish' && $isQueued) {
                        unset($postIds[$key]);
                        continue;
                    }
                }
                break;
            case 'trash':
                foreach ((array) $postIds as $key => $postId) {
                    $post = get_post($postId);
                    $status = get_post_meta($postId, 'rrze_greetings_status', true);
                    $isQueued = in_array($status, ['send', 'queued']);
                    if ($post->post_status == 'publish' && $isQueued) {
                        unset($postIds[$key]);
                        continue;
                    }
                }
                break;
            default:
                //
        }
        return $redirectTo;
    }

    public function removeMonthsDropdown($months, $postType)
    {
        if ($postType == self::$postType) {
            $months = [];
        }
        return $months;
    }

    public function applyFilters($postType)
    {
        // @todo Something
    }

    public function filterQuery($query)
    {
        // @todo Something
    }

    public function previewLink(string $url, \WP_Post $post): string
    {
        if ($post->post_type == self::$postType) {
            $url = self::getPreviewUrl($post->ID);
        }
        return $url;
    }

    public function postLink(string $url, \WP_Post $post): string
    {
        if ($post->post_type == self::$postType) {
            return self::getPostUrl($post->ID);
        }
        return $url;
    }

    public static function getPreviewUrl($postId)
    {
        return sprintf(
            '/greeting-template/?id=%d&nonce=%s',
            $postId,
            wp_create_nonce('greeting-template-preview')
        );
    }

    public static function getPostUrl($postId)
    {
        return sprintf(
            '/greeting-template/%d',
            $postId
        );
    }

    public function saveTemplate($postId, $post)
    {
        if ($post->post_type != self::$postType || wp_is_post_revision($postId)) {
            return;
        }
        $content = get_post_meta($postId, 'rrze_greetings_template_post_content', true);
        $excerpt = get_post_meta($postId, 'rrze_greetings_template_post_excerpt', true);

        remove_action('save_post', [$this, 'saveTemplate'], 99);
        wp_update_post([
            'ID' => $postId,
            'post_content' => $content,
            'post_excerpt' => $excerpt
        ]);
        add_action('save_post', [$this, 'saveTemplate'], 99, 2);
    }

    public static function getTemplates()
    {
        $args = [
            'post_type'         => [self::$postType],
            'post_status'       => 'publish',
            'nopaging'          => true,
            'order'             => 'ASC',
            'orderby'           => 'title'
        ];

        return get_posts($args);        
    }
}
