<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

class Metaboxes
{
    public function __construct()
    {
        //
    }

    public function onLoaded()
    {
        add_action('cmb2_admin_init', [$this, 'metaboxes']);
    }

    public function metaboxes()
    {
        $this->cardSettings();
        $this->imageSettings();
        $this->mailSettings();
    }

    protected function cardSettings()
    {
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_post',
            'title' => __('Card Settings', 'rrze-greetings'),
            'object_types' => ['greeting'],
            'context' => 'normal',
            'priority' => 'low',
            'show_names' => true
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_card_template',
            'name' => __('Template', 'rrze-greetings'),
            'desc' => __('Select a greeting card template.', 'rrze-greetings'),
            'type' => 'select',
            'default' => '',
            'options_cb' => [$this, 'templatesOptions'],
            'sanitization_cb' => 'sanitize_text_field'
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_title',
            'name' => __('Title', 'rrze-greetings'),
            'desc' => __('The title of the content of the greeting card.', 'rrze-greetings'),
            'type' => 'text',
            'show_on_cb' => [$this, 'showIfTemplate'],
            'sanitization_cb' => 'sanitize_text_field'
        ]);

        $cmb->add_field(array(
            'id' => 'rrze_greetings_post_content',
            'name' => __('Text', 'rrze-greetings'),
            'desc' => __('Text that will be shown after the greeting card image.', 'rrze-greetings'),
            'type' => 'textarea',
            'attributes' => [
                'rows' => '8'
            ],
            'show_on_cb' => [$this, 'showIfTemplate'],
            'sanitization_cb' => 'sanitize_textarea_field'
        ));

        $cmb->add_field(array(
            'id' => 'rrze_greetings_logo',
            'name' => 'Logo (Optional)',
            'desc' => 'The image of the website logo that will be displayed on the greeting card.',
            'type' => 'file',
            'options' => [
                'url' => false,
            ],
            'text' => [
                'add_upload_file_text' => 'Add Logo Image'
            ],
            'query_args' => [
                'type' => [
                    'image/jpeg',
                    'image/png',
                ],
            ],
            'show_on_cb' => [$this, 'showIfTemplate'],
            'preview_size' => 'medium'
        ));

        $cmb->add_field(array(
            'id' => 'rrze_greetings_footer',
            'name' => __('Footer', 'rrze-greetings'),
            'desc' => __('Footer text containing the unsubscribe link.', 'rrze-greetings'),
            'type' => 'textarea',
            'attributes' => [
                'rows' => '3'
            ],
            'show_on_cb' => [$this, 'showIfTemplate'],
            'sanitization_cb' => [$this, 'filterText']
        ));
    }

    protected function imageSettings()
    {
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_imagetext',
            'title' => __('Image Settings', 'rrze-greetings'),
            'object_types' => ['greeting'],
            'context' => 'normal',
            'priority' => 'low',
            'show_names' => true,
            'show_on_cb' => [$this, 'showIfTemplate']
        ]);

        $cmb->add_field(array(
            'id' => 'rrze_greetings_post_excerpt',
            'name' => __('Text', 'rrze-greetings'),
            'desc' => __('Text to be printed on the card image.', 'rrze-greetings'),
            'type' => 'textarea',
            'attributes' => [
                'rows' => '4'
            ],
            'sanitization_cb' => 'sanitize_textarea_field'
        ));

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_width',
            'name' => __('Line width', 'rrze-greetings'),
            'desc' => __('Number of characters per line.', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '40',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint'
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_startx',
            'name' => __('X coordinate offset', 'rrze-greetings'),
            'desc' => __('X coordinate offset from which text will be positioned relative to the image.', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '0',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint'
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_starty',
            'name' => __('Y coordinate offset', 'rrze-greetings'),
            'desc' => __('Y coordinate offset from which text will be positioned relative to the image.', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '0',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint'
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_align',
            'name' => __('Text alignment', 'rrze-greetings'),
            'type' => 'radio',
            'default' => 'left',
            'options' => [
                'left' => __('Left', 'rrze-greetings'),
                'center' => __('Center', 'rrze-greetings'),
                'right' => __('Right', 'rrze-greetings'),
            ],
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_font',
            'name' => __('Font', 'rrze-greetings'),
            'desc' => __('Select a text font.', 'rrze-greetings'),
            'type' => 'select',
            'default' => 'fonts/Roboto/Roboto-LightItalic.ttf',
            'options_cb' => [$this, 'fontsOptions']
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_color',
            'name' => __('Font color', 'rrze-greetings'),
            'type' => 'colorpicker',
            'default' => '#000000',
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_lineheight',
            'name' => __('Text line height', 'rrze-greetings'),
            'desc' => __('Text line height (pts).', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '24',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint'
        ]);

        $cmb->add_field([
            'id'   => 'rrze_greetings_imagetext_size',
            'name' => __('Text size', 'rrze-greetings'),
            'desc' => __('Text size (pts).', 'rrze-greetings'),
            'type' => 'text_small',
            'default' => '16',
            'attributes' => [
                'type' => 'number',
                'pattern' => '\d*',
            ],
            'sanitization_cb' => 'absint'
        ]);
    }

    protected function mailSettings()
    {
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_mail',
            'title' => __('Mail Settings', 'rrze-greetings'),
            'object_types' => ['greeting'],
            'context' => 'normal',
            'priority' => 'low',
            'show_names' => true,
            'show_on_cb' => [$this, 'showIfTemplate']
        ]);

        $cmb->add_field([
            'name' => __('Send Date/Time', 'rrze-greetings'),
            'id' => 'rrze_greetings_send_date',
            'type' => 'text_datetime_timestamp',
            'date_format' => __('d-m-Y', 'rrze-greetings'),
            'time_format' => __('H:i', 'rrze-greetings'),
            'attributes' => [
                'data-timepicker' => json_encode(
                    [
                        'timeFormat' => 'HH:mm',
                        'stepMinute' => 10,
                    ]
                ),
                'required' => 'required',
            ],
        ]);

        $cmb->add_field([
            'name' => __('From Name', 'rrze-greetings'),
            'id' => 'rrze_greetings_from_name',
            'type' => 'text_medium',
            'attributes' =>  [
                'required' => 'required',
            ],
        ]);

        $cmb->add_field([
            'name' => __('From Email Address', 'rrze-greetings'),
            'id' => 'rrze_greetings_from_email_address',
            'type' => 'text_email',
            'attributes' =>  [
                'required' => 'required',
            ],
        ]);

        $postId = null;
        if (isset($_GET['post'])) {
            $postId = $_GET['post'];
        } elseif (isset($_POST['post_ID'])) {
            $postId = $_POST['post_ID'];
        }

        if (!$postId || !has_post_thumbnail($postId)) {
            return;
        }
    }

    public function showIfTemplate($cmb)
    {
        $template = get_post_meta($cmb->object_id(), 'rrze_greetings_card_template', true);
        if ($template && is_a($cmb, 'CMB2')) {
            return in_array($cmb->meta_box['id'], [
                'rrze_greetings_imagetext',
                'rrze_greetings_mail'
            ]);
        }
        if (is_a($cmb, 'CMB2_Field')) {
            switch ($template) {
                case 'templates/Frohe-Weihnachten-Simpel.html':
                    return in_array($cmb->args['_id'], [
                        'rrze_greetings_title',
                        'rrze_greetings_post_content',
                        'rrze_greetings_logo',
                        'rrze_greetings_footer'
                    ]);
                default:
                    return false;
            }
        }
    }

    public function templatesOptions($field)
    {
        $templates = Functions::getFiles(plugin()->getPath('templates'), ['html'], 'templates');
        asort($templates);
        return array_merge(['' => __('None', 'rrze-greetings')], $templates);
    }

    public function fontsOptions($field)
    {
        $fonts = Functions::getFiles(plugin()->getPath('assets/fonts'), ['ttf', 'otf'], 'fonts');
        asort($fonts);
        return $fonts;
    }

    public function filterText($value, $field_args, $field)
    {
        $allowedHtml = [
            'a' => [
                'href' => [],
                'title' => [],
                'style' => []
            ],
            'br' => [],
            'em' => [],
            'strong' => [],
            'p' => []
        ];

        return wp_kses($value, $allowedHtml);
    }
}
