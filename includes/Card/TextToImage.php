<?php

namespace RRZE\Greetings\Card;

defined('ABSPATH') || exit;

/**
 * TextToImage
 * Add text to an image.
 */
class TextToImage
{
    /**
     * Image resource
     * @var resource
     */
    protected $image;

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
     * Array of TextToImage objects
     * @var array [\RRZE\Greetings\Card\TextToImage]
     */
    protected $maps = [];

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
     * The default font color (RGB).
     * @var array
     */
    protected $color = [255, 255, 255];

    /**
     * The default X position.
     * @var integer
     */
    protected $positionX = 0;

    /**
     * The default Y position.
     * @var integer
     */
    protected $positionY = 0;

    /**
     * The shadow's color (RGB).
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
     * @param string $imagePath The path to image to modify.
     */
    public function __construct(string $imagePath)
    {
        $this->imagePath = $imagePath;
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
        if (!is_readable($this->imagePath)) {
            throw new \RuntimeException('Image to write text does not exist.');
        }

        $this->ext = strtolower(pathinfo($this->imagePath, PATHINFO_EXTENSION));

        if ($this->ext == 'jpg' || $this->ext == 'jpeg') {
            $this->image = imagecreatefromjpeg($this->imagePath);
        } elseif ($this->ext == 'png') {
            $this->image = imagecreatefrompng($this->imagePath);
        } elseif ($this->ext == 'gif') {
            $this->image = imagecreatefromgif($this->imagePath);
        } else {
            throw new \RuntimeException("{$this->ext} not supported.");
        }

        foreach ($this->maps as $closure) {
            $closure($map = new self(''));

            if ($map->font !== null && !is_readable($map->font)) {
                throw new \RuntimeException("Font \"{$map->font}\" not found.");
            }

            $newColor = imagecolorallocate($this->image, $map->color[0], $map->color[1], $map->color[2]);

            if (!empty($map->shadowColor)) {
                $shadow = imagecolorallocate($this->image, $map->shadowColor[0], $map->shadowColor[1], $map->shadowColor[2]);
                $this->write(
                    $this->image,
                    $map->fontSize,
                    $map->shadowPositionX + $map->positionX,
                    $map->shadowPositionY + $map->positionY,
                    $map->text,
                    $shadow ?? $newColor,
                    $map->font
                );
            }

            $this->write($this->image, $map->fontSize, $map->positionX, $map->positionY, $map->text, $newColor, $map->font);
        }

        $saveAs = $savePath ? "$savePath.{$this->ext}" : null;

        if ($this->ext == 'jpg' || $this->ext == 'jpeg') {
            imagejpeg($this->image, $saveAs);
        } elseif ($this->ext == 'png') {
            imagepng($this->image, $saveAs);
        } elseif ($this->ext == 'gif') {
            imagegif($this->image, $saveAs);
        }
        imagedestroy($this->image);

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
     * @return $this                       \RRZE\Greetings\Card\TextToImage
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
        array $shadowColor = []
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
