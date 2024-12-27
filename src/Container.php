<?php

declare(strict_types=1);

namespace Infuse;

use Infuse\Attributes\Singleton;
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
    private array $singletons = [];

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

        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        $this->resolving[$id] = true;

        try {
            if (class_exists($id)) {
                return $this->resolve($id);
            }

            if (isset($this->bindings[$id])) {
                $callable = $this->bindings[$id];

                return $callable($this);
            }
        } finally {
            unset($this->resolving[$id]);
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
                $instance = new $id();
            } else {
                $parameters = $constructor->getParameters();
                $dependencies = $this->resolveDependencies($parameters);
                $instance = $reflector->newInstanceArgs($dependencies);
            }

            $this->cacheIfSingleton($id, $reflector, $instance);

            return $instance;
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

    /**
     * @param \ReflectionClass<object> $reflector
     */
    private function cacheIfSingleton(string $id, \ReflectionClass $reflector, mixed $instance): void
    {
        if (!is_object($instance)) {
            return;
        }

        $isSingleton = [] !== $reflector->getAttributes(Singleton::class);

        if ($isSingleton) {
            $this->singletons[$id] = $instance;
        }
    }
}
