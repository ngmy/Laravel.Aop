<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PHP80Migration:risky' => true,
    '@PHP81Migration' => true,
    '@PhpCsFixer' => true,
    '@PhpCsFixer:risky' => true,
    '@PHPUnit100Migration:risky' => true,
    /** @link https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/issues/4157 */
    'return_assignment' => false,
])
    ->setFinder($finder)
;
