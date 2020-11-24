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

		add_action('template_redirect', [$this, 'previewTemplate']);
		add_action('template_redirect', [$this, 'showTemplate']);
	}

	public function previewLink(string $url, \WP_Post $post): string
	{
		if ($post->post_type == 'greeting') {
			$url = Functions::virtualUrl($post->ID, 'greetings-card-preview');
		}
		return $url;
	}

	public function postLink(string $url, \WP_Post $post): string
	{
		if ($post->post_type == 'greeting') {
			$url = Functions::virtualUrl($post->ID, 'greetings-card');
		}
		return $url;
	}

	public function listActions()
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

	public function previewTemplate()
	{
		if (!isset($_GET['id']) || !isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'greetings-card-preview')) {
			return;
		}		

		$postId = absint($_GET['id']);
		if ($postId && ($post = get_post($postId))) {
			echo $post->post_content;
			exit;
		}
	}

	public function showTemplate()
	{
		if (!isset($_GET['id']) || !isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'greetings-card')) {
			return;
		}		

		$postId = absint($_GET['id']);
		if ($postId && ($post = get_post($postId))) {
			echo $post->post_content;
			exit;
		}
	}	
}
