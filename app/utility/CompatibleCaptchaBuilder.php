<?php

/**
 * gregwar/captcha 1.1.9 adapter for PHP 7.4–8.2.
 * Retains the upstream rendering algorithm and fingerprint sequence.
 * writePhrase derives from Gregwar/Captcha (MIT; see vendor/gregwar/captcha/LICENSE).
 */
class CompatibleCaptchaBuilder extends \Gregwar\Captcha\CaptchaBuilder
{
    // Upstream build() assigns this previously undeclared property.
    public $background;

    public static function create($phrase = null)
    {
        return new static($phrase);
    }

    protected function rand($min, $max)
    {
        return parent::rand((int) $min, (int) $max);
    }

    protected function getCol($image, $x, $y, $background)
    {
        return parent::getCol($image, (int) $x, (int) $y, $background);
    }

    protected function writePhrase($image, $phrase, $font, $width, $height)
    {
        $length = mb_strlen($phrase);
        if ($length === 0) {
            return \imagecolorallocate($image, 0, 0, 0);
        }

        // Gets the text size and start position
        $size = $width / $length - $this->rand(0, 3) - 1;
        $box = \imagettfbbox($size, 0, $font, $phrase);
        $textWidth = $box[2] - $box[0];
        $textHeight = $box[1] - $box[7];
        $x = ($width - $textWidth) / 2;
        $y = ($height - $textHeight) / 2 + $size;

        if (!$this->textColor) {
            $textColor = array($this->rand(0, 150), $this->rand(0, 150), $this->rand(0, 150));
        } else {
            $textColor = $this->textColor;
        }
        $col = \imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);

        // Write the letters one by one, with random angle
        for ($i=0; $i<$length; $i++) {
            $symbol = mb_substr($phrase, $i, 1);
            $box = \imagettfbbox($size, 0, $font, $symbol);
            $w = $box[2] - $box[0];
            $angle = $this->rand(-$this->maxAngle, $this->maxAngle);
            $offset = $this->rand(-$this->maxOffset, $this->maxOffset);
            \imagettftext($image, $size, $angle, (int) $x, (int) ($y + $offset), $col, $font, $symbol);
            $x += $w;
        }

        return $col;
    }

}
