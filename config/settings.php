<?php

namespace RRZE\Greetings\Config;

defined('ABSPATH') || exit;

/**
 * Returns the name of the option.
 * @return string Option name
 */
function getOptionName(): string
{
    return 'rrze_greetings';
}

/**
 * Returns the settings of the menu.
 * @return array Menu settings
 */
function getMenuSettings(): array
{
    return [
        'page_title'    => __('Greetings', 'rrze-greetings'),
        'menu_title'    => __('Greetings', 'rrze-greetings'),
        'capability'    => 'manage_options',
        'menu_slug'     => 'rrze-greetings',
        'title'         => __('Greetings Settings', 'rrze-greetings'),
    ];
}

/**
 * Returns the sections settings.
 * @return array Sections settings
 */
function getSections(): array
{
    return [
        [
            'id'    => 'mail_server',
            'title' => __('Mail Server', 'rrze-greetings')
        ],
        [
            'id'    => 'mail_queue',
            'title' => __('Mail Queue', 'rrze-greetings')
        ],
        [
            'id'    => 'mailing_list',
            'title' => __('Mailing List', 'rrze-greetings')
        ],
        [
            'id'    => 'templates',
            'title' => __('Templates', 'rrze-greetings')
        ]
    ];
}

/**
 * Returns the settings fields.
 * @return array Settings fields
 */
function getFields(): array
{
    return [
        'mail_server' => [
            [
                'name'    => 'encryption',
                'label'   => __('Encryption', 'rrze-greetings'),
                'desc'    => '',
                'type'    => 'radio',
                'options' => [
                    'none' => __('None', 'rrze-greetings'),
                    'tls'  => __('TLS', 'rrze-greetings'),
                    'ssl'  => __('SSL', 'rrze-greetings')
                ]
            ],
            [
                'name'              => 'host',
                'label'             => __('Host', 'rrze-greetings'),
                'desc'              => __('Host ip address.', 'rrze-greetings'),
                'placeholder'       => '127.0.0.1',
                'type'              => 'text',
                'default'           => '127.0.0.1',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'              => 'port',
                'label'             => __('Port', 'rrze-greetings'),
                'desc'              => __('Host port.', 'rrze-greetings'),
                'placeholder'       => '587',
                'type'              => 'text',
                'default'           => '587',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'              => 'sender',
                'label'             => __('Sender Addresse', 'rrze-greetings'),
                'desc'              => '',
                'placeholder'       => '',
                'type'              => 'text',
                'default'           => '',
                'sanitize_callback' => ['\RRZE\Greetings\Functions', 'sanitizeEmail']
            ],
            [
                'name'  => 'auth',
                'label' => __('Authentication', 'rrze-greetings'),
                'desc'  => __('Authentication is required to access the SMTP server', 'rrze-greetings'),
                'type'  => 'checkbox'
            ],
            [
                'name'              => 'username',
                'label'             => __('Username', 'rrze-greetings'),
                'desc'              => '',
                'placeholder'       => '',
                'type'              => 'text',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'    => 'password',
                'label'   => __('Password', 'rrze-greetings'),
                'desc'    => '',
                'type'    => 'password',
                'default' => ''
            ]
        ],
        'mail_queue' => [
            [
                'name'              => 'limit',
                'label'             => __('Queue Limit', 'rrze-greetings'),
                'desc'              => __('Maximum number of emails that can be queued at once.', 'rrze-greetings'),
                'placeholder'       => '100',
                'min'               => '1',
                'max'               => '200',
                'step'              => '1',
                'type'              => 'number',
                'default'           => '100',
                'sanitize_callback' => [
                    function ($input) {
                        return \RRZE\Greetings\Functions::validateIntRange($input, 100, 1, 200);
                    }
                ]
            ],
            [
                'name'              => 'send_limit',
                'label'             => __('Send Limit', 'rrze-greetings'),
                'desc'              => __('Maximum number of emails that can be sent per minute.', 'rrze-greetings'),
                'placeholder'       => '15',
                'min'               => '1',
                'max'               => '60',
                'step'              => '1',
                'type'              => 'number',
                'default'           => '15',
                'sanitize_callback' => [
                    function ($input) {
                        return \RRZE\Greetings\Functions::validateIntRange($input, 15, 1, 60);
                    }
                ]
            ],
            [
                'name'              => 'max_retries',
                'label'             => __('Max. Retries', 'rrze-greetings'),
                'desc'              => __('Maximum number of retries until an email is sent successfully.', 'rrze-greetings'),
                'placeholder'       => '1',
                'min'               => '0',
                'max'               => '10',
                'step'              => '1',
                'type'              => 'number',
                'default'           => '1',
                'sanitize_callback' => [
                    function ($input) {
                        return \RRZE\Greetings\Functions::validateIntRange($input, 1, 0, 10);
                    }
                ]
            ]
        ],
        'mailing_list' => [
            [
                'name'              => 'unsubscribed',
                'label'             => __('Unsubscribed E-mail Addresses', 'rrze-greetings'),
                'desc'              => __('List of cancelled email addresses through the unsubscription link.', 'rrze-greetings'),
                'placeholder'       => '',
                'type'              => 'textarea',
                'default'           => '',
                'sanitize_callback' => ['\RRZE\Greetings\Functions', 'sanitizeMailingList']
            ],
        ],
        'templates' => [
            [
                'name'    => 'import',
                'label'   => __('Import Template', 'rrze-greetings'),
                'desc'    => __('Import a default template.', 'rrze-greetings'),
                'type'    => 'select',
                'default' => '',
                'options' => [
                    '' => __('None', 'rrze-greetings'),
                    'christmas-de_DE' => __('Christmas (de_DE)', 'rrze-greetings')
                ],
                'sanitize_callback' => ['\RRZE\Greetings\Template', 'import']
            ],
        ]
    ];
}
