<?php

declare(strict_types=1);

namespace Infuse;

use Infuse\Exception\ContainerException;
use Infuse\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container implements ContainerInterface
{
    /**
     * @var array<string, callable>
     */
    private array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @var array<string, true>
     */
    private array $resolving = [];

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id identifier of the entry to look for
     *
     * @return mixed entry
     *
     * @throws NotFoundExceptionInterface  no entry was found for the provided identifier
     * @throws ContainerExceptionInterface error while retrieving the entry
     */
    public function get(string $id): mixed
    {
        if (isset($this->resolving[$id])) {
            throw ContainerException::ForCircularDependency($id);
        }

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $this->resolving[$id] = true;

            try {
                $callable = $this->bindings[$id];
                $this->instances[$id] = $callable($this);

                return $this->instances[$id];
            } finally {
                unset($this->resolving[$id]);
            }
        }

        if (\class_exists($id)) {
            $this->resolving[$id] = true;
            try {
                $instance = $this->resolve($id);
                $this->instances[$id] = $instance;

                return $instance;
            } finally {
                unset($this->resolving[$id]);
            }
        }

        throw NotFoundException::ForBinding($id);
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id identifier of the entry to look for
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    /**
     * @param \Closure(Container): mixed $definition
     *
     * @throws ContainerExceptionInterface if provided id is not unique
     */
    public function bind(string $id, \Closure $definition): void
    {
        if ($this->has($id)) {
            throw ContainerException::ForAlreadyDefinedId($id);
        }

        $this->bindings[$id] = $definition;
    }

    /**
     * @param class-string $id
     *
     * @throws ContainerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolve(string $id): mixed
    {
        try {
            $reflector = new \ReflectionClass($id);

            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class {$id} is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            if (is_null($constructor)) {
                return new $id();
            }

            $parameters = $constructor->getParameters();
            $dependencies = $this->resolveDependencies($parameters);

            return $reflector->newInstanceArgs($dependencies);
        } catch (\ReflectionException $e) {
            throw new ContainerException("Unable to reflect class {$id}: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return array<int, mixed>
     *
     * @throws ContainerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType();

            if ($dependency instanceof \ReflectionNamedType && !$dependency->isBuiltin()) {
                $dependencies[] = $this->get($dependency->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerException("Unable to resolve dependency {$parameter->name}");
        }

        return $dependencies;
    }
}
