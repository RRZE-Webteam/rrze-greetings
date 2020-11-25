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
		add_filter('preview_post_link', [$this, 'previewLink'], 10, 2);
		add_filter('post_type_link', [$this, 'postLink'], 10, 2);

		add_action('wp', [$this, 'listActions']);

		add_action('template_redirect', [$this, 'redirectTemplate']);
	}

	public function previewLink(string $url, \WP_Post $post): string
	{
		if ($post->post_type == 'greeting') {
			$url = sprintf(
				'/greetings-card/?id=%d&nonce=%s',
				$post->ID,
				wp_create_nonce('greetings-card-preview')
			);
		}
		return $url;
	}

	public function postLink(string $url, \WP_Post $post): string
	{
		if ($post->post_type == 'greeting') {
			$url = sprintf(
				'/greetings-card/%d',
				$post->ID
			);
		}
		return $url;
	}

	public function listActions()
	{
		if (isset($_GET['id']) && isset($_GET['rrze_greetings_action']) && isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'rrze_greetings_action')) {
			$postId = absint($_GET['id']);
			$action = sanitize_text_field($_GET['rrze_greetings_action']);

			$post = get_post($postId);
			if (is_null($post) || $post->post_type != 'greeting' || $post->post_status != 'publish') {
				return;
			}

			$data = Greeting::getData($postId);
			if (!$data) {
				return;
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

			wp_redirect(get_admin_url() . 'edit.php?post_type=greeting');
			exit;
		}
	}

	public function redirectTemplate()
	{
		if (empty($_SERVER['REQUEST_URI'])) {
			return;
		}
		$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$segments = array_values(array_filter(explode('/', $path)));

		if ($segments[0] == 'greetings-card' && isset($_GET['id']) && isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'greetings-card-preview')) {
			$postId = absint($_GET['id']);
			if ($postId && ($post = get_post($postId)) && current_user_can('edit_post', $postId)) {
				echo $post->post_content;
				exit;
			}
		} elseif ($segments[0] == 'greetings-card' && !empty($segments[1])) {
			$postId = absint($segments[1]);
			if ($postId && ($post = get_post($postId)) && $post->post_status == 'publish') {
				echo $post->post_content;
				exit;
			}
		} elseif ($segments[0] == 'greetings-card') {
			global $wp_query;
			$wp_query->set_404();
			status_header(404);
			get_template_part(404);
			exit;
		}
	}
}
