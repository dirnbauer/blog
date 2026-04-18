<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Tests\Unit\Domain\Repository;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verify PostRepository source code uses Context-aware Typo3QuerySettings
 * and that its query construction is workspace-safe.
 *
 * In workspace mode the Context singleton carries a WorkspaceAspect (id > 0).
 * The PostRepository MUST initialise Typo3QuerySettings with the Context so
 * that FrontendRestrictionContainer / WorkspaceRestriction can filter by
 * t3ver_wsid correctly.
 *
 * Without workspaces the Context has WorkspaceAspect with id=0 (live),
 * and the same code path must still work (queries return live records only).
 */
final class PostRepositoryWorkspaceAwarenessTest extends TestCase
{
    private static string $repositorySource;

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 4) . '/Classes/Domain/Repository/PostRepository.php';
        self::assertFileExists($path);
        $source = file_get_contents($path);
        self::assertNotFalse($source);
        self::$repositorySource = $source;
    }

    #[Test]
    public function repositoryExtendsExtbaseRepository(): void
    {
        self::assertStringContainsString(
            'extends Repository',
            self::$repositorySource,
            'PostRepository must extend Extbase\Persistence\Repository for workspace-aware queries.',
        );
    }

    #[Test]
    public function repositoryUsesContextForQuerySettings(): void
    {
        self::assertStringContainsString(
            'Context::class',
            self::$repositorySource,
            'PostRepository must use TYPO3 Context singleton which carries WorkspaceAspect.',
        );
    }

    #[Test]
    public function repositoryInitializesTypo3QuerySettings(): void
    {
        self::assertStringContainsString(
            'Typo3QuerySettings',
            self::$repositorySource,
            'PostRepository must use Typo3QuerySettings which reads workspace context.',
        );
    }

    #[Test]
    public function repositoryPassesContextToQuerySettings(): void
    {
        self::assertMatchesRegularExpression(
            '/Typo3QuerySettings::class.*Context::class/s',
            self::$repositorySource,
            'Typo3QuerySettings must be initialised with Context '
            . 'so WorkspaceAspect flows into query restrictions.',
        );
    }

    #[Test]
    public function repositoryFiltersByBlogPostDoktype(): void
    {
        self::assertStringContainsString(
            'Constants::DOKTYPE_BLOG_POST',
            self::$repositorySource,
            'PostRepository must filter by DOKTYPE_BLOG_POST (137) — '
            . 'blog posts are pages, and pages are always workspace-aware in TYPO3.',
        );
    }

    #[Test]
    public function repositoryHasInitializeObjectMethod(): void
    {
        self::assertStringContainsString(
            'function initializeObject',
            self::$repositorySource,
            'PostRepository must define initializeObject() to set up workspace-safe query defaults.',
        );
    }

    #[Test]
    public function repositoryUsesSetDefaultQuerySettings(): void
    {
        self::assertStringContainsString(
            'setDefaultQuerySettings',
            self::$repositorySource,
            'PostRepository must call setDefaultQuerySettings() to apply Context-aware settings globally.',
        );
    }

    #[Test]
    public function repositoryDisablesStoragePageRespect(): void
    {
        self::assertStringContainsString(
            'setRespectStoragePage(false)',
            self::$repositorySource,
            'PostRepository must disable storage page respect — blog posts '
            . 'are pages in a page tree, not records in a storage folder.',
        );
    }

    #[Test]
    public function repositoryUsesLanguageAspectFromContext(): void
    {
        self::assertMatchesRegularExpression(
            "/Context::class\).*getAspect\('language'\)/s",
            self::$repositorySource,
            'PostRepository must read the language aspect from Context '
            . '(the same Context that carries WorkspaceAspect).',
        );
    }

    #[Test]
    public function repositoryFiltersWorkspaceRecordsInBackendContext(): void
    {
        self::assertStringContainsString(
            't3ver_wsid',
            self::$repositorySource,
            'PostRepository must filter by t3ver_wsid in backend context. '
            . 'Extbase does not apply WorkspaceRestriction when '
            . 'setIgnoreEnableFields(true) is used, so blog posts '
            . '(pages with doktype 137) need explicit workspace filtering '
            . 'to prevent workspace-only records from appearing in LIVE.',
        );
    }

    #[Test]
    public function repositoryAppliesWorkspaceFilterOnlyInBackend(): void
    {
        self::assertStringContainsString(
            'isBackend()',
            self::$repositorySource,
            'Workspace filtering via t3ver_wsid must only be applied in '
            . 'backend context.  Frontend workspace overlay is handled by '
            . 'the TSFE rendering pipeline.',
        );
    }

    #[Test]
    public function repositoryCatchesPageNotFoundException(): void
    {
        self::assertStringContainsString(
            'PageNotFoundException',
            self::$repositorySource,
            'PostRepository::initializeObject() must catch PageNotFoundException. '
            . 'When the current backend page is a workspace-only page '
            . '(t3ver_oid=0, t3ver_wsid>0) and the editor is in LIVE, '
            . 'the rootline cannot be resolved.  The repository must survive '
            . 'this so the DI container does not crash.',
        );
    }

    #[Test]
    public function repositoryUsesRequestForCurrentPost(): void
    {
        self::assertStringContainsString(
            'RequestUtility::getPageInformation',
            self::$repositorySource,
            'findCurrentPost() must use the workspace-aware frontend.page.information '
            . 'request attribute in TYPO3 v14.',
        );
    }

    #[Test]
    public function repositoryHandlesLanguageFallback(): void
    {
        self::assertStringContainsString(
            'applyLanguageFallback',
            self::$repositorySource,
            'PostRepository must handle language fallback — workspace overlay '
            . 'must also work for translated blog posts.',
        );
    }

    #[Test]
    public function repositoryDoesNotDependOnWorkspacesExtension(): void
    {
        self::assertStringNotContainsString(
            'WorkspaceService',
            self::$repositorySource,
            'PostRepository must NOT depend on typo3/cms-workspaces. '
            . 'It must work identically whether workspaces is installed or not.',
        );
    }
}
