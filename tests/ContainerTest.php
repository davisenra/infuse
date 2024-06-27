<?php

declare(strict_types=1);

namespace Tests;

use Infuse\Container;
use Infuse\Exception\ContainerException;
use Infuse\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\CircularDependencyA;
use Tests\Fixtures\CircularDependencyB;
use Tests\Fixtures\ClassWithDefaultParameters;
use Tests\Fixtures\ClassWithOptionalDependency;
use Tests\Fixtures\Foo;
use Tests\Fixtures\FooWithDeeplyNestedDependency;
use Tests\Fixtures\FooWithDependency;
use Tests\Fixtures\NonInstantiableClass;

#[CoversClass(Container::class)]
#[UsesClass(NotFoundException::class)]
#[UsesClass(ContainerException::class)]
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    #[Test]
    public function containerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    #[Test]
    public function bindingClassToContainer(): void
    {
        $this->container->bind(Foo::class, fn () => new Foo());

        $this->assertTrue($this->container->has(Foo::class));
    }

    #[Test]
    public function bindingArrayToContainer(): void
    {
        $array = [
            'some_param' => true,
            'another_param' => 12.0,
        ];

        $this->container->bind('some_array', fn () => $array);

        $this->assertTrue($this->container->has('some_array'));
        $this->assertEquals($array, $this->container->get('some_array'));
    }

    #[Test]
    public function bindingScalarToContainer(): void
    {
        $this->container->bind('latency', fn () => 123);

        $this->assertTrue($this->container->has('latency'));
        $this->assertEquals(123, $this->container->get('latency'));
    }

    #[Test]
    public function bindingCallableToContainer(): void
    {
        $this->container->bind('callable', function () {
            return function () {
                return 123;
            };
        });

        $this->assertTrue($this->container->has('callable'));
        $this->assertIsCallable($this->container->get('callable'));
    }

    #[Test]
    public function singletonPatternEnsuresSameInstance(): void
    {
        $this->container->bind(Foo::class, fn () => new Foo());

        $someInstance = $this->container->get(Foo::class);
        $anotherInstance = $this->container->get(Foo::class);

        $this->assertSame($someInstance, $anotherInstance);
    }

    #[Test]
    public function exceptionIsThrownOnBindingDuplicateId(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Tests\Fixtures\Foo is already defined');

        $this->container->bind(Foo::class, fn () => new Foo());
        $this->container->bind(Foo::class, fn () => new Foo());
    }

    #[Test]
    public function exceptionIsThrownOnUnresolvedBinding(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No binding for "Bar" could be found.');

        $this->container->get('Bar');
    }

    #[Test]
    public function autowireDependencies(): void
    {
        $this->container->bind(FooWithDependency::class, function (Container $c) {
            /** @var Foo $dependency */
            $dependency = $c->get(Foo::class);

            return new FooWithDependency($dependency);
        });

        $instance = $this->container->get(FooWithDependency::class);

        $this->assertInstanceOf(FooWithDependency::class, $instance);
    }

    #[Test]
    public function autowireDeeplyNestedDependencies(): void
    {
        $this->container->bind(FooWithDeeplyNestedDependency::class, function (Container $c) {
            /** @var FooWithDependency $fooWithDependency */
            $fooWithDependency = $c->get(FooWithDependency::class);

            return new FooWithDeeplyNestedDependency($fooWithDependency);
        });

        $instance = $this->container->get(FooWithDeeplyNestedDependency::class);

        $this->assertInstanceOf(FooWithDeeplyNestedDependency::class, $instance);
    }

    #[Test]
    public function exceptionOnNonInstantiableClass(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Class Tests\Fixtures\NonInstantiableClass is not instantiable');

        $this->container->get(NonInstantiableClass::class);
    }

    #[Test]
    public function getThrowsExceptionIfCallableFails(): void
    {
        $this->container->bind('something', function () {
            throw new \Exception('Something wrong happened inside the callable');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something wrong happened inside the callable');

        $this->container->get('something');
    }

    #[Test]
    public function getWithDefaultParameters(): void
    {
        $this->container->bind(ClassWithDefaultParameters::class, fn () => new ClassWithDefaultParameters());

        $instance = $this->container->get(ClassWithDefaultParameters::class);
        $this->assertInstanceOf(ClassWithDefaultParameters::class, $instance);
        $this->assertEquals('default', $instance->getParam());
    }

    #[Test]
    public function resolveHandlesOptionalDependencies(): void
    {
        $this->container->bind(ClassWithOptionalDependency::class, fn () => new ClassWithOptionalDependency());

        $instance = $this->container->get(ClassWithOptionalDependency::class);
        $this->assertInstanceOf(ClassWithOptionalDependency::class, $instance);
        $this->assertNull($instance->getFoo());
    }

    #[Test]
    public function resolveThrowsExceptionForCircularDependencies(): void
    {
        $this->container->bind(CircularDependencyA::class, function (Container $c) {
            /** @var CircularDependencyB $circularDependency */
            $circularDependency = $c->get(CircularDependencyB::class);

            return new CircularDependencyA($circularDependency);
        });

        $this->container->bind(CircularDependencyB::class, function (Container $c) {
            /** @var CircularDependencyA $circularDependency */
            $circularDependency = $c->get(CircularDependencyA::class);

            return new CircularDependencyB($circularDependency);
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected while trying to resolve Tests\Fixtures\CircularDependencyA');

        $this->container->get(CircularDependencyA::class);
    }
}
