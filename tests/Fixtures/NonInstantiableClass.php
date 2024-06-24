<?php

namespace Tests\Fixtures;

class NonInstantiableClass
{
    private function __construct()
    {
    }

    public static function make(): self
    {
        return new self();
    }
}
