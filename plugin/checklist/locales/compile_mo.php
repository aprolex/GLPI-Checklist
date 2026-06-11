<?php
// Compile .po files to .mo (GNU gettext binary) — dev utility, delete after use

// Sécurité : utilitaire en ligne de commande uniquement, jamais exécutable via le web
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

function parsePo(string $content): array {
    $entries = []; $current = null; $field = null;
    foreach (explode("\n", $content) as $line) {
        $line = rtrim($line);
        if ($line === '' || (isset($line[0]) && $line[0] === '#')) continue;
        if (preg_match('/^msgid "(.*)"$/s', $line, $m)) {
            if ($current !== null) $entries[] = $current;
            $current = ['id' => stripcslashes($m[1]), 'str' => '']; $field = 'id';
        } elseif (preg_match('/^msgstr "(.*)"$/s', $line, $m)) {
            if ($current !== null) { $current['str'] = stripcslashes($m[1]); $field = 'str'; }
        } elseif (preg_match('/^"(.*)"$/s', $line, $m)) {
            if ($current !== null && $field) $current[$field] .= stripcslashes($m[1]);
        }
    }
    if ($current !== null) $entries[] = $current;
    return $entries;
}

function generateMo(array $entries): string {
    $pairs = [];
    foreach ($entries as $e) {
        if ($e['id'] !== '' && $e['str'] !== '') $pairs[$e['id']] = $e['str'];
    }
    ksort($pairs); // GNU gettext requires lexicographic order for binary search
    $ids = array_keys($pairs); $strs = array_values($pairs); $n = count($ids);
    $strings_start = 28 + $n * 16;

    $ot = []; $tt = []; $os = ''; $ts = ''; $p = $strings_start;
    foreach ($ids  as $s) { $ot[] = [$p, strlen($s)]; $os .= $s . "\x00"; $p += strlen($s) + 1; }
    foreach ($strs as $s) { $tt[] = [$p, strlen($s)]; $ts .= $s . "\x00"; $p += strlen($s) + 1; }

    $mo = pack('V', 0x950412de) . pack('V', 0) . pack('V', $n)
        . pack('V', 28) . pack('V', 28 + $n * 8)
        . pack('V', 0)  . pack('V', 28 + $n * 16);
    foreach ($ot as [$off, $len]) $mo .= pack('VV', $len, $off);
    foreach ($tt as [$off, $len]) $mo .= pack('VV', $len, $off);
    return $mo . $os . $ts;
}

$dir = __DIR__;
foreach (['fr_FR', 'en_GB'] as $lang) {
    $po_path = "$dir/$lang.po";
    $mo_path = "$dir/$lang.mo";
    if (!file_exists($po_path)) { echo "MISSING: $po_path\n"; continue; }
    $mo = generateMo(parsePo(file_get_contents($po_path)));
    file_put_contents($mo_path, $mo);
    echo "OK: $mo_path (" . strlen($mo) . " bytes, " . count(explode("\n", file_get_contents($po_path))) . " lines po)\n";
}
