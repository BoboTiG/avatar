<?php

/*
 * https://github.com/BoboTiG/avatar
 *
 * Class to generate, and print, an avatar.
 * The background color is defined by the CRC32 of the first argument (the remote address for instance).
 * The letter used is the second argument.
 *
 * To retrieve the image, call this page with the 'l' GET parameter:
 *      index2.php?l=T
 */
class Avatar
{

    private $char;
    private $color;
    private $diffs;
    private $filename;
    private $length = 80;

    /*
     * Call: new Avatar($text);
     */
    public function __construct($ip = '127.0.0.1', $letter = 'T')
    {
        $this->char = strtoupper($letter);
        if ( $this->char < 'A' || $this->char > 'Z' ) {
            $this->char = '*';
        }

        $this->color = hash('crc32', $ip);  // RGBA
        $this->filename = sprintf('%s_%s_%d.png',
            $this->color, $this->char, $this->length);

        if ( !is_file($this->filename) ) {
            $this->create_thumb();
        }
        $this->render();
    }

    /*
     * From an image resource, RGB values and a counter, this method will
     * calculate the appropriate color for a given square of the avatar.
     */
    private function color($im, $r, $g, $b, $i) {
        $i *= $this->length == 80 ? 3 : 5;
        $r = floor($r + $this->diffs[0] * $i);
        $g = floor($g + $this->diffs[1] * $i);
        $b = floor($b + $this->diffs[2] * $i);
        return imagecolorallocate($im, $r, $g, $b);
    }

    /*
     * Generate the avatar image and store it into $this->filename file.
     */
    private function create_thumb()
    {
        $s = $this->length / 4;
        list($r_, $g_, $b_, $a_) = str_split($this->color, 2);
        $r = hexdec($r_);
        $g = hexdec($g_);
        $b = hexdec($b_);
        $this->diffs = array(
            floor((255 - $r) / $this->length),
            floor((255 - $g) / $this->length),
            floor((255 - $b) / $this->length)
        );
        $i = 16;

        $im = imagecreatetruecolor($this->length, $this->length);
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, $this->length, $this->length, $color);

        // First row
        $shift = $s-1;
        imagefilledrectangle($im, 0*$s+0, 0, 1*$s-1, $shift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 1*$s+1, 0, 2*$s-1, $shift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 2*$s+1, 0, 3*$s-1, $shift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 3*$s+1, 0, 4*$s-1, $shift, $this->color($im, $r, $g, $b, --$i));
        // Second row
        $sshift = $s+1;
        $eshift = 2*$s-1;
        imagefilledrectangle($im, 3*$s+1, $sshift, 4*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 2*$s+1, $sshift, 3*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 1*$s+1, $sshift, 2*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 0*$s+0, $sshift, 1*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        // Third row
        $sshift = 2*$s+1;
        $eshift = 3*$s-1;
        imagefilledrectangle($im, 0*$s+0, $sshift, 1*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 1*$s+1, $sshift, 2*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 2*$s+1, $sshift, 3*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 3*$s+1, $sshift, 4*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        // Last row
        $sshift = 3*$s+1;
        $eshift = 4*$s-1;
        imagefilledrectangle($im, 3*$s+1, $sshift, 4*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 2*$s+1, $sshift, 3*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 1*$s+1, $sshift, 2*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));
        imagefilledrectangle($im, 0*$s+0, $sshift, 1*$s-1, $eshift, $this->color($im, $r, $g, $b, --$i));

        // Paint the letter
        $size = 50;
        $angle = 12;
        $font = './_avatars.ttf';
        $dim = imagettfbbox($size, $angle, $font, $this->char);
        $letter_width = max($dim[0], $dim[2], $dim[4], $dim[6]);
        $letter_height = min($dim[1], $dim[3], $dim[5], $dim[7]);
        $x = floor(($this->length - $letter_width) / 2); $x += 2;
        $y = floor(($this->length - $letter_height) / 2);
        // Shadow
        $rgb = 222;
        $shadow = 33;
        if ( ($r + $g + $b) / 3 < 127 ) {
            $rgb = 33;
            $shadow = 222;
        }
        $color = imagecolorallocate($im, $rgb, $rgb, $rgb);
        imagettftext($im, $size, $angle, $x-1, $y-1, $color, $font, $this->char);
        // Letter
        $color = imagecolorallocate($im, $shadow, $shadow, $shadow);
        imagettftext($im, $size, $angle, $x, $y, $color, $font, $this->char);

        imagetruecolortopalette($im, false, 255);
        imagepng($im, $this->filename);
        imagedestroy($im);
    }

    /*
     * Print an image. Use of cache.
     */
    private function render()
    {
        header('Content-Type: image/png');
        readfile($this->filename);
        exit();
    }

}

$letter = isset($_GET['l']) ? $_GET['l'] : 'T';
new Avatar($_SERVER['REMOTE_ADDR'], $letter);

?>
