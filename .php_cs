<?php

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR2' => true,
        'array_indentation' => true,
        'array_syntax' => array('syntax' => 'short'),
        'concat_space' => array('spacing' => 'one'),
        'function_typehint_space' => true,
        'hash_to_slash_comment' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => array(
            'curly_brace_block',
            'extra',
            'parenthesis_brace_block',
            'square_brace_block',
            'throw',
            'use',
        ),
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'object_operator_without_whitespace' => true,
        'single_blank_line_before_namespace' => true,
        'ternary_operator_spaces' => true,
        'trim_array_spaces' => true,
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => true,
    ))
    ->setIndent("  ")
    ->setLineEnding("\n")
;