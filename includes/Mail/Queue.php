<?php

namespace RRZE\Greetings\Mail;

defined('ABSPATH') || exit;

use RRZE\Greetings\Settings;
use RRZE\Greetings\Functions;
use RRZE\Greetings\CPT\Greeting;

use function RRZE\Greetings\plugin;

class Queue
{
    /**
     * Options
     * @var object
     */
    protected $options;

    /**
     * SMTP
     * @var object RRZE\Greetings\Mail\SMTP
     */
    protected $smtp;

    public function __construct()
    {
        $this->options = (object) Settings::getOptions();
        $this->smtp = new SMTP;
        $this->smtp->onLoaded();
    }

    /**
     * Get the maximum number of emails that can be queued at once.
     * @return int Max. number of emails queued at once.
     */
    public function queueLimit()
    {
        $limit = $this->options->mail_queue_limit;
        return absint($limit);
    }

    /**
     * Get the maximum number of emails that can be sent per minute.
     * @return int Max. number of emails sent per minute.
     */
    public function sendLimit()
    {
        $limit = $this->options->mail_queue_send_limit;
        return absint($limit);
    }

    /**
     * Get max. number of retries until an email is sent successfully.
     * @return int Max. number of retries.
     */
    public function maxRetries()
    {
        $maxRetries = $this->options->mail_queue_max_retries;
        return absint($maxRetries);
    }

    /**
     * Checks whether the mail queue is being created.
     * @param integer $postId
     * @return boolean
     */
    public static function isQueueBeingCreated(int $postId): bool
    {
        return !empty(get_option('rrze_greetings_queue_' . $postId));
    }

    /**
     * Process items from the mail queue.
     */
    public function processQueue()
    {
        $queue = $this->getQueue();

        foreach ($queue as $post) {
            if (!($greetingId = absint(get_post_meta($post->ID, 'rrze_greetings_queue_greeting_id', true)))) {
                continue;
            }
            
            $from = get_post_meta($greetingId, 'rrze_greetings_from_email_address', true);
            $fromName = get_post_meta($greetingId, 'rrze_greetings_from_name', true);

            $sender = get_post_meta($greetingId, 'rrze_greetings_sender_email_address', true);

            $replyTo = get_post_meta($greetingId, 'rrze_greetings_replyto_email_address', true);

            $to  = get_post_meta($post->ID, 'rrze_greetings_queue_to', true);

            $subject = $post->post_title;
            $body = Functions::htmlDecode($post->post_content);
            $altBody = Functions::htmlDecode($post->post_excerpt);

            $website = get_bloginfo('name') ?? parse_url(site_url(), PHP_URL_HOST);
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'X-Mailtool: RRZE-Greetings Plugin V' . plugin()->getVersion() . ' on ' . $website,
                'Reply-To: ' . $replyTo
            ];

            $isSent = $this->smtp->send(
                $from,
                $fromName,
                $sender,
                $to,
                $subject,
                $body,
                $altBody,
                $headers
            );

            if ($isSent) {
                $args = [
                    'ID' => $post->ID,
                    'post_status' => 'mail_queue_sent'
                ];
                wp_update_post($args);
            } else {
                $error = $this->smtp->getError();
                update_post_meta($post->ID, 'rrze_greetings_queue_error', $error->get_error_message());
                $retries = absint(get_post_meta($post->ID, 'rrze_greetings_queue_retries', true));
                if ($retries >= $this->maxRetries()) {
                    $args = [
                        'ID' => $post->ID,
                        'post_status' => 'mail_queue_error'
                    ];
                    wp_update_post($args);
                } else {
                    $retries++;
                    update_post_meta($post->ID, 'rrze_greetings_queue_retries', $retries);
                }
            }
        }
    }

    /**
     * Get Mail Queue.
     * @return array Array of post objects.
     */
    public function getQueue()
    {
        $before = time();
        $sendLimit = $this->sendLimit();

        $args = [
            'post_type'         => ['greeting_queue'],
            'post_status'       => 'mail_queue_queued',
            'nopaging'          => true,
            'numberposts'       => $sendLimit,
            'order'             => 'ASC',
            'orderby'           => 'date',            
            'date_query'        => [
                [
                    'column'    => 'post_date_gmt',
                    'before'    => date('Y-m-d H:i:s', $before),
                    'inclusive' => false,
                ],
            ]
        ];

        return get_posts($args);
    }

    public function setQueue(int $postId)
    {
        if (!($data = Greeting::getData($postId))) {
            return;
        }

        $mailingList = [];
        foreach ($data['mail_lists']['terms'] as $term) {
            if (empty($list = (string) get_term_meta($term->term_id, 'rrze_greetings_mailing_list', true))) {
                continue;
            }
            $mailingList = array_merge($mailingList, explode(PHP_EOL, $list));
        }
        $mailingList = array_unique($mailingList);       
        $options = (object) Settings::getOptions();
        $unsubscribedMailingList = explode(PHP_EOL, sanitize_textarea_field((string) $options->mailing_list_unsubscribed));
        $mailingList = array_diff($mailingList, $unsubscribedMailingList);
        
        $mailingListQueue = get_option('rrze_greetings_queue_' . $postId);
        if (empty($mailingListQueue)) {
            update_option('rrze_greetings_queue_' . $postId, $mailingList);
            Greeting::setStatus($postId, 'queued');
            $mailingListQueue = $mailingList;
        }

        $postId = $data['id'];
        $subject = $data['title'];
        $message = $data['content'];
        $altMessage = $data['excerpt'];

        $search = '((=unsubscribe_url))';

        $count = 1;
        foreach ($mailingListQueue as $key => $email) {
            if ($count > $this->queueLimit()) {
                break;
            }
            $unsubscribeUri = '/greeting-card/?unsubscribe=' . Functions::crypt($email);
            $content = str_replace($search, site_url($unsubscribeUri), $message);
            $excerpt = str_replace($search, site_url($unsubscribeUri), $altMessage);

            $args = [
                'post_date' => $data['send_date'],
                'post_date_gmt' => $data['send_date_gmt'],
                'post_title' => $subject,
                'post_content' => $content,
                'post_excerpt' => $excerpt,
                'post_type' => 'greeting_queue',
                'post_status' => 'mail_queue_queued',
                'post_author' => 1
            ];

            remove_filter('content_save_pre', 'wp_filter_post_kses');
            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

            $qId = wp_insert_post($args);

            add_filter('content_save_pre', 'wp_filter_post_kses');
            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

            if ($qId != 0 || !is_wp_error($qId)) {
                add_post_meta($qId, 'rrze_greetings_queue_greeting_id', $postId, true);
                add_post_meta($qId, 'rrze_greetings_queue_greeting_url', Greeting::getPostUrl($postId), true);
                add_post_meta($qId, 'rrze_greetings_queue_from', $data['from'], true);
                add_post_meta($qId, 'rrze_greetings_queue_to', $email, true);
                add_post_meta($qId, 'rrze_greetings_queue_retries', 0, true);
            }

            unset($mailingListQueue[$key]);
            update_option('rrze_greetings_queue_' . $postId, $mailingListQueue);
            $count++;
        }
        if (empty($mailingListQueue)) {
            delete_option('rrze_greetings_queue_' . $postId);
            Greeting::setStatus($postId, 'sent');
        }
    }
}
