<?php

namespace Tests\Fixtures;

class ClassWithDefaultParameters
{
    public function __construct(private readonly string $param = 'default')
    {
    }

    public function getParam(): string
    {
        return $this->param;
    }
}
