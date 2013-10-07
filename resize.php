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

ini_set('display_errors', 1);
error_reporting(E_ALL | E_NOTICE);

$f = !empty($_GET['f']) ? $_GET['f'] : null;
$txt = !empty($_GET['t']) ? $_GET['t'] : null;

$s = !empty($_GET['s']) ? explode('x', $_GET['s']) : array('500');
$dw = $s[0];
$dh = !empty($s[1]) ? $s[1] : $s[0];
$q = !empty($_GET['q']) ? (int)$_GET['q'] : 95;
$bg = !empty($_GET['bg']) ? $_GET['bg'] : 'FFFFFF';

$out = imagecreatetruecolor($dw, $dh);
#imageantialias($out, true);

$color = imagecolorallocate($out,
    base_convert(substr($bg, 0, 2), 16, 10),
    base_convert(substr($bg, 2, 2), 16, 10),
    base_convert(substr($bg, 4, 2), 16, 10)
);
imagefill($out, 0, 0, $color);
$imgType = IMAGETYPE_PNG;

if ($f) {
    $f = str_replace("\0", '', $f);
    $f = realpath(ltrim($f, '/'));

    if (!$f || !is_file($f)) {
        $f = realpath( !empty($_GET['d']) ? $_GET['d'] : 'media/image-not-found.jpg' );
    }

    if (!$f && strpos($f, __DIR__)!==0) {
        header('Cache-Control: private, no-store, no-cache');
        header('Content-type: image/gif');
        if (!$s) {
            echo base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
            exit;
        }
        $out = imagecreate($dw, $dh);
        $color = imagecolorallocate($out, 230, 230, 230);
        imagefill($out, 0, 0, $color);
        imagegif($out, null);
        imagedestroy($out);
        exit;
    }

    $imgSize = getimagesize($f);
    $imgType = $imgSize[2];//exif_imagetype($f);//requires php_exif module
    switch ($imgType) {
    case IMAGETYPE_GIF:
        $in = imagecreatefromgif($f);
        break;
    case IMAGETYPE_JPEG:
        $in = imagecreatefromjpeg($f);
        break;
    case IMAGETYPE_PNG:
        $in = imagecreatefrompng($f);
        break;
    default:

    }
    if ($in) {
        $sw = imagesx($in);
        $sh = imagesy($in);
        $scale = $sw>$sh ? $dw/$sw : $dh/$sh;
        $dw1 = $sw*$scale;
        $dh1 = $sh*$scale;
        imagecopyresampled($out, $in, ($dw-$dw1)/2, ($dh-$dh1)/2, 0, 0, $dw1, $dh1, $sw, $sh);
    }
} elseif ($txt) {
    $c = !empty($_GET['c']) ? $_GET['c'] : '888888';
    $txtColor = imagecolorallocate($out,
        base_convert(substr($c, 0, 2), 16, 10),
        base_convert(substr($c, 2, 2), 16, 10),
        base_convert(substr($c, 4, 2), 16, 10)
    );
    $font = 5;
    $cw = imagefontwidth($font);
    $ch = imagefontheight($font);
    imagestring($out, $font, ($dw-$cw*strlen($txt))/2, ($dh-$ch)/2, $txt, $txtColor);
}

switch ($imgType) {
case IMAGETYPE_GIF:
    header('Content-type: image/gif');
    imagegif($out, null);
    break;
case IMAGETYPE_JPEG:
    header('Content-type: image/jpeg');
    imagejpeg($out, null, $q);
    break;
case IMAGETYPE_PNG:
    header('Content-type: image/png');
    imagepng($out, null);
    break;
}
