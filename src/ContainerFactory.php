<?php

declare(strict_types=1);

namespace Infuse;

abstract class ContainerFactory
{
    /**
     * @param array<string, callable> $definitions
     */
    public static function FromDefinitions(array $definitions): Container
    {
        $container = new Container();

        foreach ($definitions as $id => $definition) {
            $container->bind($id, $definition);
        }

        return $container;
    }
}
