<?php
ini_set("display_errors", 1);
error_reporting(-1);
require_once __DIR__ . '/../tests/index.php';

$modules = BModuleRegistry::i()->getAllModules();
foreach ($modules as $modName => $mod) {
    //only for FCom modules
    if (false === strpos($modName, "FCom")) {
        continue;
    }
    $viewDir = $mod->root_dir . '/Frontend/views';
    if (!file_exists($viewDir)) {
        continue;
    }
    $targetFile = $mod->root_dir . '/i18n';
    if (!file_exists($targetFile)) {
        mkdir($targetFile);
    }
    $targetFile .= '/de.php';
    if (!file_exists($targetFile)) {
        touch($targetFile);
    }
    echo $targetFile . "\n";
    chmod($targetFile, 0777);
    BLocale::collectTranslations($viewDir, $targetFile);
}
