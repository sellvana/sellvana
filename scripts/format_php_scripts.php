<?php
ini_set( "display_errors", 1 );
error_reporting( -1 );
require_once __DIR__ . '/../tests/index.php';

echo "Starting" . PHP_EOL;
$modules = BModuleRegistry::i()->getAllModules();
foreach ( $modules as $modName => $mod ) {
    //only for FCom modules
    if ( false === strpos( $modName, "FCom" ) ) {
        continue;
    }
    $dir = $mod->root_dir;
    if ( !file_exists( $dir ) ) {
        echo $modName . " has no files." . PHP_EOL;
        continue;
    }
    $targetFile = __DIR__ . '/../storage/formatted/';
    BUtil::ensureDir( $targetFile );

    chmod( $targetFile, 0777 );
    formatModulePhpFiles( $dir, realpath($targetFile) );
}

function formatModulePhpFiles( $dir, $target = null )
{
    $files = BLocale::getFilesFromDir( $dir );
    if ( empty( $files ) ) {
        return true;
    }

    require_once "php_format.php";
    if(null == $target){
        $target = $dir; //overwrite files !!!
    }
    $base = realpath(__DIR__ . '/../');

    foreach ( $files as $file ) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if($ext != "php"){
            continue;
        }
        $source = formatFile($file);
        $fileName = str_replace($base, '', $file);
        $dirName = pathinfo($fileName, PATHINFO_DIRNAME);
        mkdir(rtrim($target, '/') . '/' . trim($dirName, '/'), 0775, true);
        if(@file_put_contents($target . "/" . $fileName, $source)){
            echo "$target/$fileName formatted\n\n";
        }
    }
}

echo "Done" . PHP_EOL;