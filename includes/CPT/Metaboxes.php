<?php

namespace RRZE\Greetings\CPT;

defined('ABSPATH') || exit;

use RRZE\Greetings\Functions;
use RRZE\Greetings\Template;
use RRZE\Greetings\Card\Text;
use function RRZE\Greetings\plugin;

class Metaboxes
{
    public function __construct()
    {
        require_once plugin()->getPath('vendor/cmb2') . 'init.php';
        $this->template = new Template;
    }

    public function onLoaded()
    {
        // Greeting metaboxes
        add_action('cmb2_admin_init', [$this, 'greeting']);
    }

    public function greeting()
    {
        $this->cardSettings();
        $this->imageSettings();
        $this->mailSettings();
        // Card image metabox       
        add_action('add_meta_boxes', [$this, 'cardImage']);
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
            'attributes' => [
                'required' => 'required'
            ],            
            'show_on_cb' => [$this, 'showIfTemplate'],
            'sanitization_cb' => 'sanitize_text_field'
        ]);

        $cmb->add_field(array(
            'id' => 'rrze_greetings_post_content',
            'name' => __('Text', 'rrze-greetings'),
            'desc' => __('Text that will be shown after the greeting card image.', 'rrze-greetings'),
            'type' => 'textarea',
            'attributes' => [
                'rows' => '8',
                'required' => 'required'
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
                'rows' => '3',
                'required' => 'required'
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
        return Functions::filterText($value);
    }

    public function cardImage()
    {
        $screen = get_current_screen();
        if (!$screen->base == 'post' || $screen->post_type != 'greeting') {
            return;
        }

        global $post;
        $postId = $post->ID;
        if ($post->post_type != 'greeting' || wp_is_post_revision($postId) || !has_post_thumbnail($postId)) {
            return;
        }

        $cardId = absint(get_post_meta($postId, 'rrze_greetings_card_id', true));

        wp_delete_attachment($cardId, true);

        $sourceUrl = wp_get_attachment_image_url(get_post_thumbnail_id(), 'full');
        $uploads = wp_upload_dir();
        $source = str_replace($uploads['baseurl'], $uploads['basedir'], $sourceUrl);
        $sourceExt = strtolower(pathinfo($source, PATHINFO_EXTENSION));

        $cardId = $this->uploadImage($sourceUrl, $postId, $sourceExt);
        if (is_wp_error($cardId)) {
            return;
        }

        update_post_meta($postId, 'rrze_greetings_card_id', $cardId);

        $targetUrl = wp_get_attachment_image_url($cardId, 'full');
        $this->cardImagePreview($postId, $targetUrl);
       
        $uploads = wp_upload_dir();
        $target = str_replace($uploads['baseurl'], $uploads['basedir'], $targetUrl);
        $targetExt = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        $target = rtrim($target, '.' . $targetExt);

        $text = get_post_meta($postId, 'rrze_greetings_post_excerpt', true);
        $font = get_post_meta($postId, 'rrze_greetings_imagetext_font', true);
        $color = Functions::hexToRgb(get_post_meta($postId, 'rrze_greetings_imagetext_color', true));
        $atts = [
            'width' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_width', true)),
            'startX' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_startx', true)),
            'startY' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_starty', true)),
            'align' => get_post_meta($postId, 'rrze_greetings_imagetext_align', true),
            'font' => plugin()->getPath('assets') . $font,
            'lineHeight' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_lineheight', true)),
            'size' => absint(get_post_meta($postId, 'rrze_greetings_imagetext_size', true)),
            'color' => $color
        ];

        $text = new Text($source, $target, $text, $atts);
        $text->renderToImage();

        $this->updatePost($postId, $post);
    }

    protected function cardImagePreview(int $postId, string $targetUrl)
    {
        if (get_post_meta($postId, 'rrze_greetings_card_template', true)) {
            add_meta_box(
                'rrze_greetings_greetings_image',
                __('Card Image Preview', 'rrze-greetings'),
                [$this, 'displayCardImage'],
                'greeting',
                'side',
                'low',
                [$targetUrl]
            );
        }        
    }

    public function displayCardImage($post, $callbackArgs)
    {
        $imageUrl = $callbackArgs['args'][0];
        echo '<img class="thumbnail" src="' . $imageUrl . '" style="max-width:100%">';
    }

    protected function uploadImage(string $url, int $postId, string $ext)
    {
        $attachmentId = 0;
        $file = [];
        $file['name'] = 'greetings-card-' . $postId . '.' . $ext;
        $file['tmp_name'] = download_url($url);

        if (is_wp_error($file['tmp_name'])) {
            @unlink($file['tmp_name']);
            return $file['tmp_name'];
        } else {
            $attachmentId = media_handle_sideload($file, $postId);
            if (is_wp_error($attachmentId)) {
                @unlink($file['tmp_name']);
                return $attachmentId;
            }
        }
        return $attachmentId;
    }

    protected function updatePost(int $postId, $post)
    {
        $title = (string) get_post_meta($postId, 'rrze_greetings_title', true);
        $content = (string) get_post_meta($postId, 'rrze_greetings_post_content', true);
        $logo = (string) get_post_meta($postId, 'rrze_greetings_logo', true);
        $footer = (string) get_post_meta($postId, 'rrze_greetings_footer', true);

        $data = [
            'title' => $title,
            'content' => wpautop($content),
            'logo' => $logo,
            'footer' => $footer
        ];
        $template = get_post_meta($postId, 'rrze_greetings_card_template', true);
        $content = $this->template->getContent($template, $data);
        $args = [
            'ID' => $postId,
            'post_content' => $content,
            'post_name' => md5($postId)
        ];
        wp_update_post($args);
    }
}
