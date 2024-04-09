<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Tests\Feature\stubs\Targets;

use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute1;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute2;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute3;
use Ngmy\LaravelAop\Tests\Feature\stubs\Attributes\TestAttribute4;

class TestTarget1
{
    public function method1(): void {}

    #[TestAttribute1]
    public function method2(): void {}

    #[TestAttribute2]
    public function method3(): void {}

    #[TestAttribute1]
    #[TestAttribute2]
    public function method4(): void {}

    #[TestAttribute2]
    #[TestAttribute1]
    public function method5(): void {}

    #[TestAttribute3]
    public function method6(): void {}

    #[TestAttribute4]
    public function method7(): void {}
}
