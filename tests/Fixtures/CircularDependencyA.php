<?php

namespace Tests\Fixtures;

class CircularDependencyA
{
    public function __construct(
        private CircularDependencyB $classB
    ) {
    }
}
