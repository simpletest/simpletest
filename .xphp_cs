<?php

// php-cs-fixer configuration
// https://mlocati.github.io/php-cs-fixer-configurator/

if (!file_exists(__DIR__.'/src')) {
    exit(0);
}

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        '@PHP71Migration' => true,
        '@PSR2' => true,
        //'@PHP73Migration' => true, (heredoc identation)
        'array_syntax' => ['syntax' => 'short'],
        'combine_nested_dirname' => true,
        'fopen_flags' => false,
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'ordered_imports' => true,
        'phpdoc_no_empty_return' => false, // triggers almost always false positive
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        'protected_to_private' => false,
        'cast_spaces' => ['space' => 'single'],
        'class_attributes_separation' => ['elements' => ['method']],
        'no_blank_lines_after_class_opening' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_consecutive_blank_lines' => true,
        'no_leading_import_slash' => true,
        'no_leading_namespace_whitespace' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_unused_imports' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'phpdoc_align' => true,
        'phpdoc_indent' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_package' => true,
        'phpdoc_order' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'psr0' => true,
        'single_blank_line_before_namespace' => true,
        'standardize_not_equals' => true,
        'ternary_operator_spaces' => true,
        'trailing_comma_in_multiline_array' => false
    ])
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder(
        PhpCsFixer\Finder::create()
        ->files()
        ->in(__DIR__.'/src')
        ->in(__DIR__.'/test')
        ->name('*.php')
        // exclude folders by regexp pattern
        ->notPath('#/docs/#')
        ->notPath('#/packages/#')
        ->notPath('#/tutorials/#')
        // exclude file by regexp pattern
        ->notPath('#test_with_parse_error.php#')
        // this file itself
        ->append([__FILE__])
    );
