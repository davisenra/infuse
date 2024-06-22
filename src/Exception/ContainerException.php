<?php

namespace Infuse\Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerException implements ContainerExceptionInterface
{
    public function getMessage(): string {}

    public function getCode() {}

    public function getFile(): string {}

    public function getLine(): int {}

    public function getTrace(): array {}

    public function getTraceAsString(): string {}

    public function getPrevious() {}

    public function __toString() {}
}
