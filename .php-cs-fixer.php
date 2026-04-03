<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'braces_position' => [
            'classes_opening_brace'             => 'next_line_unless_newline_at_signature_end',
            'functions_opening_brace'           => 'next_line_unless_newline_at_signature_end',
            'control_structures_opening_brace'  => 'next_line_unless_newline_at_signature_end',
            'anonymous_classes_opening_brace'   => 'next_line_unless_newline_at_signature_end',
            'anonymous_functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
        ],
        'blank_line_after_namespace' => false,
        'declare_strict_types' => false,
        'no_blank_lines_after_phpdoc' => false,
        'blank_lines_before_namespace' => ['min_line_breaks' => 0, 'max_line_breaks' => 1],
        'ordered_imports' => true,
        'single_import_per_statement' => false,
        'group_import' => true,
        'declare_strict_types' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
