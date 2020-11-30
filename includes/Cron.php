<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

class Cron
{
    /**
     * Options
     * @var object
     */
    protected $options;

    protected $events;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->options = (object) Settings::getOptions();
        $this->events = new Events;
    }

    /**
     * onLoaded
     */
    public function onLoaded()
    {
        add_action('wp', [$this, 'activateScheduledEvents']);
        add_filter('cron_schedules', [$this, 'customCronSchedules']);
        add_action('rrze_greetings_every5minutes_event', [$this, 'every5MinutesEvent']);
    }

    /**
     * customCronSchedules
     * Add custom cron schedules.
     * @param array $schedules Available cron schedules
     * @return array New cron schedules
     */
    public function customCronSchedules(array $schedules): array
    {
        $schedules['rrze_greetings_every5minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __('Every 5 minutes', 'rrze-greetings')
        ];
        return $schedules;
    }

    /**
     * activateScheduledEvents
     * Activate all scheduled events.
     */
    public function activateScheduledEvents()
    {
        if (!wp_next_scheduled('rrze_greetings_every5minutes_event')) {
            wp_schedule_event(time(), 'rrze_greetings_every5minutes', 'rrze_greetings_every5minutes_event');
        }
    }

    /**
     * every5MinutesEvent
     * Run the event every 5 minutes.
     */
    public function every5MinutesEvent()
    {
        $this->events->setMailQueue();
        $this->events->processMailQueue();
    }

    public static function clearSchedule()
    {
        wp_clear_scheduled_hook('rrze_greetings_every5minutes_event');
    }
}
