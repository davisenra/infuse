<?php

namespace Tests\Fixtures;

readonly class FooWithDependency
{
    public function __construct(
        private Foo $foo,
    ) {
    }
}
