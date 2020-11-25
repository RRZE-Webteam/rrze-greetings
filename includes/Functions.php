<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

class Functions
{
    public static function dateFormat(string $format, int $timestamp): string
    {
        return date_i18n($format, $timestamp);
    }

    public static function timeFormat(string $format, int $timestamp): string
    {
        return date_i18n($format, $timestamp);
    }

    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt && $dt->format($format) === $date;
    }

    public static function validateTime(string $date, string $format = 'H:i:s'): bool
    {
        return self::validateDate($date, $format);
    }

    public static function getFiles(string $path, array $ext, string $needle): array
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = [];
        foreach ($iterator as $path) {
            if ($path->isDir()) {
                continue;
            }
            $str = $path->__toString();
            $key = substr($str, strpos($str, $needle));
            if (in_array($path->getExtension(), $ext)) {
                $files[$key] = rtrim($path->getFilename(), '.' . $path->getExtension());
            }
        }
        return $files;
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);
        $length = strlen($hex);
        $rgb = [];
        $rgb[] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
        $rgb[] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
        $rgb[] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
        return $rgb;
    }

}
