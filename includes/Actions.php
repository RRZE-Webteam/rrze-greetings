<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

use RRZE\Greetings\CPT\Greeting;
use RRZE\Greetings\Mail\Queue;
use function RRZE\Greetings\Config\getOptionName;

class Actions
{
	/**
	 * Options
	 * @var object
	 */
	protected $options;

	public function __construct()
	{
		$this->options = (object) Settings::getOptions();
		$this->template = new Template;
	}

	public function onLoaded()
	{
		add_action('wp', [$this, 'buttonActions']);
		add_action('wp', [$this, 'responses']);
		add_action('admin_notices', [$this, 'adminNotices']);
		add_action('template_redirect', [$this, 'redirectTemplate']);
	}

	public function buttonActions()
	{
		if (isset($_GET['id']) && isset($_GET['rrze_greetings_action']) && isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], 'rrze_greetings_action')) {
			$postId = absint($_GET['id']);
			$action = sanitize_text_field($_GET['rrze_greetings_action']);

			$post = get_post($postId);
			if (is_null($post) || $post->post_type != 'greeting' || $post->post_status != 'publish') {
				return;
			}

			if (!Greeting::getData($postId)) {
				return;
			}

			$transientData = new TransientData('rrze_greetings_errors');
			switch ($action) {
				case 'send':
					if (!Queue::isQueueBeingCreated($postId)) {
						Greeting::setStatus($postId, 'send');
						$transientData->addData('success', __('Emails have been sent to the mail queue.', 'rrze-greetings'));
					} else {
						$transientData->addData('error', __('Unable to send the emails to the mail queue.', 'rrze-greetings'));
					}
					break;
				case 'cancel':
					if (!Queue::isQueueBeingCreated($postId) && Greeting::getStatus($postId) == 'send') {
						delete_post_meta($postId, 'rrze_greetings_status');
						$transientData->addData('success', __('The sending of emails has been cancelled.', 'rrze-greetings'));
					} else {
						$transientData->addData('error', __('Unable to cancel the sending of emails.', 'rrze-greetings'));
					}
					break;
				case 'restore':
					if (!Queue::isQueueBeingCreated($postId) && Greeting::getStatus($postId) == 'sent') {
						delete_post_meta($postId, 'rrze_greetings_status');
						$transientData->addData('success', __('The status has been changed to the default status.', 'rrze-greetings'));
					} else {
						$transientData->addData('error', __('The status cannot be changed to the default status.', 'rrze-greetings'));
					}
					break;
				default:
					$transientData->addData('error', __('The action could not be executed.', 'rrze-greetings'));
			}

			wp_redirect(get_admin_url() . 'edit.php?post_type=greeting');
			exit;
		}
	}

	public function adminNotices()
	{
		$transientData = new TransientData('rrze_greetings_errors');
		if (empty($errorMessages = $transientData->getData())) {
			return;
		}
		if (isset($errorMessages['success'])) {
			printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($errorMessages['success']));
		} else {
			foreach ($errorMessages as $message) {
				printf('<div class="notice notice-warning"><p>%s</p></div>', esc_html($message));
			}
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
		} elseif ($segments[0] == 'greetings-card' && !empty($segments[1]) && ($postId = absint($segments[1]))) {
			if ($postId && ($post = get_post($postId)) && $post->post_status == 'publish') {
				echo $post->post_content;
				exit;
			}
		} elseif ($segments[0] == 'greetings-card' && !isset($_GET['unsubscribe'])) {
			global $wp_query;
			$wp_query->set_404();
			status_header(404);
			get_template_part(404);
			exit;
		}
	}

	public function responses()
	{
		global $post;
		if (!is_a($post, '\WP_Post') || !is_page() || $post->post_name != 'greetings-card') {
			return;
		}
		if (isset($_GET['unsubscribe'])) {
			$email = isset($_GET['unsubscribe']) ? Functions::decrypt((string) $_GET['unsubscribe']) : false;
			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
				add_filter('the_content', function ($content) use ($email) {
					return $this->unsubscribeResponse($email);
				});
			} else {
				wp_redirect(site_url('/greetings-card/'));
				exit;
			}
		}
	}

	protected function unsubscribeResponse(string $email)
	{
		$mailingList = explode(PHP_EOL, (string) $this->options->mailing_list_unsubscribed);
		$mailingList[] = $email;
		$this->options->mailing_list_unsubscribed = Functions::sanitizeMailingList(implode(PHP_EOL, $mailingList));
		update_option(getOptionName(), $this->options);

		$data = [];
		$data['subject'] = __('UNSUBSCRIBE', 'rrze-rsvp');
		$data['notification_text'] = __('You are unsubscribed from the mailing list.', 'rrze-greetings');
		$data['unsubscribed_email_text'] = sprintf(__('Your email address %s was unsubscribed from our "Greetings Card" mailing list.', 'rrze-greetings'), $email);

		return $this->template->getContent('responses/unsubscribe.html', $data);
	}	
}
