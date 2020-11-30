<?php

namespace RRZE\Greetings\Card;

defined('ABSPATH') || exit;

/**
 * Image
 */
class Image
{
    /**
     * Image resource
     * @var resource
     */
    protected $resource;

    /**
     * Path to image.
     * @var null|string
     */
    protected $imagePath = null;

    /**
     * Image file extension.
     * @var null|string
     */
    protected $ext = null;

    /**
     * TextToImage constructor.
     * @param string $imagePath The path to image.
     */
    public function __construct(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * Set image resource.
     */
    public function setImageResource()
    {
        if (!is_readable($this->imagePath)) {
            return new \WP_Error('image_does_not_exist', __('Image to write text does not exist.', 'rrze-greetings'));
        }

        $this->ext = strtolower(pathinfo($this->imagePath, PATHINFO_EXTENSION));

        if ($this->ext == 'jpg' || $this->ext == 'jpeg') {
            $this->resource = imagecreatefromjpeg($this->imagePath);
        } elseif ($this->ext == 'png') {
            $this->resource = imagecreatefrompng($this->imagePath);
        } elseif ($this->ext == 'gif') {
            $this->resource = imagecreatefromgif($this->imagePath);
        } else {
            return new \WP_Error('image_ext_not_supported', sprintf(__('%s not supported', 'rrze-greetings'), $this->ext));
        }
    }

    public function getWidth()
    {
        return imagesx($this->resource);
    }

    public function getHeight()
    {
        return imagesy($this->resource);
    }    
}
