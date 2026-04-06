#!/usr/bin/env php
<?php

declare(strict_types=1);

$extensionRoot = dirname(__DIR__, 2);
$phpunitBinary = $extensionRoot . '/.build/bin/phpunit';
$configurationFile = $extensionRoot . '/Build/FunctionalTests.xml';

if ((getenv('DDEV_HOSTNAME') ?: '') !== '') {
    $defaults = [
        'typo3DatabaseHost' => 'db',
        'typo3DatabaseName' => 'db',
        'typo3DatabaseUsername' => 'root',
        'typo3DatabasePassword' => 'root',
        'typo3DatabasePort' => '3306',
        'typo3DatabaseDriver' => 'mysqli',
    ];

    foreach ($defaults as $name => $value) {
        if ((getenv($name) ?: '') === '') {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
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
