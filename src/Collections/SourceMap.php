<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Collections;

use Illuminate\Support\Collection;
use Ngmy\LaravelAop\ValueObjects\CompiledClass;

/**
 * @extends Collection<class-string, CompiledClass>
 */
final class SourceMap extends Collection {}
