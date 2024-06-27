<?php

namespace Tests\Fixtures;

class ClassWithOptionalDependency
{
    public function __construct(private readonly ?Foo $foo = null)
    {
    }

    public function getFoo(): ?Foo
    {
        return $this->foo;
    }
}
