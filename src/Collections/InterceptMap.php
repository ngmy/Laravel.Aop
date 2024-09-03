<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Collections;

use Illuminate\Support\Collection;
use Ray\Aop\MethodInterceptor;

/**
 * @extends Collection<class-string, list<class-string<MethodInterceptor>>>
 */
final class InterceptMap extends Collection {}
