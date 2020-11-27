<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

use RRZE\Greetings\CPT\Greeting;

class Events
{
    public static function mailQueue()
    {
        $gPosts = self::getQueuedGreeting('send');
        if (empty($gPosts)) {
            return;
        }
        foreach ($gPosts as $postId) {
            $data = Greeting::getData($postId);
            self::sendToMailQueue($data);
        }
    }

    public static function mailSend()
    {
        //
    }

    public static function handleStatus()
    {
        $posts = self::getQueuedGreeting('queued');
        if (empty($posts)) {
            return;
        }
        foreach ($posts as $postId) {
            if (!self::mailQueueExists($postId)) {
                update_post_meta($postId, 'rrze_greetings_status', 'sent');
            }
        }
    }

    protected static function sendToMailQueue(array $data)
    {
        $mailList = [];
        foreach ($data['mail_lists']['terms'] as $term) {
            if (empty($list = (string) get_term_meta($term->term_id, 'rrze_greetings_mail_list', true))) {
                continue;
            }
            $mailList = array_merge($mailList, explode(PHP_EOL, $list));
        }

        $mailList = array_unique($mailList);

        $postId = $data['id'];
        $subject = $data['title'];
        $message = $data['content'];
        $altMessage = $data['excerpt'];
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit'
        ];

        $queued = false;
        $search = '(=unsubscribe_url)';

        foreach ($mailList as $email) {
            $unsubscribeUri = '/greetings-card/unsubscribe/' . Functions::crypt($email);

            $mailAtts = [
                'headers' => $headers,
            ];

            $args = [
                'post_title' => $subject,
                'post_content' => str_replace($search, site_url($unsubscribeUri), $message),
                'post_excerpt' => str_replace($search, site_url($unsubscribeUri), $altMessage),
                'post_type' => 'greeting_queue',
                'post_status' => 'mail_queue_queued',
                'post_author' => 1
            ];

            $qId = wp_insert_post($args);
            if ($qId != 0 || !is_wp_error($qId)) {
                add_post_meta($qId, 'rrze_greetings_queue_greeting_id', $postId, true);
                add_post_meta($qId, 'rrze_greetings_queue_greeting_url', Greeting::getPostUrl($postId), true);
                add_post_meta($qId, 'rrze_greetings_queue_send_date_gmt', strtotime($data['send_date_gmt']), true);
                add_post_meta($qId, 'rrze_greetings_queue_from', $data['from'], true);
                add_post_meta($qId, 'rrze_greetings_queue_to', $email, true);
                add_post_meta($qId, 'rrze_greetings_queue_retries', 0, true);
                $queued = true;
            }
        }
        if ($queued) {
            update_post_meta($postId, 'rrze_greetings_status', 'queued');
        }
    }

    public static function getQueuedGreeting(string $status): array
    {
        $args = [
            'fields'            => 'ids',
            'post_type'         => 'greeting',
            'post_status'       => 'publish',
            'nopaging'          => true,
            'meta_query'        => [
                'relation'      => 'AND',
                'status_clause' => [
                    'key'       => 'rrze_greetings_status',
                    'value'     => $status,
                    'compare'   => '='
                ],
                //'send_clause' => [
                //    'key'       => 'rrze_greetings_send_date_gmt',
                //    'value'     => time(),
                //    'compare'   => '<',
                //    'type' => 'numeric'
                //]
            ]
        ];
        return get_posts($args);
    }

    /**
     * Checks whether a mail queue exists.
     * @param integer $postId
     * @return boolean
     */
    public static function mailQueueExists(int $postId): bool
    {
        $args = [
            'fields'            => 'ids',
            'post_type'         => 'greeting_queue',
            'post_status'       => 'mail_queue_queued',
            'meta_query'        => [
                'key'       => 'rrze_greetings_queue_greeting_id',
                'value'     => $postId,
                'compare'   => '='
            ]
        ];
        return !empty(get_posts($args));
    }
}
