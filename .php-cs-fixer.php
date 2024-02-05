<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->notName('.phpstorm.meta.php')
    ->notPath('scripts')
    ->notPath('vendor')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setRules([
        '@PhpCsFixer' => true,
        'blank_line_before_statement' => [
            'statements' => ['return']
        ],
        'increment_style' => [
            'style' => 'post'
        ],
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'no_multi_line'
        ],
        'modernize_types_casting' => true,
        'no_superfluous_phpdoc_tags' => false,
        'no_unset_cast' => false,
        'ordered_imports' => ['sort_algorithm' => 'length'],
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_no_alias_tag' => false,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last'
        ],
        'protected_to_private' => false,
        'psr_autoloading' => true,
        'simple_to_complex_string_variable' => false,
        'single_line_comment_style' => [
            'comment_types' => ['hash']
        ],
        'single_trait_insert_per_statement' => false,
        'ternary_to_null_coalescing' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false
        ],
    ])
    ->setLineEnding("\n");

// vim: ft=php sw=4 sts=4 et ai si
