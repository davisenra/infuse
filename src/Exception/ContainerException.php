<?php

namespace Infuse\Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
    public static function ForAlreadyDefinedId(string $id): ContainerExceptionInterface
    {
        return new self(sprintf('%s is already defined', $id));
    }
}
