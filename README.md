# Laravel.Aop

[![Latest Stable Version](https://img.shields.io/packagist/v/ngmy/laravel.aop.svg?style=flat-square&label=stable)](https://packagist.org/packages/ngmy/laravel.aop)
[![Test Status](https://img.shields.io/github/actions/workflow/status/ngmy/laravel.aop/test.yml?style=flat-square&label=test)](https://github.com/ngmy/Laravel.aop/actions/workflows/test.yml)
[![Lint Status](https://img.shields.io/github/actions/workflow/status/ngmy/laravel.aop/lint.yml?style=flat-square&label=lint)](https://github.com/ngmy/Laravel.aop/actions/workflows/lint.yml)
[![Code Coverage](https://img.shields.io/coverallsCoverage/github/ngmy/Laravel.Aop?style=flat-square)](https://coveralls.io/github/ngmy/Laravel.Aop)
[![Total Downloads](https://img.shields.io/packagist/dt/ngmy/laravel.aop.svg?style=flat-square)](https://packagist.org/packages/ngmy/laravel.aop)

Laravel.Aop integrates Ray.Aop with Laravel. It provides fast AOP by static weaving.

## Installation

First, you should install Laravel.Aop via the Composer package manager:

```bash
composer require ngmy/laravel.aop
```

You will be asked if you trust the `olvlvl/composer-attribute-collector` package, so you should press `y`.

Next, you should configure the `olvlvl/composer-attribute-collector` package.

> [!TIP]
> Please see the [composer-attribute-collector documentation](https://github.com/olvlvl/composer-attribute-collector)
> to learn how to configure the `olvlvl/composer-attribute-collector` package.

Then, you should publish the Laravel.Aop configuration file using the `vendor:publish` Artisan command. This command
will publish the `aop.php` configuration file to your application's `config` directory:

```bash
php artisan vendor:publish --provider="Ngmy\LaravelAop\ServiceProvider"
```

## Usage

First, you should define the attribute.
For example, let's define the `Transactional` attribute:

```php
<?php

declare(strict_types=1);

namespace App\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Transactional {}
```

Next, you should define the interceptor.
For example, let's define the `TransactionalInterceptor` interceptor:

```php
<?php

declare(strict_types=1);

namespace App\Interceptors;

use Illuminate\Support\Facades\DB;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

class Transactional implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): mixed
    {
        return DB::transaction(static fn (): mixed => $invocation->proceed());
    }
}
```

> [!TIP]
> Please see the [Ray.Aop documentation](https://github.com/ray-di/Ray.Aop) to learn more about the interceptor.

Then, you should register the attribute and the interceptor in the `intercept` configuration option of the
`config/aop.php` configuration file.
For example, let's register the `Transactional` attribute and the `TransactionalInterceptor` interceptor:

```php
use App\Attributes\Transactional;
use App\Interceptors\TransactionalInterceptor;
use Ngmy\LaravelAop\Collections\InterceptMap;

'intercept' => InterceptMap::default()->merge([
    Transactional::class => [
        TransactionalInterceptor::class,
    ],
])->toArray(),
```

Then, you should annotate the methods that you want to intercept with the attribute.
For example, let's annotate the `createUser` method of the `UserService` class with the `Transactional` attribute:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Attributes\Transactional;
use App\Models\User;

class UserService
{
    #[Transactional]
    public function createUser(string $name): void
    {
        User::create(['name' => $name]);
    }
}
```

Finally, you should run the `aop:compile` Artisan command to compile the AOP classes:

```bash
php artisan aop:compile
```

You must run the `dump-autoload` Composer command before running the `aop:compile` Artisan command.
By default, the `aop:compile` Artisan command runs the `dump-autoload` Composer command with the `--no-scripts` option
internally before compiling the AOP classes.
If you want to run the `dump-autoload` Composer command yourself, you should run the `aop:compile` Artisan
command with the `--no-dump-autoload` option:

```bash
composer dump-autoload
php artisan aop:compile --no-dump-autoload
```

> [!WARNING]
> If you want to avoid overwriting the Composer autoload files, such as when optimizing the Composer autoload files in
> a production environment, you should run the `aop:compile` Artisan command with the `--no-dump-autoload` option.

> [!IMPORTANT]
> After modifying the `intercept` configuration option or the method attributes, you should recompile the AOP classes.
> However, if you only modify the arguments of the method attributes, you do not need to recompile the AOP classes.

Now, the methods annotated with the attribute will be intercepted by the interceptor.
In this example, the `createUser` method of the `UserService` class will be intercepted by the
`TransactionalInterceptor` and will be executed in a transaction.

> [!IMPORTANT]
> The methods annotated with the attribute are intercepted by the interceptor only when the class instance is
> dependency resolved from the service container. If the class instance is created directly, the methods are not
> intercepted.

## Watcher

Laravel.Aop provides a watcher that watches the changes of the files and recompiles the AOP classes automatically.
This is useful when you are developing the application.
You may start the watcher using the `aop:watch` Artisan command:

```bash
php artisan aop:watch
```

> [!NOTE]
> The watcher runs the `dump-autoload` Composer command with the `--no-scripts` option internally before recompiling the
> AOP classes.

You may configure the files to watch in the `watcher.paths` configuration option of the `config/aop.php` configuration
file.

## Tips

### Auto compilation

You may easily compile the AOP classes automatically after the `dump-autoload` Composer command is run by adding the
`@php artisan aop:compile --no-dump-autoload --ansi` script to the `post-autoload-dump` event hook of the
`composer.json` file:

```json
{
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi",
            "@php artisan aop:compile --no-dump-autoload --ansi"
        ]
    }
}
```

## Built-in attributes

- [Cache](https://ngmy.github.io/Laravel.Aop/namespaces/ngmy-laravelaop-aspects-cache-attributes.html)
- [Logging](https://ngmy.github.io/Laravel.Aop/namespaces/ngmy-laravelaop-aspects-logging-attributes.html)
- [Transaction](https://ngmy.github.io/Laravel.Aop/namespaces/ngmy-laravelaop-aspects-transaction-attributes.html)
- [Retry](https://ngmy.github.io/Laravel.Aop/namespaces/ngmy-laravelaop-aspects-retry-attributes.html)

## Changelog

Please see the [changelog](CHANGELOG.md).

## License

Laravel.Aop is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
