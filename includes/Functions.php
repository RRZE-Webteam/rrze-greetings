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
}
