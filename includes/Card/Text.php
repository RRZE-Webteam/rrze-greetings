<?php

namespace RRZE\Greetings\Card;

defined('ABSPATH') || exit;

use function RRZE\Greetings\plugin;

class Text
{
    /**
     * Source image path
     * @var string
     */
    public $source = '';

    /**
     * Target image path
     * @var string
     */
    public $target = '';

    /**
     * Image object
     * @var object RRZE\Greetings\Card\Image
     */
    protected $image;

    /**
     * TextToImage object
     * @var obbject RRZE\Greetings\Card\TextToImage
     */    
    protected $textToImage;

    /**
     * Text
     * @var string
     */
    public $text = 'Greetings!';

    /**
     * Width (max number of characters per line)
     * @var integer
     */
    public $width = 80;

    /**
     * X coordinate offset from which text will be positioned relative to source image
     * @var integer
     */
    public $startX = 0;

    /**
     * Y coordinate offset from which text will be positioned relative to source image
     * @var integer
     */
    public $startY = 0;

    /**
     * Text alignment ("left", "center", or "right")
     * @var string
     */
    public $align = 'left';

    /**
     * Text color (RGB)
     * @var array
     */
    public $color = [0, 0, 0];

    /**
     * Text font (path to TTF or OTF file)
     * @var string
     */
    public $font = '';

    /**
     * Text line height (pts)
     * @var integer
     */
    public $lineHeight = 24;

    /**
     * Text size (pts)
     * @var integer
     */
    public $size = 16;

    /**
     * The shadow's X position.
     * @var null|integer
     */
    protected $shadowX = null;

    /**
     * The shadow's Y position.
     * @var null|integer
     */
    protected $shadowY = null;

    /**
     * The shadow's color (RGB).
     * @var array
     */
    protected $shadowColor = [];

    /**
     * Array of available lines, with character counts and allocated words
     * @var array
     */
    protected $lines;


    /**
     * Construct
     * @param string  $source The path to source image
     * @param string  $target The path to target image
     * @param string  $text   The text
     * @param array   $atts   The text attributes
     */    
    public function __construct(string $source, string $target, string $text, $atts)
    {
        $this->source = $source;
        $this->target = $target;

        $this->image = new Image($this->source);
        $this->image->setImageResource();

        $this->textToImage = TextToImage::setImage($this->source);

        $this->text = $text;

        extract($atts);
        $this->width = isset($width) && absint($width) ? absint($width) : $this->width;
        $this->startX = isset($startX) ? absint($startX) : $this->startX;
        $this->startY = isset($startY) ? absint($startY) : $this->startY;
        $this->align = !empty($align) ? (string) $align : $this->align;
        $this->color = !empty($color) ? (array) $color : $this->color;
        $this->font = !empty($font) && is_readable($font) ? $font : plugin()->getPath('assets/fonts/Roboto') . 'Roboto-LightItalic.ttf';
        $this->lineHeight = isset($lineHeight) && absint($lineHeight) ? absint($lineHeight) : $this->lineHeight;
        $this->size = isset($size) && absint($size) ? absint($size) : $this->size;
        $this->shadowX = isset($shadowX) ? absint($shadowX) : $this->shadowX;
        $this->shadowY = isset($shadowY) ? absint($shadowY) : $this->shadowY;
        $this->shadowColor = !empty($shadowColor) && is_array($shadowColor) ? $shadowColor : $this->shadowColor;

        $this->addLines();
    }

    /**
     * Add lines of text (from a textarea field)
     */
    public function addLines()
    {
        $lines = array_filter(explode(PHP_EOL, $this->text));
        foreach ($lines as $line) {
            $this->lines[] = [
                'words' => explode(' ', preg_replace('/\s+/', ' ', $line))
            ];            
        }
    }

    /**
     * Render text on image
     */
    public function renderToImage()
    {
        // Calculate maximum line width in pixels
        $maxWidthString = implode('', array_fill(0, $this->width, 'x'));
        $maxWidthBoundingBox = imagettfbbox($this->size, 0, $this->font, $maxWidthString);
        $maxLineWidth = abs($maxWidthBoundingBox[0] - $maxWidthBoundingBox[2]);

        if (empty($this->lines)) {
            return;
        }
        
        // Calculate each line width in pixels for alignment
        for ($j = 0; $j < count($this->lines); $j++) {
            // Fetch line
            $line = &$this->lines[$j];

            // Calculate width
            $lineText = implode(' ', $line['words']);
            $lineBoundingBox = imagettfbbox($this->size, 0, $this->font, $lineText);
            $line['width'] = abs($lineBoundingBox[0] - $lineBoundingBox[2]);
            $line['text'] = $lineText;
        }

        // Calculate line offsets
        for ($i = 0; $i < count($this->lines); $i++) {
            // Fetch line
            if (array_key_exists($i, $this->lines)) {
                $line = &$this->lines[$i];

                // Calculate line width in pixels
                $lineBoundingBox = imagettfbbox($this->size, 0, $this->font, $line['text']);
                $lineWidth = abs($lineBoundingBox[0] - $lineBoundingBox[2]);

                // Calculate line X,Y offsets in pixels
                switch ($this->align) {
                    case 'left':
                        $offsetX = $this->startX;
                        $offsetY = $this->startY + $this->lineHeight + ($this->lineHeight * $i);
                        break;
                    case 'center':
                        $imageWidth = $this->image->getWidth();
                        $offsetX = (($maxLineWidth - $lineWidth) / 2) + $this->startX;
                        $offsetY = $this->startY + $this->lineHeight + ($this->lineHeight * $i);
                        break;
                    case 'right':
                        $imageWidth = $this->image->getWidth();
                        $offsetX = $imageWidth - $line['width'] - $this->startX;
                        $offsetY = $this->startY + $this->lineHeight + ($this->lineHeight * $i);
                        break;
                }

                $atts = [
                    'text' => $line['text'],
                    'offsetX' => $offsetX,
                    'offsetY' => $offsetY,
                    'font' => $this->font,
                    'fontSize' => $this->size,
                    'colorR' => $this->color[0],
                    'colorG' => $this->color[1],
                    'colorB' => $this->color[2],
                    'shadowX' => $this->shadowX,
                    'shadowY' => $this->shadowY,
                    'shadowColor' => $this->shadowColor
                ];

                // Render text onto image
                $this->textToImage->open(function (TextToImage $handler) use ($atts) {
                    extract($atts);
                    $handler->add($text)
                            ->position($offsetX, $offsetY)
                            ->font($fontSize, $font)
                            ->color($colorR, $colorG, $colorB)
                            ->shadow($shadowX, $shadowY, $shadowColor);
                });
            }
        }

        $this->textToImage->close($this->target);
    }
}
