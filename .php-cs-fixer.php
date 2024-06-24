<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())->in(__DIR__);
$rules = [
    '@Symfony' => true,
    'no_unused_imports' => true,
    'concat_space' => [
        'spacing' => 'one',
    ],
];

return (new Config())
    ->setRules($rules)
    ->setFinder($finder);
