<?php

namespace Infuse\Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
    public static function ForAlreadyDefinedId(string $id): ContainerExceptionInterface
    {
        return new self("$id is already defined");
    }

    public static function ForCircularDependency(string $id): ContainerExceptionInterface
    {
        return new self("Circular dependency detected while trying to resolve $id");
    }
}
