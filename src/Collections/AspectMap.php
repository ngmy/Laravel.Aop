<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Collections;

use Illuminate\Support\Collection;
use Ray\Aop\Pointcut;

/**
 * @extends Collection<class-string, array<class-string, Pointcut>>
 */
final class AspectMap extends Collection {}
