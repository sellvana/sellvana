<?php

$destDir = __DIR__.'/../storage/formatted1'; // target dir

$tab = 4; // tab indent size
$files = array_merge(glob('../FCom/*/*.yml'), glob('../FCom/*/*/*.yml')); // find all YML files

foreach ($files as $file) {
    echo '<hr>'.$file.': ';
    $source = file_get_contents($file); // read the file
    $lbr = strpos($source, "\r\n") !== false ? "\r\n" : "\n"; // figure out file line break
    $lines = preg_split('#\r?\n#', $source); // split to lines
    $level = 0; // default level
    $lastIndent = 0; // starting indent
    $indents = array(0 => 0); // default indent is 0 whitespace
    foreach ($lines as $l => $line) {
        if (!preg_match('/^(\s*)((.?).*)$/', $line, $m)) { // if empty continue
            continue;
        }
        if (empty($m[2])) { // if only whitespace, trim it
            $lines[$l] = '';
            continue;
        }
        $indent = strlen(str_replace("\t", '    ', $m[1])); // get current indent
        if ($m[3] === '#') { // don't change current level in comments
            if (isset($indents[$indent])) { // attempt to find comment level
                $lines[$l] = ($indents[$indent] ? str_pad(' ', $indents[$indent]*$tab) : '') . $m[2];
            }
            continue;
        }
        if ($indent > $lastIndent) { // next level
            $level++;
            $indents[$indent] = $level;
        } elseif ($indent < $lastIndent) { // one of the previous levels
            if (!isset($indents[$indent])) { // not found
                echo ($l+1) . ' ';
                continue;
            }
            $level = $indents[$indent]; // set new level
            foreach ($indents as $ind => $j) { // clear any levels above
                if ($ind > $indent) {
                    unset($indents[$ind]);
                }
            }
        }
        $lastIndent = $indent; // remember last indent
        $lines[$l] = ($level ? str_pad(' ', $level*$tab) : '') . $m[2]; // reformat line
    }
    $output = join($lbr, $lines); // generate output file
    if ($output === $source) { // skip file creation if no changes
        continue;
    }
    $targetFile = str_replace('..', $destDir, $file); // target filename
    $targetDir = dirname($targetFile); // target dir
    if (!file_exists($targetDir)) { // create dir if missing
        mkdir($targetDir, 0777, true);
    }
    @unlink($targetFile); // remove file
    file_put_contents($targetFile, $output, 0777); // write file
}