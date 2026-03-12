<?php

declare(strict_types=1);

namespace T3G\AgencyPack\Blog\Tests\Unit\ViewHelper;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verify the deprecated ContentListOptionsViewHelper still exists for
 * backward compatibility but is properly marked as deprecated.
 *
 * The ViewHelper created synthetic tt_content records with fake UIDs
 * (from Constants::LISTTYPE_TO_FAKE_UID_MAPPING) that lack workspace
 * system fields (t3ver_wsid, t3ver_oid, t3ver_state, t3ver_stage).
 *
 * It MUST remain in the codebase so sitepackages that have not yet
 * updated their template overrides don't get a class-not-found error.
 * But it MUST NOT be used by the shipped templates.
 */
final class ContentListOptionsViewHelperDeprecationTest extends TestCase
{
    private static string $viewHelperSource;
    private const VIEWHELPER_CLASS = 'T3G\\AgencyPack\\Blog\\ViewHelpers\\Data\\ContentListOptionsViewHelper';

    public static function setUpBeforeClass(): void
    {
        $path = dirname(__DIR__, 3) . '/Classes/ViewHelpers/Data/ContentListOptionsViewHelper.php';
        self::assertFileExists($path);
        $source = file_get_contents($path);
        self::assertNotFalse($source);
        self::$viewHelperSource = $source;
    }

    #[Test]
    public function classFileExists(): void
    {
        $path = dirname(__DIR__, 3) . '/Classes/ViewHelpers/Data/ContentListOptionsViewHelper.php';
        self::assertFileExists($path, 'ViewHelper must still exist for backward compatibility.');
    }

    #[Test]
    public function classIsMarkedDeprecated(): void
    {
        self::assertStringContainsString(
            '@deprecated',
            self::$viewHelperSource,
            'ContentListOptionsViewHelper must be marked @deprecated.'
        );
    }

    #[Test]
    public function classExtendsAbstractViewHelper(): void
    {
        self::assertStringContainsString(
            'extends AbstractViewHelper',
            self::$viewHelperSource,
            'Must extend AbstractViewHelper for Fluid compatibility.'
        );
    }

    #[Test]
    public function classHasListTypeArgument(): void
    {
        self::assertStringContainsString(
            "'listType'",
            self::$viewHelperSource,
            'Must accept listType argument for backward compatibility.'
        );
    }

    #[Test]
    public function classHasAsArgument(): void
    {
        self::assertStringContainsString(
            "'as'",
            self::$viewHelperSource,
            'Must accept as argument for variable naming.'
        );
    }

    #[Test]
    public function classUsesListTypeMappingConstants(): void
    {
        self::assertStringContainsString(
            'LISTTYPE_TO_FAKE_UID_MAPPING',
            self::$viewHelperSource,
            'Must reference LISTTYPE_TO_FAKE_UID_MAPPING for UID generation.'
        );
    }

    #[Test]
    public function classHasRenderMethod(): void
    {
        self::assertStringContainsString(
            'function render',
            self::$viewHelperSource,
            'Must implement render() method.'
        );
    }

    #[Test]
    public function classSetsCTypeInData(): void
    {
        self::assertStringContainsString(
            "'CType'",
            self::$viewHelperSource,
            'Must set CType in the synthetic data array.'
        );
    }

    #[Test]
    public function classDoesNotSetWorkspaceFields(): void
    {
        self::assertStringNotContainsString(
            't3ver_wsid',
            self::$viewHelperSource,
            'ViewHelper must NOT set t3ver_wsid — it creates synthetic data '
            . 'that is NOT a real database row. This is WHY it was deprecated.'
        );
    }

    #[Test]
    public function classDoesNotSetSysLanguageUid(): void
    {
        self::assertStringNotContainsString(
            'sys_language_uid',
            self::$viewHelperSource,
            'ViewHelper must NOT set sys_language_uid — synthetic records '
            . 'with missing/partial system fields cause IncompleteRecordException.'
        );
    }

    #[Test]
    public function noTemplateUsesTheViewHelper(): void
    {
        $templateBase = dirname(__DIR__, 3) . '/Resources/Private/Templates';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($templateBase, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'html') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }
            self::assertStringNotContainsString(
                'contentListOptions',
                $content,
                sprintf(
                    'Template %s must NOT use ContentListOptionsViewHelper. '
                    . 'It was deprecated because synthetic records break '
                    . 'record-transformation in TYPO3 v14.',
                    str_replace($templateBase . '/', '', $file->getPathname())
                )
            );
        }
    }
}
