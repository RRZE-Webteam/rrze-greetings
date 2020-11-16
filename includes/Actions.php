<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

use RRZE\Greetings\CPT\Greeting;

class Actions
{
	public function __construct()
	{
		//
	}

	public function onLoaded()
	{
		add_action('admin_init', [$this, 'handleActions']);
	}

	public function handleActions()
	{
		if (isset($_GET['action']) && isset($_GET['id']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'action')) {
			$postId = absint($_GET['id']);
			$action = sanitize_text_field($_GET['action']);

			$post = get_post($postId);
			if ($post->post_type != 'greeting' || $post->post_status != 'publish') {
				return;
			}

			$data = Greeting::getData($postId);
			if (!$data) {
				return;
			}

			// @todo handle action

			wp_redirect(get_admin_url() . 'edit.php?post_type=greeting');
			exit;
		}
	}
}
