<?php

require_once __DIR_.'/../tests/index.php';

$modules = BModuleRegistry::getAllModules();
foreach ($modules as $modName => $mod) {
    //only for FCom modules
    if (false === strpos($modName, "FCom")) {
        continue;
    }
    $viewDir = $mod->root_dir.'/Frontend/views';
    if (!file_exists($viewDir)) {
        continue;
    }
    $targetFile = $mod->root_dir.'/i18n';
    if (!file_exists($targetFile)) {
        mkdir($targetFile);
    }
    $targetFile .='/de.php';
    if (!file_exists($targetFile)) {
        touch($targetFile);
    }
    echo $targetFile."\n";
    chmod($targetFile, 0777);
    BLocale::collectTranslations($viewDir, $targetFile);
}