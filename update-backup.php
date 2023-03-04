<?php

$home   = __DIR__;
$logDir = "{$home}/logs";
if (!is_dir($logDir)) {
    mkdir($logDir) && chmod($logDir, 0775);
}
$now    = date("Ymd_His");
$log    = "{$logDir}/app-{$now}.log";
$addLog = function ($line) use ($log) {
    $date = date("Y-m-d H:i:s");
    return file_put_contents($log, "[{$date}] {$line}" . PHP_EOL, FILE_APPEND);
};

try {
    $mcDir = (function () use ($home) {
        $configFile = "{$home}/config.ini";
        $config     = parse_ini_file($configFile);
        $mcDir      = $config["minecraft_dir"] ?? null;
        if (!strlen($mcDir)) {
            throw new Exception("Key 'minecraft_dir' not found in '{$configFile}'");
        }
        if (!is_dir($mcDir)) {
            throw new Exception("Directory not found: '{$mcDir}'");
        }
        return $mcDir;
    })();

    $names = (function () use ($mcDir) {
        $worlds = yaml_parse_file("{$mcDir}/plugins/Multiverse-Core/worlds.yml");
        $names  = array_keys($worlds["worlds"]);
        sort($names);
        return $names;
    })();

    $tmpZip = "/tmp/worlds-{$now}.zip";
    $args   = implode(" ", $names);
    $output = [];
    $exCode = -1;
    $addLog("Starting making zip file: '{$tmpZip}'");
    chdir($mcDir) && exec("zip -r {$tmpZip} {$args}", $output, $exCode);
    if ($exCode !== 0 || !is_file($tmpZip)) {
        throw new Exception("Cannot create backup file: '{$tmpZip}'");
    }
    $addLog("Finished making zip file.");
    $dataDir = "{$home}/data";
    $zipPath = "{$dataDir}/latest.zip";
    if (!is_dir($dataDir)) {
        mkdir($dataDir) && chmod($dataDir, 0775);
    }
    if (is_file($zipPath)) {
        $addLog("Removing last backup file: '{$zipPath}'");
        unlink($zipPath);
    }
    $addLog("Rename '{$tmpZip}' to '{$zipPath}'");
    rename($tmpZip, $zipPath);
    $addLog("Successfully finished.");
    return 0;
} catch (Exception $e) {
    $addLog("[ERROR] {$e->getMessage()}");
    return 1;
}
