<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

class Cron
{
    /**
     * __construct
     */
    public function __construct()
    {
        //
    }

    /**
     * onLoaded
     */    
    public function onLoaded()
    {
        add_action('wp', [$this, 'activateScheduledEvents']);
        add_filter('cron_schedules', [$this, 'customCronSchedules']);
        add_action('rrze_greetings_every10minutes_event', [$this, 'every10MinutesEvent']);
    }

    /**
     * customCronSchedules
     * Add custom cron schedules.
     * @param array $schedules Available cron schedules
     * @return array New cron schedules
     */
    public function customCronSchedules(array $schedules): array
    {
        $schedules['rrze_greetings_every10minutes'] = [
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display' => __('Every 10 minutes', 'rrze-greetings')
        ];
        return $schedules;
    }

    /**
     * activateScheduledEvents
     * Activate all scheduled events.
     */
    public function activateScheduledEvents()
    {
        if (!wp_next_scheduled('rrze_greetings_every10minutes_event')) {
            wp_schedule_event(time(), 'rrze_greetings_every10minutes', 'rrze_greetings_every10minutes_event');
        }
    }

    /**
     * every10MinutesEvent
     * Run the event every 10 minutes.
     */
    public function every10MinutesEvent()
    {
        Events::mailQueue();
        Events::mailSend();
    }

    public static function clearSchedule()
    {
        wp_clear_scheduled_hook('rrze_greetings_every10minutes_event');
    }
}