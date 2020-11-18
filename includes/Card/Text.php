<?php

namespace RRZE\Greetings\Card;

defined('ABSPATH') || exit;

use function RRZE\Greetings\plugin;

class Text
{
    /**
     * Image object
     * @var object RRZE\Greetings\Card\Image
     */
    protected $image;

    /**
     * Array of TextToImage functions
     * @var array [functions]
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
     * Text alignment (one of "left", "center", or "right")
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
    public $font = plugin()->getPath('fonts/Roboto/Roboto-Italic');

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
     * Array of available lines, with character counts and allocated words
     * @var array
     */
    protected $lines;

    /**
     * Construct
     * @param string  $imagePath The path to image
     * @param string  $text      The text
     * @param integer $width     The maximum number of characters avaiable per line
     */    
    public function __construct(string $imagePath, string $text, int $width = 80)
    {
        $this->image = new Image($imagePath);
        $this->image->setImageResource();

        $this->text = $text;
        $this->width = $width;
        $this->addLines();
    }

    /**
     * Add lines of text (from a textarea field)
     */
    public function addLines()
    {
        $lines = explode(PHP_EOL, explode(' ', $this->text));
        foreach ($lines as $line) {
            $this->lines[] = [
                'words' => explode(' ', preg_replace('/\s+/', ' ', $line))
            ];            
        }
    }

    /**
     * Render text on image
     * @param resource $image The image on which the text will be rendered
     * @return mixed \WP_Error If text is too long otherwise true
     */
    public function renderToImage($image)
    {
        // Calculate maximum line width in pixels
        $maxWidthString = implode('', array_fill(0, $this->width, 'x'));
        $maxWidthBoundingBox = imagettfbbox($this->size, 0, $this->font, $maxWidthString);
        $maxLineWidth = abs($maxWidthBoundingBox[0] - $maxWidthBoundingBox[2]);

        // Calculate each line width in pixels for alignment
        for ($j = 0; $j < count($this->lines); $j++) {
            // Fetch line
            $line = &$this->lines[$j];

            // Remove unused lines
            if (empty($line['words'])) {
                unset($this->lines[$j]);
                continue;
            }

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
                    'fontPath' => $this->fontPath,
                    'fontSize' => $this->size,
                    'colorR' => $this->color['r'],
                    'colorG' => $this->color['g'],
                    'colorB' => $this->color['b']
                ];

                // Render text onto image
                $this->textToImage[] = function (TextToImage $handler) use ($atts) {
                    extract($atts);
                    $handler->add($text)
                            ->position($offsetX, $offsetY)
                            ->font($fontSize, $fontPath)
                            ->color($colorR, $colorG, $colorB)
                            ->shadow(1, 2, [0, 0, 0]);
                };
            }
        }

        return true;
    }

    public function saveImage(string $source, string $target)
    {
        $closure = call_user_func_array(['RRZE\Greetings\Card\TextToImage', 'open'], $this->textToImage);
        TextToImage::setImage($source)->{$closure}->close($target);
    }
}
