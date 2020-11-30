<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

use RRZE\Greetings\CPT\Greeting;
use RRZE\Greetings\Mail\Queue;

class Events
{
    protected $queue;
    
    public function __construct()
    {
        $this->queue = new Queue;
    }
    public function setMailQueue()
    {
        $gPosts = Greeting::getPostsToQueue();
        if (empty($gPosts)) {
            return;
        }
        foreach ($gPosts as $postId) {
            $this->queue->setQueue($postId);
        }
    }

    public function processMailQueue()
    {
        $this->queue->processQueue();
    }
}
