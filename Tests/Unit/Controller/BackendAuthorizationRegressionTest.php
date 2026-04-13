<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BackendAuthorizationRegressionTest extends TestCase
{
    private function getSource(string $relativePath): string
    {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relativePath, '/');
        self::assertFileExists($path);
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        return $source;
    }

    #[Test]
    public function backendControllerDependsOnBackendAccessService(): void
    {
        $backendControllerSource = $this->getSource('Classes/Controller/BackendController.php');

        self::assertStringContainsString(
            'BackendAccessService',
            $backendControllerSource,
            'BackendController must depend on BackendAccessService for backend authorization checks.'
        );
    }

    #[Test]
    public function commentModerationChecksRecordPermissionsBeforeMutation(): void
    {
        $backendControllerSource = $this->getSource('Classes/Controller/BackendController.php');

        self::assertStringContainsString(
            'canModerateComment',
            $backendControllerSource,
            'updateCommentStatusAction() must verify comment moderation permission before mutating records.'
        );
    }

    #[Test]
    public function listingActionsScopeQueriesToAccessibleBlogSetups(): void
    {
        $backendControllerSource = $this->getSource('Classes/Controller/BackendController.php');

        self::assertStringContainsString(
            'findAllByPids',
            $backendControllerSource,
            'postsAction() must scope post queries to accessible blog setup ids.'
        );
        self::assertStringContainsString(
            'findAllByFilterAndBlogSetups',
            $backendControllerSource,
            'commentsAction() must scope comment queries to accessible blog setup ids.'
        );
    }

    #[Test]
    public function setupServiceFiltersBlogSetupsThroughBackendAccessService(): void
    {
        $setupServiceSource = $this->getSource('Classes/Service/SetupService.php');

        self::assertStringContainsString(
            'BackendAccessService',
            $setupServiceSource,
            'SetupService must depend on BackendAccessService to respect backend page mounts.'
        );
        self::assertStringContainsString(
            'filterAccessibleBlogSetups',
            $setupServiceSource,
            'SetupService::determineBlogSetups() must filter inaccessible blog roots.'
        );
    }
}
