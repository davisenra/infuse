<?php

declare(strict_types=1);

namespace Tests;

use Infuse\Container;
use Infuse\Exception\ContainerException;
use Infuse\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Foo;
use Tests\Fixtures\FooWithDeeplyNestedDependency;
use Tests\Fixtures\FooWithDependency;
use Tests\Fixtures\NonInstantiableClass;

#[CoversClass(Container::class)]
class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    #[Test]
    public function itCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    #[Test]
    public function itCanBindAClassToTheContainer(): void
    {
        $this->container->bind(Foo::class, fn () => new Foo());

        $this->assertTrue($this->container->has(Foo::class));
    }

    #[Test]
    public function itCanBindAnArrayToTheContainer(): void
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
    public function itCanBindAScalarToTheContainer(): void
    {
        $this->container->bind('latency', fn () => 123);

        $this->assertTrue($this->container->has('latency'));
        $this->assertEquals(123, $this->container->get('latency'));
    }

    #[Test]
    public function itGetsTheSameInstanceIfAlreadyInstantiated(): void
    {
        $this->container->bind(Foo::class, fn () => new Foo());

        $someInstance = $this->container->get(Foo::class);
        $anotherInstance = $this->container->get(Foo::class);

        $this->assertSame($someInstance, $anotherInstance);
    }

    #[Test]
    public function itThrowsAnExceptionIfBindingCannotBeResolved(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No binding for "Bar" could be found.');

        $this->container->get('Bar');
    }

    #[Test]
    public function itCanAutoWireBindingDependencies(): void
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
    public function itCanAutoWireWithDeeplyNestedDependency(): void
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
    public function itThrowsAContainerExceptionIfSomethingIsNotInstantiable(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Class Tests\Fixtures\NonInstantiableClass is not instantiable');

        $this->container->get(NonInstantiableClass::class);
    }
}
