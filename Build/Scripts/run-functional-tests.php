#!/usr/bin/env php
<?php

declare(strict_types=1);

$extensionRoot = dirname(__DIR__, 2);
$phpunitBinary = $extensionRoot . '/.build/bin/phpunit';
$configurationFile = $extensionRoot . '/Build/FunctionalTests.xml';

if (!is_file($phpunitBinary)) {
    fwrite(
        STDERR,
        "Missing PHPUnit binary at {$phpunitBinary}.\n"
        . "Run 'composer update' or 'composer install' first.\n"
    );
    exit(1);
}

$defaults = [
    'TYPO3_PATH_APP' => $extensionRoot . '/.build',
    'TYPO3_PATH_ROOT' => $extensionRoot . '/.build/public',
    'typo3DatabaseDriver' => 'mysqli',
    'typo3DatabaseHost' => '127.0.0.1',
    'typo3DatabasePort' => '3306',
    'typo3DatabaseName' => 't3func',
    'typo3DatabaseUsername' => 'root',
    'typo3DatabasePassword' => 'root',
];

foreach ($defaults as $name => $value) {
    if ((getenv($name) ?: '') === '') {
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

$command = [
    escapeshellarg($phpunitBinary),
    '-c',
    escapeshellarg($configurationFile),
];

foreach (array_slice($argv, 1) as $argument) {
    $command[] = escapeshellarg($argument);
}

passthru(implode(' ', $command), $exitCode);
exit($exitCode);
