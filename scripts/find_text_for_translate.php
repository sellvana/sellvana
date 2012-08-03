<?php

require_once __DIR_.'/../tests/index.php';

$modules = BModuleRegistry::getAllModules();
$phrases = array();
foreach ($modules as $modName => $mod) {
    //only for FCom modules
    if (false === strpos($modName, "FCom")) {
        continue;
    }
    $viewDir = $mod->root_dir.'/Frontend/views';
    if (!file_exists($viewDir)) {
        continue;
    }

    $files = BUtil::globRecursive($viewDir.'/*.php');
    foreach($files as $filename) {
        $tmp = scripts_ftft_extract_phrases($filename);
        $phrases = array_merge($phrases, $tmp);
    }
}

file_put_contents("/tmp/text_for_translate.json", json_encode($phrases));


function scripts_ftft_extract_phrases($filename)
{
    $lines = file($filename);
    $phrases = array();
    $count = 1;
    foreach ($lines as $line) {
        $matches = array();
        preg_match_all("/\b[A-Z][a-z][A-Za-z0-9 -]*\b/", $line, $matches);
        if (!empty($matches[0])) {
            foreach ($matches as $mData) {
                foreach ($mData as $m) {
                    $replace = str_replace($m, '<?= BLocale::_("'.$m.'"); ?>', $line);
                    $phrases[] = array($filename, $count, $m, $replace, $line);
                }
            }
        }
        $count++;
    }
    return $phrases;
}