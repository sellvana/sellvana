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
    $targetFile = '/../storage/formatted/';
    BUtil::ensureDir( $targetFile );

    echo $targetFile . "\n";
    chmod( $targetFile, 0777 );
    formatModulePhpFiles( $dir, $targetFile );
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
    foreach ( $files as $file ) {
        $source = formatFile($file);

        if(@file_put_contents($target . "/" . $file, $source)){
            echo "$target/$file formatted\n";
        }
    }
}

echo "Done" . PHP_EOL;

/*


         //import translation from $targetFile

         self::$_tr = '';
         self::addTranslationsFile($targetFile);
         $translations = self::getTranslations();

         //find undefined translations
         foreach ($keys as $key => $v) {
             if(isset($translations[$key])) {
                 unset($keys[$key]);
             }
         }
         //add undefined translation to $targetFile
         $newTranslations = array();
         if ($translations) {
             foreach($translations as $trKey => $tr){
                 list(,$newTranslations[$trKey]) = each($tr);
             }
         }
         $newTranslations = array_merge($newTranslations, $keys);

         $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
         switch ($ext) {
             case 'php':
                 static::saveToPHP($targetFile, $newTranslations);
                 break;
             case 'csv':
                 static::saveToCSV($targetFile, $newTranslations);
                 break;
             case 'json':
                 static::saveToJSON($targetFile, $newTranslations);
                 break;
             case 'po':
                 static::saveToJSON($targetFile, $newTranslations);
                 break;
             default:
                 throw new Exception("Undefined format of translation targetFile. Possible formats are: json/csv/php");
         }

     }

 */