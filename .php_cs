<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('*.xml')
    ->in(getcwd());

if (file_exists(__DIR__ . '/.gitignore')) {
    foreach (file(__DIR__ . '/.gitignore') as $ignore) {
        $ignore = trim($ignore);
        if (is_dir(__DIR__ . '/' . trim($ignore, '/'))) {
            $finder->exclude(trim($ignore, '/'));
        } else {
            $finder->notName(trim($ignore, '/'));
        }
    }
}

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . DIRECTORY_SEPARATOR . basename(__FILE__) . '.cache')
    ->finder($finder)
    ->level(\Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        'phpdoc_order',
        'align_double_arrow',
        'align_equals',
        'concat_with_spaces',
        'ereg_to_preg',
        'multiline_spaces_before_semicolon',
        'newline_after_open_tag',
        'no_blank_lines_before_namespace',
        'ordered_use',
//        'phpdoc_var_to_type',
        'header_comment',
        'short_array_syntax',
//        'php4_constructor',
    ]);
