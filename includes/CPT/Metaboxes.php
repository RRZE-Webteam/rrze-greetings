<?php

namespace RRZE\Greetings\CPT;

defined('ABSPATH') || exit;

use RRZE\Greetings\Functions;
use RRZE\Greetings\Template;
use function RRZE\Greetings\plugin;

class Metaboxes
{
    protected $presetFields = [];

    public function __construct()
    {
        $this->template = new Template;

        $this->presetFields = [
            'card_image_url' => __('The url of the greeting card image (required). Usage: {{=card_image_url}}', 'rrze-greetings'),
            'card_url' => __('The url of the greeting card (optional). Usage: {{=card_url}}', 'rrze-greetings'),
            'unsubscribe_url' => __('The url of the unsubscribe page (optional). Usage: {{=unsubscribe_url}}', 'rrze-greetings'),
            'website_url' => __('The url of the website (optional). Usage: {{=website_url}}', 'rrze-greetings'),
            'website_name' => __('The name of the website (optional). Usage: {{=website_name}}', 'rrze-greetings'),
            'header_image_url' => __('The url of the header image (optional). Usage: {{=header_image_url}}', 'rrze-greetings')
        ];
    }

    public function onLoaded()
    {
        // Greeting metaboxes
        add_action('cmb2_admin_init', [$this, 'greeting']);
        // Greeting Template metaboxes
        add_action('cmb2_admin_init', [$this, 'greetingTemplate']);
        add_action('cmb2_before_form', [$this, 'beforeForm'], 10, 4);
    }

    public function greeting()
    {
        $this->cardSettings();
        $this->cardImageSettings();
        $this->mailSettings();
        // Card image preview metabox       
        add_action('add_meta_boxes', [$this, 'cardImagePreview'], 10, 2);
    }

    public function greetingTemplate()
    {
        $this->contentField();
        add_action('add_meta_boxes', [$this, 'presetFields']);
        $this->customFields();
    }

    public function presetFields()
    {
        add_meta_box(
            'rrze_greetings_template_preset_fields',
            __('Preset Field Names', 'rrze-greetings'),
            [$this, 'presetFieldsList'],
            'greeting_template',
            'normal',
            'high'
        );
    }

    protected function contentField()
    {
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_template_post',
            'title' => __('Template Content', 'rrze-greetings'),
            'object_types' => ['greeting_template'],
            'context' => 'normal',
            'priority' => 'high',
            'show_names' => true
        ]);

        $cmb->add_field(array(
            'id' => 'rrze_greetings_template_post_content',
            'name' => __('HTML', 'rrze-greetings'),
            'desc' => __('HTML version of the template.', 'rrze-greetings'),
            'type' => 'textarea',
            'attributes' => [
                'rows' => '8',
                'required' => 'required'
            ],
            'sanitization_cb' => [$this, 'sanitizeHtml'],
            'escape_cb' => [$this, 'escapeHtml']
        ));


        $cmb->add_field(array(
            'id' => 'rrze_greetings_template_post_excerpt',
            'name' => __('Plain Text', 'rrze-greetings'),
            'desc' => __('Plain text version of the template.', 'rrze-greetings'),
            'type' => 'textarea',
            'attributes' => [
                'rows' => '8',
                'required' => 'required'
            ],
            'sanitization_cb' => 'sanitize_textarea'
        ));
    }

    public function presetFieldsList()
    {
        echo '<div class="cmb2-wrap">';

        echo '<p>' . __('The preset fields can be used directly in the template using the {{=field-name}} format.', 'rrze-greetings') . '</p>';
        echo '<p>' . __('The value of the preset fields are set dynamically and cannot be modified during the editing of the greeting card.', 'rrze-greetings') . '</p>';

        echo '<div class="cmb2-metabox cmb-field-list">';
        foreach ($this->presetFields as $field => $description) {
            echo '<div class="cmb-row">';
            echo '<div class="cmb-th">' . $field . '</div>';
            echo '<div class="cmb-td"><p class="cmb2-metabox-description">' . $description . '</p></div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    public function beforeForm($cmb_id, $object_id, $object_type, $cmb)
    {
        if ('rrze_greetings_template_custom_fields' !== $cmb_id) {
            return;
        }
        echo '<p>' . __('Custom fields can be used directly in the template using the {{=field-name}} format.', 'rrze-greetings') . '</p>';
        echo '<p>' . __('The value of the custom fields are entered manually during the editing of the greeting card.', 'rrze-greetings') . '</p>';
    }

    protected function customFields()
    {
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_template_custom_fields',
            'title' => __('Custom Fields', 'rrze-greetings'),
            'object_types' => ['greeting_template'],
            'context' => 'normal',
            'priority' => 'high',
            'show_names' => true
        ]);

        $group = $cmb->add_field([
            'id'          => 'rrze_greetings_template_fields',
            'type'        => 'group',
            'repeatable'  => true,
            'options'     => [
                'group_title'   => __('Custom Fields {#}', 'rrze-greetings'),
                'add_button'    => __('Add Another Field', 'rrze-greetings'),
                'remove_button' => __('Remove Field', 'rrze-greetings'),
                'closed'        => false,
                'sortable'      => false,
            ],
        ]);

        $cmb->add_group_field($group, [
            'id'   => 'id',
            'name' => __('Field Name', 'rrze-greetings'),
            'desc' => __('Enter the name of the field that will be used by the template. Usage: {{=field-name}}', 'rrze-greetings'),
            'type' => 'text',
            'sanitization_cb' => [$this, 'sanitizeFieldName']
        ]);

        $cmb->add_group_field($group, [
            'id'               => 'type',
            'name'             => __('Field Type', 'rrze-greetings'),
            'desc'             => __('Select a field type.', 'rrze-greetings'),
            'type'             => 'select',
            'options'          => [
                'text' => __('Text', 'rrze-greetings'),
                'textarea'   => __('Textarea', 'rrze-greetings'),
                'file'     => __('File', 'rrze-greetings'),
            ],
        ]);

        $cmb->add_group_field($group, [
            'id'   => 'name',
            'name' => __('Label Name', 'rrze-greetings'),
            'desc' => __('Enter the name of the field label (for editing purposes only).', 'rrze-greetings'),
            'type' => 'text',
            'sanitization_cb' => 'sanitize_text_field'
        ]);

        $cmb->add_group_field($group, [
            'id'   => 'desc',
            'name' => __('Description', 'rrze-greetings'),
            'desc' => __('Enter the description of the field (for editing purposes only).', 'rrze-greetings'),
            'type' => 'text',
            'sanitization_cb' => 'sanitize_text_field'
        ]);
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

        // Add dynamic fields during normal view.
        add_action('cmb2_init_hookup_rrze_greetings_post', [$this, 'addTplFields']);

        // Add dynamic fields during save process.
        add_action('cmb2_post_process_fields_rrze_greetings_post', [$this, 'addTplFields']);
    }

    public function addTplFields($cmb)
    {
        if (!$cmb->object_id()) {
            return;
        }
        $postId = $cmb->object_id();
        if (!($tplId = get_post_meta($postId, 'rrze_greetings_card_template', true))) {
            return;
        }

        $tplFields = get_post_meta($tplId, 'rrze_greetings_template_fields', true);
        $position = 2;

        foreach ($tplFields as $field) {
            if (empty($field['id'])) {
                continue;
            }

            $id = sprintf('rrze_greetings_template_%d_field_%s', $tplId, $field['id']);
            $name = $field['name'] ?? __('Field #', 'rrze-greetings');
            $desc = $field['desc'];

            switch ($field['type']) {
                case 'text':
                    $cmb->add_field(array(
                        'id' => $id,
                        'name' => $name,
                        'desc' => $desc,
                        'type' => 'text',
                        'sanitization_cb' => 'sanitize_text_field'
                    ), $position++);
                    break;
                case 'textarea':
                    $cmb->add_field(array(
                        'id' => $id,
                        'name' => $name,
                        'desc' => $desc,
                        'type' => 'wysiwyg',
                        'options' => [
                            'wpautop' => true,
                            'media_buttons' => false,
                            'textarea_name' => 'rrze_greetings_editor_' . $field['id'],
                            'textarea_rows' => get_option('default_post_edit_rows', 10),
                            'tabindex' => '',
                            'editor_css' => '',
                            'editor_class' => '',
                            'teeny' => true,
                            'dfw' => false,
                            'tinymce' => true,
                            'quicktags' => false
                        ]
                    ), $position++);
                    break;
                case 'file':
                    $cmb->add_field(array(
                        'id' => $id,
                        'name' => $name,
                        'desc' => $desc,
                        'type' => 'file',
                        'options' => [
                            'url' => false,
                        ],
                        'text' => [
                            'add_upload_file_text' => __('Add Image', 'rrze-greetings')
                        ],
                        'query_args' => [
                            'type' => [
                                'image/jpeg',
                                'image/png',
                            ],
                        ],
                        'preview_size' => 'medium'
                    ), $position++);
                    break;
                default:
                    //
            }
        }
    }

    protected function cardImageSettings()
    {
        $cmb = new_cmb2_box([
            'id' => 'rrze_greetings_imagetext',
            'title' => __('Image Settings', 'rrze-greetings'),
            'object_types' => ['greeting'],
            'context' => 'normal',
            'priority' => 'low',
            'show_names' => true,
            'show_on_cb' => [$this, 'showIfHasImage']
        ]);

        $cmb->add_field(array(
            'id' => 'rrze_greetings_print_text_on_image',
            'name' => __('Print text on image', 'rrze-greetings'),
            'desc' => __('Allows to print text on the card image.', 'rrze-greetings'),
            'type' => 'checkbox'
        ));

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
            'default' => '100',
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
            'default' => '100',
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
            'show_on_cb' => [$this, 'showIfHasTemplate']
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
            'escape_cb' => [$this, 'escapeSendDate']
        ]);

        $cmb->add_field([
            'name' => __('From Name', 'rrze-greetings'),
            'id' => 'rrze_greetings_from_name',
            'type' => 'text_medium',
            'description' => __('The name of the person sending the message.', 'rrze-greetings'),
            'attributes' =>  [
                'required' => 'required',
            ],
        ]);

        $cmb->add_field([
            'name' => __('From Email Address', 'rrze-greetings'),
            'id' => 'rrze_greetings_from_email_address',
            'type' => 'text_email',
            'description' => __('The email address that sent the message.', 'rrze-greetings'),
            'attributes' =>  [
                'required' => 'required',
            ],
        ]);

        $cmb->add_field([
            'name' => __('Reply-To Email Address', 'rrze-greetings'),
            'id' => 'rrze_greetings_replyto_email_address',
            'type' => 'text_email',
            'description' => __('The email address that will be used to reply to the message.', 'rrze-greetings'),
            'attributes' =>  [
                'required' => 'required',
            ],
        ]);
    }

    public function sanitizeFieldName($value, $field_args, $field)
    {
        if (!empty($value)) {
            $value = sanitize_title($value);
        }
        return $value;
    }

    public function showIfHasTemplate($cmb)
    {
        return (bool) get_post_meta($cmb->object_id(), 'rrze_greetings_card_template', true);
    }

    public function showIfHasImage($cmb)
    {
        return has_post_thumbnail($cmb->object_id());
    }

    public function templatesOptions($field)
    {
        $tplArry = ['' => __('None', 'rrze-greetings')];
        $templates = GreetingTemplate::getTemplates();
        foreach ($templates as $post) {
            $tplArry[$post->ID] = $post->post_title;
        }
        return $tplArry;
    }

    public function fontsOptions($field)
    {
        $fonts = Functions::getFiles(plugin()->getPath('assets/fonts'), ['ttf', 'otf'], 'fonts');
        asort($fonts);
        return $fonts;
    }

    public function sanitizeHtml($value, $field_args, $field)
    {
        return Functions::htmlEncode($value);
    }

    public function escapeHtml($value, $field_args, $field)
    {
        return Functions::htmlDecode($value);
    }

    public function escapeSendDate($value, $fieldArgs, $field)
    {
        $gmtDate = get_gmt_from_date(date('Y-m-d H:i:s', $value));
        update_post_meta($field->object_id, 'rrze_greetings_send_date_gmt', strtotime($gmtDate));
        return $value;
    }

    public function cardImagePreview($postType, $post)
    {
        if ($postType != 'greeting' || !has_post_thumbnail($post->ID)) {
            return;
        }
        $hasTemplate = get_post_meta($post->ID, 'rrze_greetings_card_template', true);
        $hasCardId = absint(get_post_meta($post->ID, 'rrze_greetings_card_id', true));
        if (!$hasTemplate || !$hasCardId) {
            return;
        }
        add_meta_box(
            'rrze_greetings_greetings_image',
            __('Card Image Preview', 'rrze-greetings'),
            [$this, 'displayCardImage'],
            'greeting',
            'side',
            'low'
        );
    }

    public function displayCardImage($post)
    {
        $cardId = absint(get_post_meta($post->ID, 'rrze_greetings_card_id', true));
        $imageUrl = wp_get_attachment_image_url($cardId, 'full');
        echo '<img class="thumbnail" src="' . $imageUrl . '" style="max-width:100%">';
    }
}
