<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Lightweight guardrails: repositories stay on Extbase and avoid ad-hoc workspace SQL.
 * Behavioral workspace coverage lives in PostRepositoryWorkspaceFilterTest (functional).
 */
final class AllRepositoriesWorkspaceAwarenessTest extends TestCase
{
    private static function getRepoBase(): string
    {
        return dirname(__DIR__, 4) . '/Classes/Domain/Repository';
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function repositoryFileProvider(): array
    {
        $base = self::getRepoBase();
        $repos = [];
        $paths = glob($base . '/*Repository.php');
        foreach ($paths === false ? [] : $paths as $path) {
            if (basename($path) === 'PostRepositoryConstraintBuilder.php') {
                continue;
            }
            $repos[basename($path, '.php')] = [$path];
        }
        return $repos;
    }

    #[Test]
    #[DataProvider('repositoryFileProvider')]
    public function repositoryExtendsExtbaseRepository(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);
        self::assertStringContainsString('extends Repository', $content);
    }

    #[Test]
    #[DataProvider('repositoryFileProvider')]
    public function repositoryDoesNotQueryWorkspaceFieldsDirectly(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);
        self::assertDoesNotMatchRegularExpression(
            '/t3ver_wsid|t3ver_oid|t3ver_state|t3ver_stage/',
            $content,
            basename($path) . ' must not query workspace fields directly.',
        );
    }

    #[Test]
    public function postWorkspaceConstraintsLiveInDedicatedBuilder(): void
    {
        $path = self::getRepoBase() . '/PostRepositoryConstraintBuilder.php';
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertNotFalse($content);
        self::assertStringContainsString('t3ver_wsid', $content);
        self::assertStringContainsString('isBackendRequest', $content);
    }

    #[Test]
    public function postRepositoryDelegatesConstraintSetupToBuilder(): void
    {
        $content = file_get_contents(self::getRepoBase() . '/PostRepository.php');
        self::assertNotFalse($content);
        self::assertStringContainsString('PostRepositoryConstraintBuilder', $content);
    }
}
