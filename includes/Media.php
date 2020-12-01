<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

class Media
{
    /**
     * Add Media Library's hooks
     */
    public function __construct()
    {
        add_filter('ajax_query_attachments_args', [$this, 'hideMediaOverlayView']);
        add_action('pre_get_posts', [$this, 'hideMediaListView']);
    }

    /**
     * Hide files from the Media Library's overlay (modal) view
     * if they have the meta key 'rrze_greetings_card' set.
     * @param array $args An array of query variables.
     */
    public function hideMediaOverlayView($args)
    {
        if (!is_admin()) {
            return;
        }

        $args['meta_query'] = [
            [
                'key'     => 'rrze_greetings_hide_file',
                'compare' => 'NOT EXISTS',
            ]
        ];

        return $args;
    }

    /**
     * Hide files from the Media Library's list view
     * if they have the meta key 'rrze_greetings_card' set.
     * @param WP_Query $query \WP_Query instance (passed by reference).
     */
    public function hideMediaListView($query)
    {
        if (!is_admin()) {
            return;
        }

        if (!$query->is_main_query()) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || 'upload' !== $screen->id || 'attachment' !== $screen->post_type) {
            return;
        }

        $query->set(
            'meta_query',
            [
                [
                    'key'     => 'rrze_greetings_hide_file',
                    'compare' => 'NOT EXISTS',
                ]
            ]
        );

        return;
    }

    /**
     * Update file metadata.
     * @param integer $fileId File Id
     * @return void
     */
    public static function updateFileMetadata(int $fileId)
    {
        $upload_dir = wp_upload_dir();
        $upload_basedir = $upload_dir['basedir'];
        $data = wp_get_attachment_metadata($fileId);
        $originalFileName = $data['file'] ?? '';
        $newData = $data;

        $allSizesFilenames = wp_list_pluck($data['sizes'], 'file');

        foreach ($allSizesFilenames as $size => $filename) {
            $p = strrpos($originalFileName, '/');
            $path = ($p !== false) ? substr($originalFileName, 0, $p + 1) : '';
            $file = $upload_basedir . '/' . $path . $filename;

            if (file_exists($file)) {
                @unlink($file);
            }
            unset($newData['sizes'][$size]);
        }

        if (count($newData) === 1 && empty($newData['sizes'])) {
            $newData = [];
        }
        wp_update_attachment_metadata($fileId, $newData);
    }
}
