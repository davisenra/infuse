<?php

namespace Tests\Fixtures;

class CircularDependencyB
{
    public function __construct(
        private CircularDependencyA $classA
    ) {
    }
}
