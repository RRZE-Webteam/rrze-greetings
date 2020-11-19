<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type self::$postType
 * ------------------------------------------------------------------------- */

namespace RRZE\Greetings\CPT;

defined('ABSPATH') || exit;

use RRZE\Greetings\Functions;
use RRZE\Greetings\Mail\Queue;
use RRZE\Greetings\Card\Text;
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
        //
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
        // Fires once a post has been saved.
        add_action('save_post_greeting', [$this, 'saveQueue'], 10, 3);
        // Metaboxes
        add_action('cmb2_admin_init', [$this, 'cmb2Metaboxes']);
        add_action('add_meta_boxes', [$this, 'renderImage']);
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
            'featured_image'            => _x('Source Image', 'Overrides the “Featured Image” phrase for this post type.', 'rrze-greetings'),
            'set_featured_image'        => _x('Set Source Image', 'Overrides the “Set featured image” phrase for this post type.', 'rrze-greetings'),
            'remove_featured_image'        => _x('Remove Source Image', 'Overrides the “Remove featured image” phrase for this post type.', 'rrze-greetings'),
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
            'description'               => __('Add and edit Greeting data', 'rrze-greetings'),
            'labels'                    => $labels,
            'supports'                  => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
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
            'publicly_queryable'        => false,
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

    public function cmb2Metaboxes()
    {
        // Mail
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_mail',
            'title' => __('Mail Settings', 'rrze-greetings'),
            'object_types' => [self::$postType],
            'context' => 'normal',
            'priority' => 'low',
            'show_names' => true,
        ]);

        $cmb->add_field([
            'name' => __('Send Date/Time', 'rrze-greetings'),
            'id' => 'rrze_greetings_send_date',
            'type' => 'text_datetime_timestamp',
            'date_format' => __('d-m-Y', 'rrze-greetings'),
            'time_format' => __('H:i', 'rrze-greetings'),
            'attributes' => [
                'data-timepicker' => json_encode(
                    [
                        'timeFormat' => 'HH:mm',
                        'stepMinute' => 10,
                    ]
                ),
                'required' => 'required',
            ],
        ]);

        $cmb->add_field([
            'name' => __('From Name', 'rrze-greetings'),
            'id' => 'rrze_greetings_from_name',
            'type' => 'text_medium',
            'attributes' =>  [
                'required' => 'required',
            ],
        ]);

        $cmb->add_field([
            'name' => __('From Email Address', 'rrze-greetings'),
            'id' => 'rrze_greetings_from_email_address',
            'type' => 'text_email',
            'attributes' =>  [
                'required' => 'required',
            ],
        ]);

        $postId = null;
        if (isset($_GET['post'])) {
            $postId = $_GET['post'];
        } elseif (isset($_POST['post_ID'])) {
            $postId = $_POST['post_ID'];
        }

        if (!$postId || !has_post_thumbnail($postId)) {
            return;
        }

        // Image text settings
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_imagetext',
            'title' => __('Image Text Settings', 'rrze-greetings'),
            'object_types' => [self::$postType],
            'context' => 'normal',
            'priority' => 'low',
            'show_names' => true,
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_width',
            'name' => __('Line width', 'rrze-greetings'),
            'desc' => __('Number of characters per line.', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '80',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint',
            'escape_cb' => 'absint',
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_startx',
            'name' => __('X coordinate offset', 'rrze-greetings'),
            'desc' => __('X coordinate offset from which text will be positioned relative to the image.', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '0',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint',
            'escape_cb' => 'absint',
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_starty',
            'name' => __('Y coordinate offset', 'rrze-greetings'),
            'desc' => __('Y coordinate offset from which text will be positioned relative to the image.', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '0',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint',
            'escape_cb' => 'absint',
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_align',
            'name' => __('Text alignment', 'rrze-greetings'),
            'type' => 'radio',
            'default' => 'left',
            'options' => [
                'left' => __('Left', 'rrze-greetings'),
                'center' => __('Center', 'rrze-greetings'),
                'right' => __('Right', 'rrze-greetings'),
            ],
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_color',
            'name' => __('Font color', 'rrze-greetings'),
            'type' => 'colorpicker',
            'default' => '#000000',
        ]);

        $fonts = Functions::getFiles(plugin()->getPath('assets/fonts'), ['ttf', 'otf'], 'fonts');
        asort($fonts);
        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_font',
            'name' => __('Text font', 'rrze-greetings'),
            'desc' => __('Select a font', 'rrze-greetings'),
            'type' => 'select',
            'default' => 'fonts/Roboto/Roboto-LightItalic.ttf',
            'options' => $fonts,
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_lineheight',
            'name' => __('Text line height', 'rrze-greetings'),
            'desc' => __('Text line height (pts).', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '24',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint',
            'escape_cb' => 'absint',
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_size',
            'name' => __('Text size', 'rrze-greetings'),
            'desc' => __('Text size (pts).', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '16',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint',
            'escape_cb' => 'absint',
        ]);
    }

    public function addFormFields($taxonomy)
    {
        echo '<div class="form-field">
        <label for="greetings_mail_list">' . __('Mail List', 'rrze-greetings') . '</label>
        <textarea id="greetings_mail_list" rows="5" cols="40" name="rrze_greetings_mail_list"></textarea>
        <p>' . __('Enter one email address per line.', 'rrze-greetings') . '</p>
        </div>';
    }

    public function editFormFields($term, $taxonomy)
    {
        $value = get_term_meta($term->term_id, 'rrze_greetings_mail_list', true);

        echo '<tr class="form-field">
        <th>
            <label for="greetings_mail_list">' . __('Mail List', 'rrze-greetings') . '</label>
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

    public static function getData(int $postId): array
    {
        $data = [];

        $post = get_post($postId);
        if (!$post) {
            return $data;
        }

        $data['id'] = $post->ID;

        $sendDateGmt = absint(get_post_meta($post->ID, 'rrze_greetings_send_date_gmt', true));
        $data['send_date_gmt'] = date('Y-m-d H:i:s', $sendDateGmt);
        $sendDate = absint(get_post_meta($post->ID, 'rrze_greetings_send_date', true));
        $data['send_date'] = date('Y-m-d H:i:s', $sendDate);
        $data['send_date_format'] = sprintf(
            __('%1$s at %2$s'),
            Functions::dateFormat(__('Y/m/d'), $sendDate),
            Functions::timeFormat(__('g:i a'), $sendDate)
        );

        $data['post_date_gmt'] = $post->post_date_gmt;
        $data['post_date'] = $post->post_date;
        $data['post_date_format'] = sprintf(
            __('%1$s at %2$s'),
            get_the_time(__('Y/m/d'), $post),
            get_the_time(__('g:i a'), $post)
        );

        $data['categories'] = self::getTermsList($post->ID, self::$categoryTaxonomy);
        $data['mail_lists'] = self::getTermsList($post->ID, self::$mailListTaxonomy);

        $data['status'] = get_post_meta($post->ID, 'rrze_greeting_status', true);
        $data['post_status'] = $post->post_status;

        return $data;
    }

    protected static function getTermsList($postId, $taxonomy)
    {
        $postTerms = [];
        $postType = get_post_type($postId);
        $terms = get_the_terms($postId, $taxonomy);
        $termslist = '';
        if (!empty($terms)) {
            foreach ($terms as $term) {
                $postTerms[] = "<a href='edit.php?post_type={$postType}&{$taxonomy}={$term->slug}'> " . esc_html(sanitize_term_field('name', $term->name, $term->term_id, $taxonomy, 'edit')) . "</a>";
            }
            $termslist = implode(', ', $postTerms);
        }
        return $termslist ? $termslist : '&mdash;';
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
                echo $data['categories'];
                break;
            case 'mail_list':
                echo $data['mail_lists'];
                break;
            case 'send_date':
                echo $data['send_date_format'];
                break;
            case 'action':
                $status = $data['status'];
                $publish = ($data['post_status'] == 'publish');

                if ($publish) {
                    $_wpnonce = wp_create_nonce('action');

                    if ($status == 'cancelled') {
                        $cancelledButton = '<button class="button button-secondary" disabled>' . _x('Cancelled', 'Greeting', 'rrze-greetings') . '</button>';
                        $restoreButton = sprintf(
                            '<a href="edit.php?post_type=%1$s&action=restore&id=%2$d&_wpnonce=%3$s" class="button">%4$s</a>',
                            self::$postType,
                            $data['id'],
                            $_wpnonce,
                            _x('Restore', 'Greeting', 'rrze-greetings')
                        );
                        $button = $cancelledButton . $restoreButton;
                    } else {
                        $cancelButton = sprintf(
                            '<a href="edit.php?post_type=%1$s&action=cancel&id=%2$d&_wpnonce=%3$s" class="button button-secondary" data-id="%2$d">%4$s</a>',
                            self::$postType,
                            $data['id'],
                            $_wpnonce,
                            _x('Cancel', 'Greeting', 'rrze-greetings')
                        );
                        if ($status == 'sent') {
                            $button = $cancelButton . '<button class="button button-primary" disabled>' . _x('Sent', 'Greeting', 'rrze-greetings') . '</button>';
                        } else {
                            $button = $cancelButton . sprintf(
                                '<a href="edit.php?post_type=%1$s&action=confirm&id=%2$d&_wpnonce=%3$s" class="button button-primary" data-id="%2$d">%4$s</a>',
                                self::$postType,
                                $data['id'],
                                $_wpnonce,
                                _x('Send', 'Greeting', 'rrze-greetings')
                            );
                        }
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

    public function renderImage()
    {
        $screen = get_current_screen();
        if (!$screen->base == 'post' || $screen->post_type != self::$postType) {
            return;
        }

        global $post;
        $postId = $post->ID;
        if (!has_post_thumbnail($postId)) {
            return;
        }

        $cardId = absint(get_post_meta($postId, 'rrze_greetings_card_id', true));
        wp_delete_attachment($cardId, true);

        $sourceUrl = wp_get_attachment_image_url(get_post_thumbnail_id(), 'full');
        $uploads = wp_upload_dir();
        $source = str_replace($uploads['baseurl'], $uploads['basedir'], $sourceUrl);
        $sourceExt = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        $cardId = $this->uploadImage($sourceUrl, $postId, $sourceExt);
        if (is_wp_error($cardId)) {
            return;
        }

        update_post_meta($postId, 'rrze_greetings_card_id', $cardId);

        $targetUrl = wp_get_attachment_image_url($cardId, 'full');
        $uploads = wp_upload_dir();
        $target = str_replace($uploads['baseurl'], $uploads['basedir'], $targetUrl);
        $targetExt = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $target = rtrim($target, '.' . $targetExt);

        $font = get_post_meta($postId, 'rrze_greetings_imagetext_font', true);
        $color = Functions::hexToRgb(get_post_meta($postId, 'rrze_greetings_imagetext_color', true));
        $atts = [
            'width' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_width', true)),
            'startX' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_startx', true)),
            'startY' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_starty', true)),
            'align' => get_post_meta($postId, 'rrze_greetings_imagetext_align', true),
            'font' => plugin()->getPath('assets') . $font,
            'lineHeight' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_lineheight', true)),
            'size' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_size', true)),
            'color' => $color
        ];

        $text = new Text($source, $target, $post->post_excerpt, $atts);
        $text->renderToImage();
        // Add metabox to display rendered image
        add_meta_box(
            'rrze_greetings_greetings_image',
            __('Processed Image', 'rrze-greetings'),
            [$this, 'displayRenderedImage'],
            self::$postType,
            'advanced',
            'high',
            [$targetUrl]
        );
    }

    public function displayRenderedImage($post, $callbackArgs)
    {
        $imageUrl = $callbackArgs['args'][0];
        echo '<img src="' . $imageUrl . '">';
    }

    protected function uploadImage(string $url, int $postId, string $ext)
    {
        $attachmentId = 0;
        $file = [];
        $file['name'] = 'greetings-card-' . $postId . '.' . $ext;
        $file['tmp_name'] = download_url($url);

        if (is_wp_error($file['tmp_name'])) {
            @unlink($file['tmp_name']);
            return $file['tmp_name'];
        } else {
            $attachmentId = media_handle_sideload($file, $postId);
            if (is_wp_error($attachmentId)) {
                @unlink($file['tmp_name']);
                return $attachmentId;
            }
        }
        return $attachmentId;
    }

    public function saveQueue($postId, $post, $update)
    {
        if ($post->post_status != 'publish' || $update || wp_is_post_revision($postId)) {
            return;
        }

        $mailList = [];
        $termList = wp_get_post_terms(
            $postId,
            'rrze_greetings_mail_list',
            ['fields' => 'ids']
        );
        foreach ($termList as $term) {
            $termMeta = get_term_meta($term->term_id, 'rrze_greetings_mail_list', true);
            $termMetaAry = array_filter(explode(PHP_EOL, $termMeta));
            if (!empty($termMetaAry)) {
                $mailList[$term->term_id][] = $termMetaAry;
            }
        }

        $subject = $post->post_title;
        $message = $post->post_content;
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit'
        ];

        $mailAtts = [
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
        ];
    }
}
