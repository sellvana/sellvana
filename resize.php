<?php

ini_set('display_errors', 1);
error_reporting(E_ALL | E_NOTICE);

$f = !empty($_GET['f']) ? $_GET['f'] : null;
$s = !empty($_GET['s']) ? explode('x', $_GET['s']) : array('');
$def = !empty($_GET['d']) ? $_GET['d'] : null;
$dw = $s[0];
$dh = !empty($s[1]) ? $s[1] : $s[0];
$q = !empty($_GET['q']) ? (int)$_GET['q'] : 95;
$bg = !empty($_GET['bg']) ? $_GET['bg'] : 'FFFFFF';

if (empty($f) || empty($s)
    #|| empty($_SERVER['HTTP_REFERER']) || empty($_SERVER['HTTP_HOST'])
    #|| strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])===false
    || strpos($f, '..')!==false
    || $f[0]=='/' && strpos($f, dirname(__FILE__))!==0
    #|| !is_file($_GET['f'])
) {
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
if (!is_file($f)) {
    $f = !empty($def) ? $def : __DIR__.'/media/image-not-found.jpg';
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