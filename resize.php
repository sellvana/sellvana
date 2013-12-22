<?php

/**
 * Parameters:
 *
 * - f=<filename>
 * - d=<default_filename>
 * - s=<width>x<height> || <width|500>
 * - q=<quality|95>
 * - bg=<background|#FFFFFF>
 * - t=text
 * - c=<text color|888888>
 */

#$t = microtime(true);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_NOTICE);

$resizer = new ImageResizer($_GET);
$resizer->render();
#echo microtime(true)-$t;

class ImageResizer
{
    const DATE_FORMAT = 'D, d M Y H:i:s \G\M\T';

    protected $cacheDir = 'media/thumb_cache';
    protected $useCache = true;

    protected $file;
    protected $default;
    protected $txt;
    protected $size;
    protected $dw;
    protected $dh;
    protected $quality;
    protected $bg;
    protected $txtColor;

    protected $mtime;
    protected $out;
    protected $outImgType = IMAGETYPE_PNG;
    protected $outFile = null;

    public function __construct($p)
    {
        $this->file = !empty($p['f']) ? $p['f'] : null;
        $this->default = !empty($_GET['d']) ? $_GET['d'] : 'media/image-not-found.jpg';
        $this->txt = !empty($p['t']) ? $p['t'] : null;

        $this->size = !empty($p['s']) ? explode('x', $p['s']) : array();
        $this->dw = !empty($this->size[0]) ? $this->size[0] : 500;
        $this->dh = !empty($this->size[1]) ? $this->size[1] : $this->dw;
        $this->quality = !empty($p['q']) ? (int)$p['q'] : 95;
        $this->bg = !empty($p['bg']) ? $p['bg'] : 'FFFFFF';
        $this->txtColor = !empty($p['c']) ? $p['c'] : '888888';

        if ($this->file) {
            $this->file = str_replace("\0", '', $this->file);
            $this->file = realpath(ltrim($this->file, '/'));

            if (!$this->file || !is_file( $this->file )) {
                $this->file = realpath( $this->default );
            }

            if (!$this->file || strpos($this->file, __DIR__) !== 0) {
                $this->outputEmptyImage();
            }

            $this->mtime = filemtime($this->file);
            $imgSize = getimagesize($this->file);
            $this->outImgType = $imgSize[2];
        }
    }

    public function render()
    {
        $this->outputHeaders();
        if ($this->tryCache()) {
            return;
        }
        $this->importImage();
        $this->exportImage();
        $this->outputFile();
    }

    protected function outputEmptyImage()
    {
        header('Cache-Control: public');
        header('Expires: '.gmdate(static::DATE_FORMAT, time()+30*86400));
        header('Content-type: image/gif');
        if (!$this->size) {
            echo base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
            exit;
        }
        $this->out = imagecreate($this->dw, $this->dh);
        $color = imagecolorallocate($this->out, 230, 230, 230);
        imagefill($this->out, 0, 0, $color);
        imagegif($this->out, null);
        imagedestroy($this->out);
        exit;
    }

    protected function allocateColor($color)
    {
        $parts = str_split($color, 2);
        $color = imagecolorallocate($this->out,
            base_convert($parts[0], 16, 10),
            base_convert($parts[1], 16, 10),
            base_convert($parts[2], 16, 10)
        );
        return $color;
    }

    protected function outputHeaders()
    {
        if ($this->mtime) {
            header('Last-Modified: ' . gmdate(static::DATE_FORMAT, $this->mtime));
        }

        switch ($this->outImgType) {
        case IMAGETYPE_GIF:
            header('Content-type: image/gif');
            break;
        case IMAGETYPE_JPEG:
            header('Content-type: image/jpeg');
            break;
        case IMAGETYPE_PNG:
            header('Content-type: image/png');
            break;
        }
    }

    protected function outputFile()
    {
        if (!$this->outFile) {
            return;
        }

        $fs = fopen($this->outFile, 'rb');
        $fd = fopen('php://output', 'wb');
        while (!feof($fs)) fwrite($fd, fread($fs, 8192));
        fclose($fs);
        fclose($fd);
    }

    protected function tryCache()
    {
        if (!$this->file || !$this->useCache) {
            return;
        }

        //$filename = preg_replace('#'.preg_quote(__DIR__).'[/\\\\](media[/\\\\])?#', '', $this->file);
        $filename = ltrim(str_replace(__DIR__, '', $this->file), '/\\');
        $this->outFile = $this->cacheDir . '/' . $this->dw . 'x' . $this->dh . '/' . $filename;
        if (file_exists($this->outFile) && filemtime($this->outFile) >= $this->mtime) {
            $this->outputFile();
            return true;
        }

        $dir = dirname($this->outFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return false;
    }

    protected function importImage()
    {
        $this->out = imagecreatetruecolor($this->dw, $this->dh);
        #imageantialias($this->out, true);
        imagefill($this->out, 0, 0, $this->allocateColor($this->bg));

        if ($this->file) {
            switch ($this->outImgType) {
            case IMAGETYPE_GIF:
                $in = imagecreatefromgif($this->file);
                break;
            case IMAGETYPE_JPEG:
                $in = imagecreatefromjpeg($this->file);
                break;
            case IMAGETYPE_PNG:
                $in = imagecreatefrompng($this->file);
                break;
            default:

            }
            if ($in) {
                $sw = imagesx($in);
                $sh = imagesy($in);
                $scale = $sw>$sh ? $this->dw/$sw : $this->dh/$sh;
                $dw1 = $sw*$scale;
                $dh1 = $sh*$scale;
                $left = ($this->dw-$dw1)/2;
                $top = ($this->dh-$dh1)/2;
                imagecopyresampled($this->out, $in, $left, $top, 0, 0, $dw1, $dh1, $sw, $sh);
            }

        } elseif ($this->txt) {
            $font = 5;
            $cw = imagefontwidth($font);
            $ch = imagefontheight($font);
            $left = ( $this->dw - $cw * strlen( $this->txt ) ) / 2;
            $top = ( $this->dh - $ch ) / 2;
            $color = $this->allocateColor($this->txtColor);
            imagestring($this->out, $font, $left, $top, $this->txt, $color);
        }
    }

    protected function exportImage()
    {
        switch ($this->outImgType) {
        case IMAGETYPE_GIF:
            imagegif($this->out, $this->outFile);
            break;
        case IMAGETYPE_JPEG:
            imagejpeg($this->out, $this->outFile, $this->quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($this->out, $this->outFile);
            break;
        }
    }
}
