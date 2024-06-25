# Infuse 🍃

*A minimal PSR-11 implementation.*

## Features

- ✔️ Autowiring (powered by Reflection)
- ✔️ Simple API (only 3 methods)
- ✔️ Container can be built from definitions
- ✔️ Singleton pattern
- ⏳ Detects circular dependencies

## Documentation

Infuse only has three methods:

```php
public function get(string $id): mixed;
public function has(string $id): bool;
public function bind(string $id, callable $callable): void;
```

**Example:**

```php
use Infuse\Container;

$container = new Container();

// Container::bind tells the Container how to build a Bar object
$container->bind(Bar::class, function () {
    return new Bar();
});

// Container::has checks if there's a binding with the provided id
$container->has(Bar::class); // true

// the callable parameter receives the Container itself as argument
$container->bind(Foo::class, function (Container $c) {
    $bar = $c->get(Bar::class);
    return new Foo($bar):
});

// Container::get retrieves an instance from the Container, the bound callable will be called at this moment
$foo = $container->get(Foo::class);
$isFoo = $foo instanceof Foo; // true

// Every instance is a singleton, you'll always receive the same reference
$sameFoo = $container->get(Foo::class);
$sameInstance = $foo === $sameFoo; // true

// This will throw a ContainerException, ids must be unique
$container->bind(Bar::class, function (Container $c) {
    return new Bar();
});

// This will throw a NotFoundException, this id has not been bound
$container->get(Zee::class);

// You can bind basically anything
$container->bind('some_array', fn () => ['hello' => 'world']);
$container->bind('some_scalar', fn () => 42);
```

**Create from definitions:**

```php
// definitions.php
<?php

use Infuse\Container;

// should be shaped as array<string, callable>

return [
    GeoLocationService::class => function (Container $c) {
        $config = $c->get('config');
        return new GeoLocationService($config['GEOLOCATION_API_KEY']);
    },
    'config' => function () {
        return [
            'GEOLOCATION_API_KEY' => $_ENV['GEOLOCATION_API_KEY'],
        ];
    },
];
```

```php
use Infuse\ContainerFactory;

$definitions = require __DIR__ . '/definitions.php';
$container = ContainerFactory::FromDefinitions($definitions);
$container->has('config'); // true
$container->has(GeoLocationService::class); // true
```

## Installing

The recommended way to install Infuse is through [Composer](https://getcomposer.org/).

```bash
composer require infuse/infuse
```

## Tests

To execute the test suite, you'll need to install all development dependencies.

```bash
git clone https://github.com/davisenra/infuse
composer install
composer test
```

## License

This project is licensed under the MIT license. See [License File](LICENSE.txt) for more information.