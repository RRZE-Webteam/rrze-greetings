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
		// Custom Post Status
		add_action('init', [$this, 'registerPostStatus']);
		// CPT Custom Columns.
		add_filter('manage_greeting_queue_posts_columns', [$this, 'columns']);
		add_action('manage_greeting_queue_posts_custom_column', [$this, 'customColumn'], 10, 2);
		add_filter('manage_edit-greeting_queue_sortable_columns', [$this, 'sortableColumns']);
		// CPT List Filters.
		add_filter('months_dropdown_results', [$this, 'removeMonthsDropdown'], 10, 2);
		add_action('restrict_manage_posts', [$this, 'applyFilters']);
		add_filter('parse_query', [$this, 'filterQuery']);
		// List Actions
		add_filter('post_row_actions', [$this, 'rowActions'], 10, 2);
		add_filter('bulk_actions-edit-greeting_queue', [$this, 'bulkActions']);
		// List Views
		add_filter('views_edit-greeting_queue', [$this, 'views']);
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

	public function registerPostStatus()
	{
		register_post_status('mail_queue_queued', [
			'label'                     => _x('Queued', 'Mail Queue Status', 'rrze-greetings'),
			'public'                    => true,
			'private'                   => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop('Queued <span class="count">(%s)</span>', 'Queued <span class="count">(%s)</span>', 'rrze-greetings')
		]);

		register_post_status('mail_queue_sent', [
			'label'                     => _x('Sent', 'Mail Queue Status', 'rrze-greetings'),
			'public'                    => true,
			'private'                   => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop('Sent <span class="count">(%s)</span>', 'Sent <span class="count">(%s)</span>', 'rrze-greetings')
		]);

		register_post_status('mail_queue_error', [
			'label'                     => _x('Error', 'Mail Queue Status', 'rrze-greetings'),
			'public'                    => true,
			'private'                   => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop('Error <span class="count">(%s)</span>', 'Error <span class="count">(%s)</span>', 'rrze-greetings')
		]);
	}

	public static function getData(int $postId): array
	{
		$data = [];

		$post = get_post($postId);
		if (!$post) {
			return $data;
		}

		$data['id'] = $post->ID;

		$data['send_date_gmt'] = $post->post_date_gmt;
		$data['send_date'] = $post->post_date;
		$data['post_date_format'] = sprintf(
			__('%1$s at %2$s'),
			get_the_time(__('Y/m/d'), $post),
			get_the_time(__('g:i a'), $post)
		);

		$data['status'] = $post->post_status;
		$data['subject'] = $post->post_title;

		$data['greeting_link'] = get_post_meta($post->ID, 'rrze_greetings_queue_greeting_url', true);
		$data['from'] = get_post_meta($post->ID, 'rrze_greetings_queue_from', true);
		$data['to'] = get_post_meta($post->ID, 'rrze_greetings_queue_to', true);
		$data['retries'] = get_post_meta($post->ID, 'rrze_greetings_queue_retries', true);

		return $data;
	}

	public function columns($columns)
	{
		$columns = [
			'cb' => $columns['cb'],
			'subject' => __('Subject', 'rrze-greetings'),
			'send_date' => __('Send Date', 'rrze-greetings'),
			'from' => __('From', 'rrze-greetings'),
			'to' => __('To', 'rrze-greetings'),
			'retries' => __('Retries', 'rrze-greetings'),
			'status' => __('Status', 'rrze-greetings')
		];
		return $columns;
	}

	public function customColumn($column, $postId)
	{
		$data = self::getData($postId);

		switch ($column) {
			case 'subject':
				echo $data['subject'];
				break;
			case 'send_date':
				echo $data['send_date'];
				break;
			case 'from':
				echo esc_attr($data['from']);
				break;
			case 'to':
				echo $data['to'];
				break;
			case 'retries':
				echo $data['retries'];
				break;
			case 'status':
				$status = get_post_stati(['show_in_admin_status_list' => true], 'objects');
				echo $status[$data['status']]->label;
				break;
			default:
				echo '&mdash;';
		}
	}

	public function sortableColumns($columns)
	{
		return $columns;
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
		if (
			$post->post_type != 'greeting_queue'
			|| !in_array($post->post_status, ['mail_queue_queued', 'mail_queue_sent', 'mail_queue_error'])
		) {
			return $actions;
		}
		if (isset($actions['edit'])) {
			unset($actions['edit']);
		}
		if (isset($actions['inline hide-if-no-js'])) {
			unset($actions['inline hide-if-no-js']);
		}
		return $actions;
	}

	public function bulkActions($actions)
	{
		if (isset($actions['edit'])) {
			unset($actions['edit']);
		}
		return $actions;
	}

	public function views($views)
	{
		if (isset($views['mine'])) {
			unset($views['mine']);
		}
		return $views;
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
