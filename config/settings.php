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
            'id'    => 'smtp',
            'title' => __('SMTP Settings', 'rrze-greetings')
        ],
        [
            'id'    => 'queue',
            'title' => __('Queue Settings', 'rrze-greetings')
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
        'smtp' => [
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
                'placeholder'       => __('127.0.0.1', 'rrze-greetings'),
                'type'              => 'text',
                'default'           => '127.0.0.1',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'              => 'port',
                'label'             => __('Port', 'rrze-greetings'),
                'desc'              => __('Host port.', 'rrze-greetings'),
                'placeholder'       => __('587', 'rrze-greetings'),
                'type'              => 'text',
                'default'           => '587',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'  => 'checkbox',
                'label' => __('Use authentication', 'rrze-greetings'),
                'desc'  => __('Authentication is required to access the server', 'rrze-greetings'),
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
        'queue' => [
            [
                'name'              => 'limit',
                'label'             => __('Queue Limit', 'rrze-greetings'),
                'desc'              => __('Amount of mails processed per cronjob execution.', 'rrze-greetings'),
                'placeholder'       => '10',
                'min'               => 1,
                'max'               => 100,
                'step'              => '1',
                'type'              => 'number',
                'default'           => '10',
                'sanitize_callback' => 'floatval'
            ],
            [
                'name'              => 'interval',
                'label'             => __('WP-Cron Interval', 'rrze-greetings'),
                'desc'              => __('Time in seconds wp_cron waits until next execution.', 'rrze-greetings'),
                'placeholder'       => '300',
                'min'               => 60,
                'max'               => 3600,
                'step'              => '1',
                'type'              => 'number',
                'default'           => '300',
                'sanitize_callback' => 'floatval'
            ],
            [
                'name'              => 'min_recipients',
                'label'             => __('Min. recipients to enqueue', 'rrze-greetings'),
                'desc'              => __('Minimum amount of recipients required to enqueue mail instead of sending immediately.', 'rrze-greetings'),
                'placeholder'       => '1',
                'min'               => 1,
                'max'               => 100,
                'step'              => '1',
                'type'              => 'number',
                'default'           => '1',
                'sanitize_callback' => 'floatval'
            ],
            [
                'name'              => 'max_retry',
                'label'             => __('Max. retry for mail sending', 'rrze-greetings'),
                'desc'              => __('Maximum number of retry for mail sending.', 'rrze-greetings'),
                'placeholder'       => '1',
                'min'               => 0,
                'max'               => 100,
                'step'              => '1',
                'type'              => 'number',
                'default'           => '1',
                'sanitize_callback' => 'floatval'
            ]
        ]
    ];
}
