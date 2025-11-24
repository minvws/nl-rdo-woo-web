<?php

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'node_modules'])
;

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
        'phpdoc_to_comment' => ['ignored_tags' => ['var']],
        'types_spaces' => ['space_multiple_catch' => 'single'],
        'no_multiline_whitespace_around_double_arrow' => false,
    ])
    ->setFinder($finder)
;
