<?php

namespace RRZE\Greetings;

use RRZE\Greetings\CPT\GreetingTemplate;

defined('ABSPATH') || exit;

/**
 * [Template description]
 */
class Template
{
    /**
     * [__construct description]
     */
    public function __construct()
    {
        //
    }

    /**
     * [getContent description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @param  bool   $file     [description]
     * @return string           [description]
     */
    public function getContent($template = '', $data = [], $file = true)
    {
        return $this->parseContent($template, $data, $file);
    }

    /**
     * [parseContent description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @param  bool   $file     [description]
     * @return string           [description]
     */
    protected function parseContent($template, $data, $file)
    {
        $content = '';
        if ($file) {
            $content = self::getTemplate($template);
        } else {
            $content = $template;
        }

        if (empty($content) || empty($data)) {
            return '';
        }
      
        $parser = new Parser();
        return $parser->parse($content, $data);
    }

    /**
     * [getTemplate description]
     * @param  string $template [description]
     * @return string           [description]
     */
    protected static function getTemplate($template)
    {
        $content = '';
        $templateFile = sprintf('%s%s', plugin()->getDirectory(), $template);       
        if (is_readable($templateFile)) {
            ob_start();
            include($templateFile);
            $content = ob_get_contents();
            @ob_end_clean();            
        }
        return $content;
    }

    public static function import(string $input): string
    {
        $htmlTpl = self::getTemplate('templates/' . $input . '.html');
        $txtTpl = self::getTemplate('templates/' . $input . '.txt');
        if (!$htmlTpl || !$txtTpl) {
            return '';
        }

        switch ($input) {
            case 'christmas-de_DE':
                $content = Functions::htmlEncode($htmlTpl);
                $excerpt = $txtTpl;
                $args = [
                    'post_title' => __('Christmas (de_DE)', 'rrze-greetings'),
                    'post_content' => $content,
                    'post_excerpt' => $excerpt,
                    'post_type' => GreetingTemplate::getPostType(),
                    'post_status' => 'publish',
                    'post_author' => 1,
                ];
                if (!($tplId = wp_insert_post($args))) {
                    return '';
                }
                add_post_meta($tplId, 'rrze_greetings_template_post_content', $content, true);
                add_post_meta($tplId, 'rrze_greetings_template_post_excerpt', $excerpt, true);
                $fields = [
                    [
                        'id' => 'title',
                        'type' => 'text',
                        'name' => 'Titel',
                        'desc' => 'Der Titel des Inhalts der Grußkarte.'                      
                    ],
                    [
                        'id' => 'content',
                        'type' => 'textarea',
                        'name' => 'Text',
                        'desc' => 'Text, der nach dem Grußkartenbild angezeigt wird.'                        
                    ],
                    [
                        'id' => 'logo',
                        'type' => 'file',
                        'name' => 'Logo',
                        'desc' => 'Das Bild des Website-Logos, das auf der Grußkarte angezeigt wird.'                        
                    ]
                ];
                add_post_meta($tplId, 'rrze_greetings_template_fields', $fields, true);
            break;
            default:
                //
        }
        return '';
    }
}
