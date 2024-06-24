<?php

namespace Infuse\Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    public static function forBinding(string $id): self
    {
        return new self(sprintf('No binding for "%s" could be found.', $id));
    }
}
