<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . DIRECTORY_SEPARATOR . 'src')
    ->in(__DIR__ . DIRECTORY_SEPARATOR . 'tests')
;

$config = new PhpCsFixer\Config();
$config->setRules([
        '@PSR2' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
    ;

return $config;
