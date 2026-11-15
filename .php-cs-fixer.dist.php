<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setCacheFile(__DIR__ . '/build/.php-cs-fixer.cache')
    ->setRules([
        '@PER-CS2.0' => true,
        'function_declaration' => ['closure_fn_spacing' => 'one'],
        'single_line_empty_body' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/tools'])
            ->name('*.php')
            ->append([__FILE__]),
    );
