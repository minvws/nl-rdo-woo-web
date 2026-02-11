<?php

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/apps',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude(['var', 'node_modules'])
    ->append([__DIR__ . '/phparkitect.php']);

return (new PhpCsFixer\Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'increment_style' => ['style' => 'post'],
        'no_mixed_echo_print' => ['use' => 'print'],
        'not_operator_with_space' => false,
        'not_operator_with_successor_space' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_to_comment' => ['ignored_tags' => ['throws', 'var']],
        'types_spaces' => ['space_multiple_catch' => 'single'],
        'no_multiline_whitespace_around_double_arrow' => false,
        'global_namespace_import' => ['import_classes' => true, 'import_constants' => true, 'import_functions' => true],
        'single_line_throw' => false,
        'multiline_whitespace_before_semicolons' => true,
    ])
    ->setFinder($finder);
