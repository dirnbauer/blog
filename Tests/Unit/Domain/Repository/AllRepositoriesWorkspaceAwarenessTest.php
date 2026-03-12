<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verify that ALL blog repositories are workspace-safe by checking their
 * source code for proper patterns.
 *
 * Key requirements:
 * - Repositories must extend Extbase\Persistence\Repository (workspace-aware)
 * - Repositories must NOT manually query t3ver_* fields
 * - Repositories must NOT depend on typo3/cms-workspaces
 * - Repositories must use Extbase query API (not raw SQL)
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
        foreach (glob($base . '/*Repository.php') ?: [] as $path) {
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

        self::assertStringContainsString(
            'extends Repository',
            $content,
            basename($path) . ' must extend Extbase Repository for workspace-safe queries.'
        );
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
            basename($path) . ' must NOT manually query workspace fields. '
            . 'Workspace overlay is handled by TYPO3 Core.'
        );
    }

    #[Test]
    #[DataProvider('repositoryFileProvider')]
    public function repositoryDoesNotDependOnWorkspacesExtension(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        self::assertStringNotContainsString(
            'WorkspaceService',
            $content,
            basename($path) . ' must NOT depend on WorkspaceService. '
            . 'Blog must work without workspaces installed.'
        );
    }

    #[Test]
    #[DataProvider('repositoryFileProvider')]
    public function repositoryPrimaryQueriesUseExtbaseApi(string $path): void
    {
        $content = file_get_contents($path);
        self::assertNotFalse($content);

        // All repositories must use $this->createQuery() for primary domain
        // queries. ConnectionPool usage is acceptable for auxiliary queries
        // (MM relations, aggregate counts, page tree traversal) because those
        // query helper tables that are not the repository's own domain model.
        self::assertStringContainsString(
            'extends Repository',
            $content,
            basename($path) . ' must extend Extbase Repository for workspace-safe primary queries.'
        );

        // If ConnectionPool is used, verify the repository also uses createQuery()
        // for its main domain model queries.
        if (str_contains($content, 'ConnectionPool')) {
            self::assertStringContainsString(
                'createQuery()',
                $content,
                basename($path) . ' uses ConnectionPool but must also use '
                . 'createQuery() for primary domain queries (workspace-safe).'
            );
        }
    }

    #[Test]
    public function allExpectedRepositoriesExist(): void
    {
        $base = self::getRepoBase();
        $expected = [
            'PostRepository.php',
            'CommentRepository.php',
            'TagRepository.php',
            'AuthorRepository.php',
            'CategoryRepository.php',
        ];

        foreach ($expected as $file) {
            self::assertFileExists(
                $base . '/' . $file,
                'Repository file must exist: ' . $file
            );
        }
    }

    #[Test]
    public function postRepositoryUsesContextSingleton(): void
    {
        $content = file_get_contents(self::getRepoBase() . '/PostRepository.php');
        self::assertNotFalse($content);

        self::assertStringContainsString(
            'Context::class',
            $content,
            'PostRepository must use the Context singleton which carries WorkspaceAspect.'
        );
    }

    #[Test]
    public function commentRepositoryExistsAndExtends(): void
    {
        $content = file_get_contents(self::getRepoBase() . '/CommentRepository.php');
        self::assertNotFalse($content);

        self::assertStringContainsString(
            'extends Repository',
            $content,
            'CommentRepository must extend Extbase Repository.'
        );
    }
}
