<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\Rendering;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verify the blog rendering chain is safe for both workspace and non-workspace
 * environments by analysing the source code of key components.
 *
 * Rendering chain for blog plugins in page templates:
 *   1. Fluid template calls <f:cObject typoscriptObjectPath="tt_content.{listType}.20" />
 *   2. CObjectViewHelper creates ContentObjectRenderer, sets PSR-7 request on it
 *   3. The .20 TypoScript path resolves to EXTBASEPLUGIN (registered via configurePlugin)
 *   4. ExtbasePluginContentObject creates Extbase Bootstrap with ContentObjectRenderer
 *   5. Bootstrap initializes ConfigurationManager with the request
 *   6. RequestBuilder creates ExtbaseRequest from PSR-7 request
 *   7. Controller uses Repository → Extbase QueryBuilder → WorkspaceRestriction
 *
 * Workspace context flows through: PSR-7 request → Context singleton → WorkspaceAspect
 *
 * This test verifies each link in this chain at the source level.
 */
final class BlogRenderingChainSafetyTest extends TestCase
{
    private static function getExtensionPath(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function getVendorPath(): string
    {
        return dirname(__DIR__, 4);
    }

    #[Test]
    public function cObjectViewHelperCreatesContentObjectRendererFromRequest(): void
    {
        $path = self::getVendorPath() . '/typo3/cms-fluid/Classes/ViewHelpers/CObjectViewHelper.php';
        if (!file_exists($path)) {
            self::markTestSkipped('CObjectViewHelper source not available.');
        }
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'setRequest',
            $source,
            'CObjectViewHelper must call setRequest() on ContentObjectRenderer '
            . 'so that workspace context flows from the PSR-7 request.'
        );
    }

    #[Test]
    public function cObjectViewHelperDoesNotHardcodeWorkspaceLogic(): void
    {
        $path = self::getVendorPath() . '/typo3/cms-fluid/Classes/ViewHelpers/CObjectViewHelper.php';
        if (!file_exists($path)) {
            self::markTestSkipped('CObjectViewHelper source not available.');
        }
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringNotContainsString(
            't3ver_',
            $source,
            'CObjectViewHelper must not contain workspace-specific logic. '
            . 'Workspace context is carried by the request, not by the VH.'
        );
    }

    #[Test]
    public function extbasePluginContentObjectPassesRequestToBootstrap(): void
    {
        $path = self::getVendorPath() . '/typo3/cms-extbase/Classes/ContentObject/ExtbasePluginContentObject.php';
        if (!file_exists($path)) {
            self::markTestSkipped('ExtbasePluginContentObject source not available.');
        }
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'setContentObjectRenderer',
            $source,
            'ExtbasePluginContentObject must pass ContentObjectRenderer to Bootstrap.'
        );

        self::assertStringContainsString(
            'initialize',
            $source,
            'ExtbasePluginContentObject must call Bootstrap::initialize() with request.'
        );
    }

    #[Test]
    public function extbaseBootstrapForwardsRequestToConfigurationManager(): void
    {
        $path = self::getVendorPath() . '/typo3/cms-extbase/Classes/Core/Bootstrap.php';
        if (!file_exists($path)) {
            self::markTestSkipped('Extbase Bootstrap source not available.');
        }
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'configurationManager->setRequest',
            $source,
            'Extbase Bootstrap must forward PSR-7 request to ConfigurationManager.'
        );
    }

    #[Test]
    public function postControllerExtendsActionController(): void
    {
        $path = self::getExtensionPath() . '/Classes/Controller/PostController.php';
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'extends ActionController',
            $source,
            'PostController must extend Extbase ActionController which is workspace-aware.'
        );
    }

    #[Test]
    public function postControllerInjectsPostRepository(): void
    {
        $path = self::getExtensionPath() . '/Classes/Controller/PostController.php';
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'PostRepository',
            $source,
            'PostController must use PostRepository (workspace-aware via Context).'
        );
    }

    #[Test]
    public function commentControllerExtendsActionController(): void
    {
        $path = self::getExtensionPath() . '/Classes/Controller/CommentController.php';
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'extends ActionController',
            $source,
            'CommentController must extend Extbase ActionController.'
        );
    }

    #[Test]
    public function widgetControllerExtendsActionController(): void
    {
        $path = self::getExtensionPath() . '/Classes/Controller/WidgetController.php';
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'extends ActionController',
            $source,
            'WidgetController must extend Extbase ActionController.'
        );
    }

    #[Test]
    public function extLocalconfDoesNotConditionallyCheckWorkspaces(): void
    {
        $path = self::getExtensionPath() . '/ext_localconf.php';
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringNotContainsString(
            'cms-workspaces',
            $source,
            'ext_localconf.php must not conditionally check for workspaces extension. '
            . 'Plugin registration must be identical with and without workspaces.'
        );

        self::assertStringNotContainsString(
            'WorkspaceService',
            $source,
            'ext_localconf.php must not reference WorkspaceService.'
        );
    }

    #[Test]
    public function blogPostsArePagesDoktype137(): void
    {
        $path = self::getExtensionPath() . '/Classes/Constants.php';
        $source = file_get_contents($path);
        self::assertNotFalse($source);

        self::assertStringContainsString(
            'DOKTYPE_BLOG_POST = 137',
            $source,
            'Blog posts are pages (doktype 137). Pages are always workspace-aware '
            . 'in TYPO3, so blog posts inherit workspace support automatically.'
        );
    }

    #[Test]
    public function noControllerDirectlyQueriesWorkspaceFields(): void
    {
        $controllerDir = self::getExtensionPath() . '/Classes/Controller';
        $paths = glob($controllerDir . '/*.php');
        foreach ($paths === false ? [] : $paths as $path) {
            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }
            self::assertDoesNotMatchRegularExpression(
                '/t3ver_wsid|t3ver_oid|t3ver_state/',
                $source,
                basename($path) . ' must not query workspace fields directly. '
                . 'Workspace handling is done by Core, not by controllers.'
            );
        }
    }

    #[Test]
    public function noControllerDependsOnWorkspacesExtension(): void
    {
        $controllerDir = self::getExtensionPath() . '/Classes/Controller';
        $paths = glob($controllerDir . '/*.php');
        foreach ($paths === false ? [] : $paths as $path) {
            $source = file_get_contents($path);
            if ($source === false) {
                continue;
            }
            // BackendController may legitimately reference workspace info for display
            if (str_contains(basename($path), 'Backend')) {
                continue;
            }
            self::assertStringNotContainsString(
                'WorkspaceService',
                $source,
                basename($path) . ' must not depend on WorkspaceService.'
            );
        }
    }
}
