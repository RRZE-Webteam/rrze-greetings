<?php

namespace RRZE\Greetings\Mail;

defined('ABSPATH') || exit;

use RRZE\Greetings\Settings;

class SMTP
{
    protected $altBody = '';

    protected $attachments = [];

    /**
     * Options
     * @var object
     */
    protected $options;

    public function __construct()
    {
        $options = Settings::getOptions();
        $this->options = (object) $options['smtp'];
    }

    public function onLoaded()
    {
        // Fires after a PHPMailer\PHPMailer\Exception is caught.
        add_action('wp_mail_failed', [$this, 'onMailError']);
    }

    /**
     * send
     * Send an email.
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $altBody
     * @param string $headers
     * @param string $attachment
     * @return boolean
     */
    public function send(string $to, string $subject, string $body, string $altBody, string $headers, string $attachments = []): bool
    {
        $this->altBody = $altBody;
        $this->attachments = $attachments;

        // Setup before send email.
        add_action('phpmailer_init', [$this, 'phpMailerInit']);
        add_filter('wp_mail_content_type', [$this, 'setContentType']);
        add_filter('wp_mail_from', [$this, 'filterFrom']);
        add_filter('wp_mail_from_name', [$this, 'filterName']);

        // Send Email.
        $isSent = wp_mail($to, $subject, $body, $headers);

        // Cleanup after send email.
        $this->attachments = [];
        remove_action('phpmailer_init', [$this, 'phpMailerInit']);
        remove_filter('wp_mail_content_type', [$this, 'setContentType']);
        remove_filter('wp_mail_from', [$this, 'filterFrom']);
        remove_filter('wp_mail_from_name', [$this, 'filterName']);

        return $isSent;
    }

    /**
     * phpmailerInit
     * @param object $phpmailer Ref. to the current instance of \PHPMailer
     */
    public function phpMailerInit($phpmailer)
    {
        $phpmailer->SMTPKeepAlive = true;
        $phpmailer->IsSMTP();

        $phpmailer->Host = $this->options->host;
        $phpmailer->Port = $this->options->port;
        $phpmailer->SMTPSecure = ($this->options->encryption == 'none') ? false : $this->options->encryption;
        $phpmailer->SMTPAuth = ($this->options->auth == 'on');
        $phpmailer->Username = $this->options->username;
        $phpmailer->Password = $this->options->password;

        $phpmailer->AltBody = $this->altBody;

        // Add attachments to email (if any).
        foreach ($this->attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                $phpmailer->AddEmbeddedImage($attachment['path'], $attachment['cid']);
            }
        }
    }

    public function setContentType()
    {
        return "text/html";
    }

    /**
     * filterFrom
     * Callable function of the hook 'wp_mail_from'. 
     * Filters the email address to send from.
     * @param string $from Sender's email address
     * @return string
     */
    public function filterFrom($from): string
    {
        $newFrom = $this->options->email_sender_email;
        return ($newFrom != '') ? $newFrom : $from;
    }

    /**
     * filterName
     * Callable function of the hook 'wp_mail_from_name'.
     * Filters the name to associate with the 'from' email address.
     * @param string $name Sender's name
     * @return string
     */
    public function filterName($name): string
    {
        $newName = $this->options->email_sender_name;
        return ($newName != '') ? $newName : $name;
    }

    /**
     * PHPMailer Exception message
     * @param object $error \WP_Error object with the PHPMailer\PHPMailer\Exception message, 
     *                      and an array containing the mail recipient, subject, message, headers, and attachments.
     * @return object       \WP_Error object
     */
    public function onMailError($error)
    {
        return $error;
    }
}
