<?php

declare(strict_types=1);
/**
 *
 * @author   xiaoguo0426
 * @contact  740644717@qq.com
 * @license  MIT
 */
define('BASE_PATH', dirname(__DIR__, 1));

require BASE_PATH . '/vendor/autoload.php';

$composer_json_file_path = BASE_PATH . '/composer.json';

$require = [];
$autoload = [];
$autoloadFiles = [];
$autoloadDev = [];
$configProviders = [];
$replaces = [];

// $splFileInfo = new SplFileInfo($composer_json_file_path);
// var_dump($splFileInfo);
// var_dump($splFileInfo->getATime());
// var_dump($splFileInfo->getBasename());
// var_dump($splFileInfo->getCTime());
// var_dump($splFileInfo->getExtension());
// var_dump($splFileInfo->getFileInfo());
// var_dump($splFileInfo->getFilename());
// var_dump($splFileInfo->getGroup());
// var_dump($splFileInfo->getInode());
// var_dump($splFileInfo->getMTime());
// var_dump($splFileInfo->getPath());
// var_dump($splFileInfo->getPathInfo());
// var_dump($splFileInfo->getPathname());
// var_dump($splFileInfo->getSize());

$component = basename(dirname($composer_json_file_path));
$composerJson = json_decode(file_get_contents($composer_json_file_path), true, 512, JSON_THROW_ON_ERROR);
if (isset($composerJson['name']) && str_starts_with($composerJson['name'], 'hyperf')) {
    $replaces[$composerJson['name']] = '*';
}

foreach ($composerJson['autoload']['files'] ?? [] as $file) {
    $autoloadFiles[] = preg_replace('#^./#', '', $file);
}
foreach ($composerJson['autoload']['psr-4'] ?? [] as $ns => $dir) {
    $value = trim($dir, '/') . '/';
    if (isset($autoload[$ns])) {
        $autoload[$ns] = [$value, ...(array) $autoload[$ns]];
    } else {
        $autoload[$ns] = $value;
    }
}
foreach ($composerJson['autoload-dev']['psr-4'] ?? [] as $ns => $dir) {
    $value = trim($dir, '/') . '/';
    if (isset($autoloadDev[$ns])) {
        $autoloadDev[$ns] = [$value, ...(array) $autoloadDev[$ns]];
    } else {
        $autoloadDev[$ns] = $value;
    }
}

if (isset($composerJson['extra']['hyperf']['config'])) {
    $configProviders = array_merge($configProviders, (array) $composerJson['extra']['hyperf']['config']);
}
var_dump($autoload);
var_dump($autoloadFiles);
var_dump($autoloadDev);
var_dump($configProviders);
var_dump($replaces);
exit;
ksort($autoload);
sort($autoloadFiles);
ksort($autoloadDev);
sort($configProviders);
ksort($replaces);

$json = json_decode(file_get_contents(__DIR__ . '/../composer.json'));
$json->autoload->files = $autoloadFiles;
$json->autoload->{'psr-4'} = $autoload;
$json->{'autoload-dev'}->{'psr-4'} = $autoloadDev;
$json->extra->hyperf->config = $configProviders;
$json->replace = $replaces;

file_put_contents(
    $composer_json_file_path,
    json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
);
