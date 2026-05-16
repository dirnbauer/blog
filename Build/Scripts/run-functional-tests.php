#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$extensionRoot = dirname(__DIR__, 2);
$phpunitBinary = $extensionRoot . '/.build/bin/phpunit';
$configurationFile = $extensionRoot . '/Build/FunctionalTests.xml';
$memoryLimit = getenv('TYPO3_TESTING_MEMORY_LIMIT') ?: '1G';

if (!is_file($phpunitBinary)) {
    fwrite(
        STDERR,
        "Missing PHPUnit binary at {$phpunitBinary}.\n"
        . "Run 'composer update' or 'composer install' first.\n",
    );
    exit(1);
}

$pathDefaults = [
    'TYPO3_PATH_APP' => $extensionRoot . '/.build',
    'TYPO3_PATH_ROOT' => $extensionRoot . '/.build/public',
];

$databaseEnvironmentVariables = [
    'typo3DatabaseDriver',
    'typo3DatabaseHost',
    'typo3DatabasePort',
    'typo3DatabaseName',
    'typo3DatabaseUsername',
    'typo3DatabasePassword',
    'typo3DatabaseSocket',
    'typo3DatabaseCharset',
];
$hasDatabaseOverride = false;
foreach ($databaseEnvironmentVariables as $name) {
    if ((getenv($name) ?: '') !== '') {
        $hasDatabaseOverride = true;
        break;
    }
}

if (!$hasDatabaseOverride && !extension_loaded('pdo_sqlite')) {
    fwrite(
        STDERR,
        "The default functional test database uses pdo_sqlite, but the extension is not loaded.\n"
        . "Enable pdo_sqlite or set typo3DatabaseDriver and the related typo3Database* variables for a database server.\n",
    );
    exit(1);
}

$databaseDefaults = $hasDatabaseOverride ? [
    'typo3DatabaseDriver' => 'mysqli',
    'typo3DatabaseHost' => '127.0.0.1',
    'typo3DatabasePort' => '3306',
    'typo3DatabaseName' => 't3func',
    'typo3DatabaseUsername' => 'root',
    'typo3DatabasePassword' => 'root',
    'typo3DatabaseSocket' => '',
] : [
    'typo3DatabaseDriver' => 'pdo_sqlite',
];

foreach (array_merge($pathDefaults, $databaseDefaults) as $name => $value) {
    if ((getenv($name) ?: '') === '') {
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$command = [
    escapeshellarg(PHP_BINARY),
    '-d',
    escapeshellarg('memory_limit=' . $memoryLimit),
    escapeshellarg($phpunitBinary),
    '-c',
    escapeshellarg($configurationFile),
];

foreach (array_slice($argv, 1) as $argument) {
    $command[] = escapeshellarg($argument);
}

passthru(implode(' ', $command), $exitCode);
exit($exitCode);
