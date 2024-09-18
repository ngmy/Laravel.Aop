<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\utils\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class DoesNotDeleteCompiledDirectoryAfter {}
