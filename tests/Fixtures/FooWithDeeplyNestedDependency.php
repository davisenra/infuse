<?php

namespace Tests\Fixtures;

readonly class FooWithDeeplyNestedDependency
{
    public function __construct(
        private FooWithDependency $foo
    ) {
    }
}
