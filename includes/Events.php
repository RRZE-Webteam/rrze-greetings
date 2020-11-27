<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

use RRZE\Greetings\CPT\Greeting;

class Events
{
    public static function mailQueue()
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
                    'value'     => 'send',
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
        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $data = Greeting::getData(get_the_ID());
                self::sendToMailQueue($data);
            }
            wp_reset_postdata();
        }
    }

    public static function mailSend()
    {
        //
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
                'post_status' => 'publish',
                'post_author' => 1
            ];

            $qId = wp_insert_post($args);
            if ($qId != 0 || !is_wp_error($qId)) {
                $queued = true;
            }
        }
        if ($queued) {
            update_post_meta($postId, 'rrze_greetings_status', 'queued');
        }
    }
}
