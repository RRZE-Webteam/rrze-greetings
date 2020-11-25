<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'greeting_queue'
 * ------------------------------------------------------------------------- */

namespace RRZE\Greetings\CPT;

defined('ABSPATH') || exit;

use RRZE\Greetings\Functions;

class GreetingQueue
{
    /**
     * Custom post type
     * @var string
     */
	protected static $postType = 'greeting_queue';
		
	public function __construct()
	{
		//
	}

	public function onLoaded()
	{
		// Register CPT.
		add_action('init', [$this, 'registerPostType']);
		// CPT Custom Columns.
		add_filter('manage_greeting_queue_posts_columns', [$this, 'columns']);
		add_action('manage_greeting_queue_posts_custom_column', [$this, 'customColumn'], 10, 2);
		add_filter('manage_edit-greeting_queue_sortable_columns', [$this, 'sortableColumns']);
		// CPT List Filters.
		add_filter('months_dropdown_results', [$this, 'removeMonthsDropdown'], 10, 2);
		add_action('restrict_manage_posts', [$this, 'applyFilters']);
		add_filter('parse_query', [$this, 'filterQuery']);
	}

	public function registerPostType()
	{
		$labels = [
			'name'					=> _x('Mail Queue', 'Post type general name', 'rrze-greetings'),
			'singular_name'			=> _x('Mail Queue', 'Post type singular name', 'rrze-greetings'),
			'menu_name'				=> _x('Mail Queue', 'Admin Menu text', 'rrze-greetings'),
			'name_admin_bar'		=> _x('Mail Queue', 'Add New on Toolbar', 'rrze-greetings'),
			'add_new'				=> __('Add New', 'rrze-greetings'),
			'add_new_item'			=> __('Add New Mail Queue', 'rrze-greetings'),
			'new_item'				=> __('New Mail Queue', 'rrze-greetings'),
			'edit_item'				=> __('Edit Mail Queue', 'rrze-greetings'),
			'view_item'				=> __('View Mail Queue', 'rrze-greetings'),
			'all_items'				=> __('All Mail Queue', 'rrze-greetings'),
			'search_items'			=> __('Search Mail Queue', 'rrze-greetings'),
			'not_found'				=> __('No Mail Queue found.', 'rrze-greetings'),
			'not_found_in_trash'	=> __('No Mail Queue found in Trash.', 'rrze-greetings'),
			'filter_items_list'		=> _x('Filter Mail Queue list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'rrze-greetings'),
			'items_list_navigation'	=> _x('Mail Queue list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'rrze-greetings'),
			'items_list'			=> _x('Mail Queue list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'rrze-greetings'),
		];

		$args = [
			'label' => __('Mailing List', 'rrze-greetings'),
			'description' => __('Add and edit mailing list data', 'rrze-greetings'),
			'labels' => $labels,
			'supports'                  => false,
			'hierarchical' 				=> false,
			'public' 					=> false,
			'show_ui' 					=> true,
			'show_in_menu' 				=> false,
			'show_in_nav_menus' 		=> false,
			'show_in_admin_bar' 		=> false,
			'can_export' 				=> false,
			'has_archive' 				=> false,
			'exclude_from_search' 		=> true,
			'publicly_queryable' 		=> false,
			'delete_with_user'          => false,
			'show_in_rest'              => false,
			'capability_type' 			=> Capabilities::getCptCapabilityType(self::$postType),
			'capabilities'              => (array) Capabilities::getCptCaps(self::$postType),
			'map_meta_cap'              => Capabilities::getCptMapMetaCap(self::$postType)
		];

		register_post_type(self::$postType, $args);
	}

	public static function getData(int $postId): array
	{
		$data = [];

		$post = get_post($postId);
		if (!$post) {
			return $data;
		}

		$data['id'] = $post->ID;

        $sendDateGmt = absint(get_post_meta($post->ID, 'rrze_greetings_queue_send_date_gmt', true));
        $data['send_date_gmt'] = date('Y-m-d H:i:s', $sendDateGmt);
        $sendDate = absint(get_post_meta($post->ID, 'rrze_greetings_queue_send_date', true));
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
		
		$data['status'] = get_post_meta($post->ID, 'rrze_greetings_queue_status', true);
		$data['post_status'] = $post->post_status;

		return $data;
	}

	public function columns($columns)
	{
		$columns = [
			'cb' => $columns['cb'],
			'greeting' => __('Greeting', 'rrze-greetings'),
			'send_date' => __('Send Date', 'rrze-greetings'),
			'to' => __('To', 'rrze-greetings'),
			'subject' => __('Subject', 'rrze-greetings'),
			'retries' => __('Retries', 'rrze-greetings'),
			'status' => __('Status', 'rrze-greetings')
		];
		return $columns;
	}

	public function customColumn($column, $postId)
	{
		$data = self::getData($postId);

		switch ($column) {
			case 'greeting':
				echo '&mdash;';
				break;
			case 'send_data':
				echo '&mdash;';
				break;
			case 'to':
				echo '&mdash;';
				break;
			case 'subject':
				echo '&mdash;';
				break;
			case 'retries':
				echo '&mdash;';
				break;
			case 'status':
				echo '&mdash;';
				break;
			default:
				echo '&mdash;';
		}
	}

	public function sortableColumns($columns)
	{
		$columns = [
			'send_date' => 'send_date',
			'to' => 'send_date',
			'subject' => 'subbject'
		];
		return $columns;
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
