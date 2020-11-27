<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'greetings'
 * ------------------------------------------------------------------------- */

namespace RRZE\Greetings\CPT;

defined('ABSPATH') || exit;

use RRZE\Greetings\Functions;
use RRZE\Greetings\Card\Text;
use RRZE\Greetings\Mail\Queue;
use RRZE\Greetings\Template;

use function RRZE\Greetings\plugin;

class Greeting
{
    /**
     * Custom post type
     * @var string
     */
    protected static $postType = 'greeting';

    /**
     * Category taxonomy
     * @var string
     */
    protected static $categoryTaxonomy = 'greetings_category';

    /**
     * Category taxonomy
     * @var string
     */
    protected static $mailListTaxonomy = 'greetings_mail_list';

    /**
     * Queue
     * @var object RRZE\Greetings\Mail\Queue
     */
    protected $queue;

    protected $mailList;

    protected $filterMailListIds;

    protected $filterDate;

    public function __construct()
    {
        $this->template = new Template;
    }

    public function onLoaded()
    {
        // Register CPT.
        add_action('init', [$this, 'registerPostType']);
        // Register Taxonomies.
        add_action('init', [$this, 'registerTaxonomies']);
        // CPT Custom Columns.
        add_filter('manage_greeting_posts_columns', [$this, 'columns']);
        add_action('manage_greeting_posts_custom_column', [$this, 'customColumn'], 10, 2);
        add_filter('manage_edit-greeting_sortable_columns', [$this, 'sortableColumns']);
        // CPT List Filters.
        add_filter('months_dropdown_results', [$this, 'removeMonthsDropdown'], 10, 2);
        add_action('restrict_manage_posts', [$this, 'applyFilters'], 10, 1);
        add_filter('parse_query', [$this, 'filterQuery'], 10);
        // Taxonomy Terms Fields.
        add_action('greetings_mail_list_add_form_fields', [$this, 'addFormFields']);
        add_action('greetings_mail_list_edit_form_fields', [$this, 'editFormFields'], 10, 2);
        add_action('created_greetings_mail_list', [$this, 'saveFormFields']);
        add_action('edited_greetings_mail_list', [$this, 'saveFormFields']);
        // Taxonomy Custom Columns
        add_filter('manage_edit-greetings_category_columns', [$this, 'categoryColumns']);
        add_filter('manage_edit-greetings_mail_list_columns', [$this, 'mailListColumns']);
        add_filter('manage_greetings_mail_list_custom_column', [$this, 'mailListCustomColumns'], 10, 3);
        // List Actions
        add_filter('post_row_actions', [$this, 'rowActions'], 10, 2);
        add_filter('handle_bulk_actions-edit-greeting', [$this, 'bulkActionsHandler'], 10, 3);
    }

    public function registerPostType()
    {
        $labels = [
            'name'                      => _x('Greetings', 'Post type general name', 'rrze-greetings'),
            'singular_name'             => _x('Greeting', 'Post type singular name', 'rrze-greetings'),
            'menu_name'                 => _x('Greetings', 'Admin Menu text', 'rrze-greetings'),
            'name_admin_bar'            => _x('Greeting', 'Add New on Toolbar', 'rrze-greetings'),
            'add_new'                   => __('Add New', 'rrze-greetings'),
            'add_new_item'              => __('Add New Greeting', 'rrze-greetings'),
            'new_item'                  => __('New Greeting', 'rrze-greetings'),
            'edit_item'                 => __('Edit Greeting', 'rrze-greetings'),
            'view_item'                 => __('View Greeting', 'rrze-greetings'),
            'all_items'                 => __('All Greetings', 'rrze-greetings'),
            'search_items'              => __('Search Greetings', 'rrze-greetings'),
            'featured_image'            => _x('Card Image Source', 'Overrides the “Featured Image” phrase for this post type.', 'rrze-greetings'),
            'set_featured_image'        => _x('Set the card image source', 'Overrides the “Set featured image” phrase for this post type.', 'rrze-greetings'),
            'remove_featured_image'        => _x('Remove the card image source', 'Overrides the “Remove featured image” phrase for this post type.', 'rrze-greetings'),
            'use_featured_image'        => _x('Use as source image', 'Overrides the “Use as featured image” phrase for this post type.', 'rrze-greetings'),
            'not_found'                 => __('No Greetings found.', 'rrze-greetings'),
            'not_found_in_trash'        => __('No Greetings found in Trash.', 'rrze-greetings'),
            'archives'                  => _x('Greeting archives', 'The post type archive label used in nav menus.', 'rrze-greetings'),
            'filter_items_list'         => _x('Filter Greetings list', 'Screen reader text for the filter links heading on the post type listing screen.', 'rrze-greetings'),
            'items_list_navigation'     => _x('Greetings list navigation', 'Screen reader text for the pagination heading on the post type listing screen.', 'rrze-greetings'),
            'items_list'                => _x('Greetings list', 'Screen reader text for the items list heading on the post type listing screen.', 'rrze-greetings'),
        ];

        $args = [
            'label'                     => __('Greeting', 'rrze-greetings'),
            'description'               => __('Add and edit Greetings Cards', 'rrze-greetings'),
            'labels'                    => $labels,
            'supports'                  => ['title', 'thumbnail'],
            'hierarchical'              => false,
            'public'                    => true,
            'show_ui'                   => true,
            'show_in_menu'              => true,
            'show_in_admin_bar'         => true,
            'menu_position'             => 39,
            'menu_icon'                 => 'dashicons-smiley',
            'can_export'                => false,
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

    public function registerTaxonomies()
    {
        $labels = [
            'name' => _x('Categories', 'taxonomy general name', 'rrze-greetings'),
            'singular_name' => _x('Category', 'taxonomy singular name', 'rrze-greetings'),
        ];
        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'rewrite' => self::$categoryTaxonomy,
            'capabilities' => [
                'manage_terms' => 'edit_greetings',
                'edit_terms' => 'edit_greetings',
                'delete_terms' => 'edit_greetings',
                'assign_terms' => 'edit_greetings'
            ]
        ];
        register_taxonomy(self::$categoryTaxonomy, self::$postType, $args);

        $labels = [
            'name' => _x('Mail Lists', 'taxonomy general name', 'rrze-greetings'),
            'singular_name' => _x('Mail List', 'taxonomy singular name', 'rrze-greetings'),
            'all_items' => __('All Lists', 'rrze-greetings'),
            'edit_item' => __('Edit List', 'rrze-greetings'),
            'view_item' => __('View List', 'rrze-greetings'),
            'update_item' => __('Update List', 'rrze-greetings'),
            'add_new_item' => __('Add New List', 'rrze-greetings'),
            'new_item_name' => __('New List Name', 'rrze-greetings'),
            'parent_item' => __('Main List', 'rrze-greetings'),
            'parent_item_colon' => __('Main List:', 'rrze-greetings'),
            'search_items' => __('Search Lists', 'rrze-greetings'),
            'not_found' => __('No lists found', 'rrze-greetings'),
            'back_to_items' => __('Back to lists', 'rrze-greetings'),
        ];
        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'rewrite' => self::$mailListTaxonomy,
            'capabilities' => [
                'manage_terms' => 'edit_greetings',
                'edit_terms' => 'edit_greetings',
                'delete_terms' => 'edit_greetings',
                'assign_terms' => 'edit_greetings'
            ]
        ];
        register_taxonomy(self::$mailListTaxonomy, self::$postType, $args);
    }

    public function addFormFields($taxonomy)
    {
        echo '<div class="form-field">
        <label for="greetings_mail_list">' . __('E-mail Addresses', 'rrze-greetings') . '</label>
        <textarea id="greetings_mail_list" rows="5" cols="40" name="rrze_greetings_mail_list"></textarea>
        <p>' . __('Enter one email address per line.', 'rrze-greetings') . '</p>
        </div>';
    }

    public function editFormFields($term, $taxonomy)
    {
        $value = get_term_meta($term->term_id, 'rrze_greetings_mail_list', true);

        echo '<tr class="form-field">
        <th>
            <label for="greetings_mail_list">' . __('E-mail Addresses', 'rrze-greetings') . '</label>
        </th>
        <td>
            <textarea id="greetings_mail_list" rows="5" cols="50" name="rrze_greetings_mail_list">' . esc_attr($value) . '</textarea>
            <p class="description">' . __('Enter one email address per line.', 'rrze-greetings') . '</p>
        </td>
        </tr>';
    }

    public function saveFormFields($termId)
    {
        if (!isset($_POST['rrze_greetings_mail_list'])) {
            return;
        }
        $mailList = sanitize_textarea_field($_POST['rrze_greetings_mail_list']);

        // @todo email address validation

        update_term_meta(
            $termId,
            'rrze_greetings_mail_list',
            $mailList
        );
    }

    public function categoryColumns($columns)
    {
        $columns['posts'] = __('Greetings', 'rrze-greetings');
        return $columns;
    }

    public function mailListColumns($columns)
    {
        $columns['posts'] = __('Greetings', 'rrze-greetings');
        $columns['emails'] = __('Emails', 'rrze-greetings');
        return $columns;
    }

    public function mailListCustomColumns($content, $columnName, $termId)
    {
        $term = get_term($termId, 'greetings_mail_list');
        switch ($columnName) {
            case 'emails':
                if (empty($list = (string) get_term_meta($term->term_id, 'rrze_greetings_mail_list', true))) {
                    $content = 0;
                }
                $mailList = explode(PHP_EOL, $list);
                $content = count($mailList);
                break;
            default:
                break;
        }
        return $content;
    }

    public static function getData(int $postId): array
    {
        $data = [];

        $post = get_post($postId);
        if (!$post) {
            return $data;
        }

        $data['id'] = $post->ID;

        $sendDateGmt = absint(get_post_meta($post->ID, 'rrze_greetings_send_date_gmt', true));
        $data['send_date_gmt'] = $sendDateGmt ? date('Y-m-d H:i:s', $sendDateGmt) : '';
        $sendDate = absint(get_post_meta($post->ID, 'rrze_greetings_send_date', true));
        $data['send_date'] = $sendDate ? date('Y-m-d H:i:s', $sendDate) : '';
        $data['send_date_format'] = $sendDate ? sprintf(
            __('%1$s at %2$s'),
            Functions::dateFormat(__('Y/m/d'), $sendDate),
            Functions::timeFormat(__('g:i a'), $sendDate)
        ) : '';

        $data['post_date_gmt'] = $post->post_date_gmt;
        $data['post_date'] = $post->post_date;
        $data['post_date_format'] = sprintf(
            __('%1$s at %2$s'),
            get_the_time(__('Y/m/d'), $post),
            get_the_time(__('g:i a'), $post)
        );

        $data['title'] = $post->post_title;
        $data['content'] = $post->post_content;
        $data['excerpt'] = $post->post_excerpt;

        $data['categories'] = self::getTermsList($post->ID, self::$categoryTaxonomy);
        $data['mail_lists'] = self::getTermsList($post->ID, self::$mailListTaxonomy);

        $data['status'] = get_post_meta($post->ID, 'rrze_greetings_status', true);
        $data['post_status'] = $post->post_status;

        return $data;
    }

    protected static function getTermsList($postId, $taxonomy)
    {
        $postTerms = [
            'taxonomy' => $taxonomy,
            'terms' => null,
            'links' => null
        ];
        $postType = get_post_type($postId);
        $terms = get_the_terms($postId, $taxonomy);
        $termslinks = [];
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $termslinks[] = "<a href='edit.php?post_type={$postType}&{$taxonomy}={$term->slug}'> " . esc_html(sanitize_term_field('name', $term->name, $term->term_id, $taxonomy, 'edit')) . "</a>";
            }
            $postTerms['terms'] = $terms;
            $postTerms['links'] = implode(', ', $termslinks);
        }
        return $postTerms;
    }

    public function columns($columns)
    {
        $columns = [];
        $columns['cb'] = true;
        $columns['title'] = __('Title', 'rrze-greetings');
        $columns['category'] = __('Category', 'rrze-greetings');
        $columns['mail_list'] = __('Mail List', 'rrze-greetings');
        $columns['send_date'] = __('Send Date', 'rrze-greetings');
        $columns['action'] = __('Action', 'rrze-greetings');
        return $columns;
    }

    public function sortableColumns($columns)
    {
        $columns['title'] = 'title';
        $columns['send_date'] = 'send_date';
        $columns['action'] = 'action';
        return $columns;
    }

    function customColumn($column, $postId)
    {
        $data = self::getData($postId);

        switch ($column) {
            case 'category':
                echo $data['categories']['links'] ?? '&mdash;';
                break;
            case 'mail_list':
                echo $data['mail_lists']['links'] ?? '&mdash;';
                break;
            case 'send_date':
                echo $data['send_date_format'];
                break;
            case 'action':
                $status = $data['status'];
                $publish = ($data['post_status'] == 'publish');

                if ($publish) {
                    $nonce = wp_create_nonce('rrze_greetings_action');

                    if ($status == 'send') {
                        $sendButton = '<button class="button button-secondary" disabled>' . _x('Send', 'Greeting', 'rrze-greetings') . '</button>';
                        $cancelButton = sprintf(
                            '<a href="edit.php?post_type=%s&id=%d&rrze_greetings_action=cancel&nonce=%s" class="button button-secondary" data-id="%1$d">%s</a>',
                            self::$postType,
                            $data['id'],
                            $nonce,
                            _x('Cancel', 'Greeting', 'rrze-greetings')
                        );
                        $button = $sendButton . $cancelButton;
                    } elseif ($status == 'queued') {
                        $button = '<button class="button button-primary" disabled>' . _x('Queued', 'Greeting', 'rrze-greetings') . '</button>';
                    } elseif ($status == 'sent') {
                        $button = '<button class="button button-primary" disabled>' . _x('Sent', 'Greeting', 'rrze-greetings') . '</button>';
                    } else {
                        $button = sprintf(
                            '<a href="edit.php?post_type=%s&id=%d&rrze_greetings_action=send&nonce=%s" class="button button-primary" data-id="%1$d">%s</a>',
                            self::$postType,
                            $data['id'],
                            $nonce,
                            _x('Send', 'Greeting', 'rrze-greetings')
                        );
                    }
                    echo $button;
                } else {
                    echo '&mdash;';
                }
                break;
            default:
                echo '&mdash;';
        }
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
        if ($post->post_type != 'greeting' || $post->post_status != 'publish') {
            return $actions;
        }

        $actions = [];
        $title = _draft_or_post_title();
        $status = get_post_meta($post->ID, 'rrze_greetings_status', true);
        $isQueued = in_array($status, ['send', 'queued']);
        $canEdit = current_user_can('edit_post', $post->ID);
        $canDelete = current_user_can('delete_post', $post->ID);

        if (!$isQueued && $canEdit) {
            $actions['edit'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                get_edit_post_link($post->ID),
                /* translators: %s: Post title. */
                esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $title)),
                __('Edit')
            );
        }

        if (!$isQueued && $canDelete) {
            if (EMPTY_TRASH_DAYS) {
                $actions['trash'] = sprintf(
                    '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                    get_delete_post_link($post->ID),
                    /* translators: %s: Post title. */
                    esc_attr(sprintf(__('Move &#8220;%s&#8221; to the Trash'), $title)),
                    _x('Delete', 'Booking', 'rrze-rsvp')
                );
            } else {
                $actions['delete'] = sprintf(
                    '<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
                    get_delete_post_link($post->ID, '', true),
                    /* translators: %s: Post title. */
                    esc_attr(sprintf(__('Delete &#8220;%s&#8221; permanently'), $title)),
                    __('Delete Permanently')
                );
            }
        }

        $actions['view'] = sprintf(
            '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
            get_permalink($post->ID),
            /* translators: %s: Post title. */
            esc_attr(sprintf(__('View &#8220;%s&#8221;'), $title)),
            __('View')
        );

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
        // @todo
    }

    public function filterQuery($query)
    {
        // @todo
    }
}
