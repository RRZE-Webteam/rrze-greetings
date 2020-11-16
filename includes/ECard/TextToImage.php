<?php

namespace RRZE\Greetings;

defined('ABSPATH') || exit;

/**
 * TextToImage
 * Add text to an image.
 */
class TextToImage
{
    /**
     * Array of TextToImage objects
     * @var array [\RRZE\Greetings\ECard\TextToImage]
     */
    protected $maps = [];

    /**
     * The path to image to write text onto.
     * @var null|string
     */
    protected $imagePath = null;

    /**
     * An an array of properties for creating new image if $imagePath is ''.
     * @var array
     */
    protected $create = [];

    /**
     * The text to add to image.
     * @var null|string
     */
    protected $text = null;

    /**
     * Text font (path to TTF or OTF file)
     * @var null|string
     */
    protected $font = null;

    /**
     * The font size of text
     * @var integer
     */
    protected $fontSize = 5;

    /**
     * The default shadow's X position.
     * @var integer
     */
    protected $positionX = 0;

    /**
     * The default shadow's Y position.
     * @var integer
     */
    protected $positionY = 0;

    /**
     * The default shadow's color.
     * @var array
     */
    protected $color = [255, 255, 255];

    /**
     * The shadow's color.
     * @var array
     */
    protected $shadowColor = [];

    /**
     * The shadow's X position.
     * @var null|integer
     */
    protected $shadowPositionX = null;

    /**
     * The shadow's Y position.
     * @var null|integer
     */
    protected $shadowPositionY = null;

    /**
     * TextToImage constructor.
     * @param string $imagePath    The path to image to modify.
     * @param array $create        An an array of properties for creating new image if $imagePath is ''.
     */
    public function __construct(string $imagePath, array $create = [])
    {
        $this->imagePath = $imagePath;
        $this->create = $create;
    }

    /**
     * Select image render type.
     * @param resource $image
     * @param int $fontSize
     * @param int $x
     * @param int $y
     * @param string $text
     * @param int $color
     * @param string|null $font
     */
    protected function write($image, int $fontSize, int $x, int $y, string $text, int $color, string $font = null)
    {
        if ($font !== null) {
            imagettftext($image, $fontSize, 0, $x, $y, $color, $font, $text);
        } else {
            imagestring($image, $fontSize, $x, $y, $text, $color);
        }
    }

    /**
     * Open image for modification.
     * @param $closures  Closure class. A sets of image modifications.
     * @return $this
     */
    public function open(\Closure ...$closures): self
    {
        $this->maps = array_merge($this->maps, $closures);
        return $this;
    }

    /**
     * Writes modifications to image and return new imag path.
     * @param string|null $savePath    The path to save modified image, if null, image is outputted to browser.
     * @return string
     */
    public function close(string $savePath = null)
    {
        if (count($this->create) != 0) {
            $ext   = $this->create[2];
            $image = @imagecreate($this->create[0], $this->create[1]);
            imagecolorallocate($image, ...$this->create[3]);
        } else {
            if (!is_readable($this->imagePath)) {
                return new \WP_Error('image_does_not_exist', __('Image to write text does not exist.', 'rrze-greetings'));
              }

            $ext = strtolower(pathinfo($this->imagePath, PATHINFO_EXTENSION));

            if ($ext == 'jpg' || $ext == 'jpeg') {
                $image = imagecreatefromjpeg($this->imagePath);
            } elseif ($ext == 'png') {
                $image = imagecreatefrompng($this->imagePath);
            } elseif ($ext == 'gif') {
                $image = imagecreatefromgif($this->imagePath);
            } else {
                return new \WP_Error('path_ext_not supported', sprintf(__('%s not supported', 'rrze-greetings'), $ext));
            }
        }

        foreach ($this->maps as $closure) {
            $closure($map = new self(''));

            if ($map->font !== null && !is_readable($map->font)) {
                return new \WP_Error('font_not_found', sprintf(__('Font "%s" not found.', 'rrze-greetings'), $map->font));
            }

            $newColor = imagecolorallocate($image, $map->color[0], $map->color[1], $map->color[2]);

            if (count($map->shadowColor) != 0) {
                $shadow = imagecolorallocate($image, $map->shadowColor[0], $map->shadowColor[1], $map->shadowColor[2]);
                $this->write(
                    $image,
                    $map->fontSize,
                    $map->shadowPositionX + $map->positionX,
                    $map->shadowPositionY + $map->positionY,
                    $map->text,
                    $shadow ?? $newColor,
                    $map->font
                );
            }

            $this->write($image, $map->fontSize, $map->positionX, $map->positionY, $map->text, $newColor, $map->font);
        }

        $saveAs = $savePath ? "$savePath.{$ext}" : null;

        if ($ext == 'jpg' || $ext == 'jpeg') {
            imagejpeg($image, $saveAs);
        } elseif ($ext == 'png') {
            imagepng($image, $saveAs);
        } elseif ($ext == 'gif') {
            imagegif($image, $saveAs);
        }
        imagedestroy($image);

        return $savePath;
    }

    /**
     * Set up a new image for modification.
     * @param string $imagePath    The image path.
     * @return static
     */
    public static function setImage(string $imagePath): self
    {
        return new self($imagePath);
    }

    /**
     * Create a new image for modification.
     * @param int $width       The width of the image.
     * @param int $height      The height of the image.
     * @param string $ext      The image format e.g png, jpeg or gif
     * @param array $bgColor   An array [r, g, b] of image background color.
     * @return self
     */
    public static function createImage(int $width, int $height, string $ext = 'png', array $bgColor = [255, 255, 255]): self
    {
        return new self('', [$width, $height, $ext, $bgColor]);
    }

    /**
     * Standard Set.
     * @param string $text                 Text to add.
     * @param int $positionX               Text X position.
     * @param int $positionY               Text Y position.
     * @param array $color                 Text color [r, g, b]
     * @param string|null $font            Text font file path.
     * @param int $fontSize                Text font size.
     * @param int|null $shadowPositionX    Text shadow position x.
     * @param int|null $shadowPositionY    Text shadow position y.
     * @param array $shadowColor           Text shadow color.
     * @return $this                       \RRZE\Greetings\ECard\TextToImage
     */
    public function set(
        string $text,
        int $positionX = 0,
        int $positionY = 0,
        array $color = [255, 255, 255],
        string $font = null,
        int $fontSize = 5,
        int $shadowPositionX = null,
        int $shadowPositionY = null,
        array $shadowColor = [0, 0, 0]
    ): self {
        $this->text = $text;
        $this->positionX = $positionX;
        $this->positionY = $positionY;
        $this->color = $color;
        $this->fontSize = $fontSize;
        $this->font = $font;
        $this->shadowPositionX = $shadowPositionX;
        $this->shadowPositionY = $shadowPositionY;
        $this->shadowColor = $shadowColor;

        return $this;
    }

    /**
     * Add a text.
     * @param string $text  The text to add.
     * @return $this
     */
    public function add(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Set the position of added text.
     * @param int $x    The X position.
     * @param int $y    The Y position.
     * @return $this
     */
    public function position(int $x, int $y): self
    {
        $this->positionX = $x;
        $this->positionY = $y;
        return $this;
    }

    /**
     * Set a font file and size for a text.
     * @param int $size             The text font size.
     * @param string|null $path     The text font file path.
     * @return $this
     */
    public function font(int $size, string $path = null): self
    {
        $this->fontSize = $size;
        $this->font = $path;
        return $this;
    }

    /**
     * Set a color for a text.
     * @param int $r    Red
     * @param int $g    Green
     * @param int $b    Blue.
     * @return $this
     */
    public function color(int $r = 255, int $g = 255, int $b = 255): self
    {
        $this->color = [$r, $g, $b];
        return $this;
    }

    /**
     * Set a shadow for specified text.
     * @param int|null $positionX  The shadow x position.
     * @param int|null $positionY  The shadow y position.
     * @param array $color         Array [r, g, b] or the shadow color.
     * @return $this
     */
    public function shadow(int $positionX = null, int $positionY = null, array $color = []): self
    {
        $this->shadowPositionX = $positionX;
        $this->shadowPositionY = $positionY;
        $this->shadowColor = $color;
        return $this;
    }
}
