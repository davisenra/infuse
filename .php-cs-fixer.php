<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())->in(__DIR__);
$rules = [
    '@PER-CS' => true,
];

return (new Config())
    ->setRules($rules)
    ->setFinder($finder);