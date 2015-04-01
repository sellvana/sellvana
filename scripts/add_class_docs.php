<?php

if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    exit;
}

ini_set("display_errors", 1);
error_reporting(-1);

$rootDir = dirname(__DIR__);
require_once $rootDir . '/core/FCom/Core/Main.php';
BConfig::i()->set( 'fs/root_dir', $rootDir );
FCom_Core_Main::i()->init( 'FCom_Core' );
BModuleRegistry::i()->bootstrap();

$files = BUtil::i()->globRecursive($rootDir . '/core/*', '*.php');

echo "<pre>";
//var_dump($files);

foreach ($files as $file) {
    $source = file_get_contents($file);
    $tokens = token_get_all($source);
    for ($i = 0, $l = sizeof($tokens); $i < $l; $i++) {
        if (is_array($tokens[$i]) && $tokens[$i][0] === T_CLASS) {
            break;
        }
    }
    if ($i === $l) {
        continue;
    }
    $className = $tokens[$i+2][1];
    $i--;
    if (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
        $i--;
    }
    $declared = [];
    if (is_array($tokens[$i]) && $tokens[$i][0] === T_DOC_COMMENT && strpos($tokens[$i][1], 'Copyright') === false) {
        $docBlockIdx = $i;
        $origDocBlock = $tokens[$i][1];
        $docBlock = preg_replace('#^\s*\*/\s*$#m', '', $origDocBlock);
        preg_match_all('/^\s*\*\s+@(var|property)\s+([A-Za-z0-9_]+)\s+\$([A-Za-z0-9_]+)\s*$/m', $docBlock, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            if ($m[2] === $m[3]) {
                $declared[$m[2]] = $m[3];
            }
        }
    } else {
        $l += 2;
        $docBlockIdx = $i + 2;
        $docBlock = '/**
 * Class ' . $className . '
 *
';
        array_splice($tokens, $i + 1, 0, [[T_WHITESPACE, "\n\n", 0], [T_DOC_COMMENT, $docBlock, 0]]);
        #var_dump($tokens); exit;
    }
    if (!preg_match_all('/\$this->(FCom_[A-Za-z0-9_]+)/', $source, $matches, PREG_SET_ORDER)) {
        continue;
    }
    $toDeclare = [];
    foreach ($matches as $m) {
        if (!empty($declared[$m[1]])) {
            continue;
        }
        $toDeclare[$m[1]] = $m[1];
    }
    if ($docBlock === $origDocBlock) {
        continue;
    }
    sort($toDeclare);
    foreach ($toDeclare as $cls) {
        $docBlock .= ' * @property ' . $cls . ' $' . $cls . "\n";
    }
    $docBlock .= ' */';
    $tokens[$docBlockIdx][1] = $docBlock;
    //var_dump($origDocBlock, $docBlock);

    $result = '';
    for ($i = 0; $i < $l; $i++) {
        $result .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
    }
    echo htmlspecialchars($result) . "<hr>";
    file_put_contents($file, $result);
}
