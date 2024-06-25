<?php

namespace Tests;

use Infuse\Container;
use Infuse\ContainerFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Foo;
use Tests\Fixtures\FooWithDependency;

#[CoversClass(ContainerFactory::class)]
#[UsesClass(Container::class)]
class ContainerFactoryTest extends TestCase
{
    #[Test]
    public function itCanBuildAContainerFromDefinitionsArray(): void
    {
        $definitions = [
            'some_scalar' => fn () => 123,
            'some_string' => fn () => 'hello',
            'some_array' => fn () => [
                'foo' => 'bar',
            ],
            'some_class' => fn () => new Foo(),
            'some_class_with_dependencies' => function (Container $c) {
                /** @var Foo $foo */
                $foo = $c->get(Foo::class);

                return new FooWithDependency($foo);
            },
        ];

        $container = ContainerFactory::fromDefinitions($definitions);

        $this->assertInstanceOf(Container::class, $container);
        $this->assertTrue($container->has('some_scalar'));
        $this->assertTrue($container->has('some_string'));
        $this->assertTrue($container->has('some_array'));
        $this->assertTrue($container->has('some_class'));
        $this->assertTrue($container->has('some_class_with_dependencies'));
    }
}
