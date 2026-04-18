<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    die('This script supports command line usage only. Please check your command.');
}

$header = <<<EOF
This file is part of the package t3g/blog.

For the full copyright and license information, please read the
LICENSE file that was distributed with this source code.
EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setIndent('    ')
    ->setLineEnding("\n")
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'header_comment' => [
            'header' => $header,
        ],
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'author',
            ],
        ],
        'lowercase_cast' => true,
        'native_function_casing' => true,
        'no_alias_functions' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_unused_imports' => true,
        'no_unneeded_control_parentheses' => true,
        'no_whitespace_in_blank_line' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'self_accessor' => true,
        'single_line_comment_style' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'type_declaration_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude('.build')
            ->exclude('node_modules')
            ->exclude('var')
            ->in([
                __DIR__ . '/Build/Scripts',
                __DIR__ . '/Classes',
                __DIR__ . '/Configuration',
                __DIR__ . '/Tests',
            ])
            ->append([
                // ext_emconf.php is a data file: it is parsed by the install
                // tool and the TER pipeline as bare array config. It must NOT
                // carry `declare(strict_types=1)` -- excluded here so
                // `declare_strict_types` does not re-add it.
                __DIR__ . '/ext_localconf.php',
                __DIR__ . '/ext_tables.php',
            ])
    );
