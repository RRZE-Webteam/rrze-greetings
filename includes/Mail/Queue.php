<?php

namespace RRZE\Greetings\Mail;

defined('ABSPATH') || exit;

use RRZE\Greetings\Settings;
use RRZE\Greetings\Mail\SMTP;

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
        $options = Settings::getOptions();
        $this->options = (object) $options['queue'];
    }

    public function onLoaded()
    {
        $this->smtp = new SMTP;
    }

    /**
     * Get interval.
     * @return int $interval Value of interval.
     */
    public function interval()
    {
        $interval = $this->options->interval;
        return absint($interval);
    }

    /**
     * Get limit number of mails in interval.
     * @return int $limit Maximum number of emails in interval.
     */
    public function limit()
    {
        $limit = $this->options->limit;
        return absint($limit);
    }

    /**
     * Set/update email queue.
     * @param array $queue An array of arrays of email adresses and keys of their content.
     * @return bool|mixed  False if value was not set and true if value was set. If method is skipped, returned value.
     */
    protected function setQueue($queue)
    {
        return Transient::set($this->transientKey, $queue, WEEK_IN_SECONDS);
    }

    /**
     * Get mails queue.
     * @return array An array of arrays of email adresses and keys of their content.
     */
    public function getQueue()
    {
        $before = current_time('timestamp') - $this->options->interval;
        $limit = $this->options->limit;
        $maxRetry = $this->options->max_retry;

        $args = [
            'fields'            => 'ids',
            'post_type'         => ['greeting_queue'],
            'post_status'       => 'publish',
            'nopaging'          => true,
            'numberposts'       => $limit,
            'date_query'        => [
                [
                    'before'    => date('Y-m-d H:i:s', $before),
                    'inclusive' => false,
                ],
            ],
            'meta_query'        => [
                [
                    'key'       => 'rrze_greetings_queue_retries',
                    'value'     => $maxRetry,
                    'compare'   => '<='
                ]
            ]
        ];

        return get_posts($args);
    }

    /**
     * Add email to queue.
     * @param string|array $to          Array of email addresses to send message.
     * @param string       $subject     Email subject.
     * @param string       $message     Message contents.
     * @param string|array $headers     Optional. Additional headers.
     * @param string|array $attachments Optional. Files to attach.
     */
    public function addToQueue($to, $subject, $message, $headers = '', $attachments = [])
    {
        // Make an array with mail attributes
        $mailAtts = compact('subject', 'message', 'headers', 'attachments');

        // Set unique queue key based on values
        $queueKey = md5(serialize($mailAtts));

        $atts = [
            'post_title'    => sanitize_text_field($subject),
            'post_content'  => sanitize_textarea_field($message),
            'post_type'     => 'greeting_queue',
            'post_status'   => 'publish',
            'post_author'   => 1
        ];

        return wp_insert_post($atts, true);
    }

    /**
     * Send email from queue.
     * @param string $emailTo  Email address that should be emailed to.
     * @param array $atts Atts of the email.
     */
    protected function sendMail($emailTo, $atts)
    {
        $mail = $this->smtp->send($emailTo, $atts['subject'], $atts['message'], $atts['headers'], $atts['attachments']);
        return $mail;
    }

    /**
     * Process items from queue.
     *
     * By default, every six minutes send ten emails.
     *
     * @since 1.0
     * @access public
     */
    public function process_queue()
    {
        // Look for existing addresses
        $existing = $this->getQueue();
        if (!is_array($existing) && $existing) {
            return false;
        }

        // Check how much emails are already sent in this interval
        $sent = Transient::get('rrze_greetings_mail_queue');
        if (!$sent) {
            $sent = 0;
        }

        /*
             * Maximum number of allowed email to send
             * is difference between maximum allowed and
             * number of sent emails in this interval.
             */
        $limit = $this->limit() - $sent;

        $num_sent = 0;

        foreach ($existing as $key => $value) {
            if ($num_sent >= $limit) {
                break;
            }

            $emailTo  = key($value);
            $email_key = $value[$emailTo];

            $this->sendMail($emailTo, $email_key);

            // Remove item from array
            unset($existing[$key]);

            // Increase number of sent emails
            $num_sent++;
        }

        // Save temporary that stores existing of temporary based on existence mail in queue
        if ($existing) {
            Transient::set('rrze_greeting_mail_queue_exist', 1, WEEK_IN_SECONDS);
        } else {
            Transient::delete('rrze_greeting_mail_queue_exist');
        }

        // Save new queue
        $this->setQueue($existing);

        // Save new number of sent emails in this interval
        $new_sent = $sent + $num_sent;
        Transient::update('rrze_greetings_mail_queue', $new_sent, $this->interval());
    }

    /**
     * Schedule task if it's needed.
     *
     * @since 1.0
     * @access public
     */
    public function maybe_schedule_task()
    {
        // Check if this is Backdrop request
        if (did_action('wp_ajax_nopriv_hm_backdrop_run')) {
            return;
        }

        // Check if queue exists
        $exists = Transient::get('rrze_greeting_mail_queue_exist');
        if (!$exists) {
            return;
        }

        // Check how much emails are already sent in this interval
        $sent = Transient::get('rrze_greetings_mail_queue');
        if (!$sent) {
            $sent = 0;
        }

        // If number of sent is smaller than maximum number, schedule task
        if ($sent < $this->limit()) {
            $task = new Task(array($this, 'process_queue'));
            $task->schedule();
        }
    }
}
