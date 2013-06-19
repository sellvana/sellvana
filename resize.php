<?php

/**
 * Parameters:
 *
 * - f=<filename>
 * - d=<default_filename>
 * - s=<width>x<height> || <width|500>
 * - q=<quality|95>
 * - bg=<background|#FFFFFF>
 */

ini_set('display_errors', 1);
error_reporting(E_ALL | E_NOTICE);

$f = !empty($_GET['f']) ? realpath(ltrim($_GET['f'], '/')) : null;
if (!$f || !is_file($f)) {
    $f = realpath( !empty($_GET['d']) ? $_GET['d'] : 'media/image-not-found.jpg' );
}
if (!empty($f)) {
    $s = !empty($_GET['s']) ? explode('x', $_GET['s']) : array('500');
    $dw = $s[0];
    $dh = !empty($s[1]) ? $s[1] : $s[0];
    $q = !empty($_GET['q']) ? (int)$_GET['q'] : 95;
    $bg = !empty($_GET['bg']) ? $_GET['bg'] : 'FFFFFF';
}
if (strpos($f, __DIR__)!==0) {
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

$sw = imagesx($in);
$sh = imagesy($in);
$out = imagecreatetruecolor($dw, $dh);
#imageantialias($out, true);

$scale = $sw>$sh ? $dw/$sw : $dh/$sh;
$dw1 = $sw*$scale;
$dh1 = $sh*$scale;

$color = imagecolorallocate($out,
    base_convert(substr($bg, 0, 2), 16, 10),
    base_convert(substr($bg, 2, 2), 16, 10),
    base_convert(substr($bg, 4, 2), 16, 10)
);
imagefill($out, 0, 0, $color);

imagecopyresampled($out, $in, ($dw-$dw1)/2, ($dh-$dh1)/2, 0, 0, $dw1, $dh1, $sw, $sh);

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
